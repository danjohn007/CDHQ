<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Subscription.php';

class CredentialsController extends Controller {

    /**
     * Credencial digital del afiliado (vista interna, requiere sesión).
     */
    public function show(string $id): void {
        Auth::requireAuth();
        $model     = new Affiliate();
        $affiliate = $model->findWithDetails((int)$id);
        if (!$affiliate) {
            $this->flash('error', 'Afiliado no encontrado.');
            $this->redirect('affiliates');
        }

        // El rol AFILIADO sólo puede ver su propia credencial
        $current = Auth::currentUser();
        if ($current['role'] === ROLE_AFILIADO) {
            $own = $model->findByUserId((int)$current['id']);
            if (!$own || (int)$own['id'] !== (int)$id) {
                $this->flash('error', 'Acceso denegado.');
                $this->redirect('dashboard');
            }
        }

        $subModel  = new Subscription();
        $activeSub = $subModel->getActiveSubscription((int)$id);

        // El QR apunta a la página PÚBLICA de verificación, no al perfil privado.
        // Se usa un token aleatorio para que el padrón no pueda enumerarse.
        $token           = $model->ensureVerificationToken((int)$id);
        $verificationUrl = self::verificationUrl($token);

        $this->view('credentials/show', [
            'title'           => 'Credencial Digital',
            'affiliate'       => $affiliate,
            'activeSub'       => $activeSub,
            'verificationUrl' => $verificationUrl,
        ]);
    }

    /**
     * Verificación PÚBLICA de una credencial: es la página a la que lleva el
     * código QR impreso. No requiere sesión y muestra únicamente los datos
     * mínimos necesarios para acreditar la credencial (principio de
     * minimización de datos): folio, nombre, estado y vigencia.
     *
     * Nunca expone correo, teléfono ni domicilio del afiliado.
     */
    /**
     * Verificación manual PÚBLICA, de respaldo para las credenciales impresas
     * cuyo código QR apunta al dominio anterior (cdhq.app, ya inexistente).
     * Pide folio y apellido para evitar que se recorra el padrón.
     */
    public function verifyForm(): void {
        $folio    = trim($_GET['folio']    ?? '');
        $apellido = trim($_GET['apellido'] ?? '');

        // Sin datos todavía: sólo mostrar el formulario
        if ($folio === '' || $apellido === '') {
            $this->view('credentials/verify_form', [
                'title'    => 'Verificar una credencial',
                'folio'    => $folio,
                'apellido' => $apellido,
                'searched' => false,
            ]);
            return;
        }

        try {
            $model     = new Affiliate();
            $affiliate = $model->findByNumberAndLastName($folio, $apellido);
        } catch (Throwable $e) {
            error_log('[CDHQ] Fallo en verificación manual: ' . $e->getMessage());
            $this->view('credentials/verify_form', [
                'title'    => 'Verificar una credencial',
                'folio'    => $folio,
                'apellido' => $apellido,
                'searched' => true,
                'dbError'  => true,
            ]);
            return;
        }

        if (!$affiliate) {
            http_response_code(404);
            $this->view('credentials/verify_form', [
                'title'    => 'Verificar una credencial',
                'folio'    => $folio,
                'apellido' => $apellido,
                'searched' => true,
                'notFound' => true,
            ]);
            return;
        }

        // Coincide: reutiliza la misma página de resultado que el QR
        $this->verify($model->ensureVerificationToken((int)$affiliate['id']));
    }

    public function verify(string $token): void {
        $affiliate = null;
        $activeSub = null;
        $isValid   = false;
        $dbError   = false;

        try {
            $model     = new Affiliate();
            $affiliate = $model->findByVerificationToken($token);

            if ($affiliate) {
                $subModel  = new Subscription();
                $activeSub = $subModel->getActiveSubscription((int)$affiliate['id']);
                // Una credencial vale si el afiliado está ACTIVO y su vigencia no expiró
                if ($affiliate['state'] === 'ACTIVO' && $activeSub) {
                    $isValid = strtotime($activeSub['end_date']) >= strtotime(date('Y-m-d'));
                }
            } else {
                http_response_code(404);
            }
        } catch (Throwable $e) {
            // Página pública: nunca mostrar detalles internos a quien escanea
            $dbError = true;
            http_response_code(503);
            error_log('[CDHQ] Fallo al verificar credencial: ' . $e->getMessage());
        }

        $this->view('credentials/verify', [
            'title'     => 'Verificación de credencial',
            'affiliate' => $affiliate,
            'activeSub' => $activeSub,
            'isValid'   => $isValid,
            'dbError'   => $dbError,
        ]);
    }

    /**
     * URL pública y absoluta de verificación para un token dado.
     * Usa BASE_URL para que siga funcionando aunque el sitio esté en modo
     * front controller (/index.php/verificar/...) por falta de mod_rewrite.
     */
    public static function verificationUrl(string $token): string {
        return rtrim(BASE_URL, '/') . '/verificar/' . $token;
    }
}
