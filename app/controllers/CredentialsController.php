<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Credential.php';

/**
 * Credenciales y su verificación pública.
 *
 * En cdhq_mobile el token del QR vive en `credentials.verification_token`
 * (64 hex). Cada renovación emite una credencial nueva y conserva la anterior.
 */
class CredentialsController extends Controller {

    private const PER_PAGE = 30;

    /** Listado administrativo */
    public function index(): void {
        Auth::requireAdmin();

        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => $_GET['status'] ?? '',
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $model = new Credential();
        $total = $model->countWithFilters($filters);

        $this->view('credentials/index', [
            'title'       => 'Credenciales',
            'credentials' => $model->listWithFilters($filters, self::PER_PAGE, $offset),
            'filters'     => $filters,
            'page'        => $page,
            'total'       => $total,
            'pages'       => max(1, (int) ceil($total / self::PER_PAGE)),
        ]);
    }

    /** Credencial digital imprimible (vista interna) */
    public function show(string $id): void {
        Auth::requireAdmin();
        $model      = new Credential();
        $credential = $model->findDetailed((int) $id);
        if (!$credential) {
            $this->flash('error', 'Credencial no encontrada.');
            $this->redirect('credentials');
        }

        $this->view('credentials/show', [
            'title'           => 'Credencial digital',
            'credential'      => $credential,
            'verificationUrl' => self::verificationUrl($credential['verification_token']),
        ]);
    }

    /**
     * Suspender o revocar. Nunca se borra la credencial: cambia de estado y
     * el motivo queda registrado.
     */
    public function changeStatus(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model      = new Credential();
        $credential = $model->findDetailed((int) $id);
        if (!$credential) {
            $this->flash('error', 'Credencial no encontrada.');
            $this->redirect('credentials');
        }

        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!in_array($status, Credential::ESTADOS, true)) {
            $this->flash('error', 'Estado no válido.');
            $this->redirect('credentials');
        }
        if (in_array($status, ['SUSPENDIDA', 'REVOCADA'], true) && $reason === '') {
            $this->flash('error', 'Suspender o revocar una credencial exige un motivo.');
            $this->redirect('credentials');
        }

        $model->changeStatus((int) $id, $status, $reason);
        Auth::logActivity('CAMBIAR_ESTADO_CREDENCIAL', 'credentials', (int) $id,
            "{$credential['credential_number']}: {$credential['status']} → {$status}"
            . ($reason !== '' ? " · {$reason}" : ''));
        $this->flash('success', 'Credencial actualizada.');
        $this->redirect('credentials');
    }

    /**
     * Verificación PÚBLICA (destino del QR). No requiere sesión y muestra
     * únicamente lo mínimo para acreditar la credencial: folio, nombre, estado
     * y vigencia. Nunca correo, teléfono ni domicilio.
     */
    public function verify(string $token): void {
        $credential = null;
        $isValid    = false;
        $dbError    = false;

        try {
            $credential = (new Credential())->findByToken($token);
            if ($credential) {
                $isValid = $this->isValid($credential);
            } else {
                http_response_code(404);
            }
        } catch (Throwable $e) {
            $dbError = true;
            http_response_code(503);
            error_log('[CDHQ] Fallo al verificar credencial: ' . $e->getMessage());
        }

        $this->view('credentials/verify', [
            'title'      => 'Verificación de credencial',
            'credential' => $credential,
            'isValid'    => $isValid,
            'dbError'    => $dbError,
        ]);
    }

    /**
     * Verificación manual PÚBLICA, respaldo para credenciales impresas cuyo QR
     * apunta al dominio anterior. Exige folio Y apellido para que el padrón no
     * pueda recorrerse probando folios consecutivos.
     */
    public function verifyForm(): void {
        $folio    = trim($_GET['folio'] ?? '');
        $apellido = trim($_GET['apellido'] ?? '');

        if ($folio === '' || $apellido === '') {
            $this->view('credentials/verify_form', [
                'title'    => 'Verificar una credencial',
                'folio'    => $folio,
                'apellido' => $apellido,
            ]);
            return;
        }

        try {
            $credential = (new Credential())->findByNumberAndLastName($folio, $apellido);
        } catch (Throwable $e) {
            error_log('[CDHQ] Fallo en verificación manual: ' . $e->getMessage());
            $this->view('credentials/verify_form', [
                'title'    => 'Verificar una credencial',
                'folio'    => $folio,
                'apellido' => $apellido,
                'dbError'  => true,
            ]);
            return;
        }

        if (!$credential) {
            http_response_code(404);
            $this->view('credentials/verify_form', [
                'title'    => 'Verificar una credencial',
                'folio'    => $folio,
                'apellido' => $apellido,
                'notFound' => true,
            ]);
            return;
        }

        $this->view('credentials/verify', [
            'title'      => 'Verificación de credencial',
            'credential' => $credential,
            'isValid'    => $this->isValid($credential),
            'dbError'    => false,
        ]);
    }

    /** Una credencial vale si está VIGENTE y su fecha de expiración no pasó */
    private function isValid(array $credential): bool {
        return $credential['status'] === 'VIGENTE'
            && strtotime($credential['expires_at']) >= time();
    }

    /** URL pública y absoluta de verificación para un token dado */
    public static function verificationUrl(string $token): string {
        return rtrim(BASE_URL, '/') . '/verificar/' . $token;
    }
}
