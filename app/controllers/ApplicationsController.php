<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/DefenderApplication.php';
require_once MODELS_PATH . '/Affiliate.php';

/**
 * Postulaciones para defensor de derechos humanos: aceptar, rechazar,
 * pedir correcciones y habilitar el pago de la anualidad.
 */
class ApplicationsController extends Controller {

    private const PER_PAGE = 25;

    public function index(): void {
        Auth::requireAdmin();

        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => $_GET['status'] ?? '',
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $model = new DefenderApplication();
        $total = $model->countWithFilters($filters);

        $this->view('applications/index', [
            'title'        => 'Postulaciones',
            'applications' => $model->listWithFilters($filters, self::PER_PAGE, $offset),
            'filters'      => $filters,
            'page'         => $page,
            'total'        => $total,
            'pages'        => max(1, (int) ceil($total / self::PER_PAGE)),
            'counts'       => $model->countsByStatus(),
        ]);
    }

    public function show(string $id): void {
        Auth::requireAdmin();
        $model = new DefenderApplication();
        $app   = $model->findDetailed((int) $id);
        if (!$app) {
            $this->flash('error', 'Postulación no encontrada.');
            $this->redirect('applications');
        }

        $this->view('applications/show', [
            'title'       => 'Postulación #' . $app['id'],
            'application' => $app,
            'documents'   => $model->getDocuments((int) $id),
            'allowed'     => DefenderApplication::TRANSICIONES[$app['status']] ?? [],
        ]);
    }

    /**
     * Resolver la postulación. Al aprobar se crea o vincula el afiliado como
     * PROSPECTO y la postulación queda en PENDIENTE_PAGO: la acreditación
     * definitiva sólo llega por el webhook de Stripe cuando se paga.
     */
    public function decide(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new DefenderApplication();
        $app   = $model->findDetailed((int) $id);
        if (!$app) {
            $this->flash('error', 'Postulación no encontrada.');
            $this->redirect('applications');
        }

        $nuevo    = $_POST['status'] ?? '';
        $notas    = trim($_POST['review_notes'] ?? '');
        $anterior = $app['status'];

        // El servidor revalida la transición aunque la interfaz ya la haya
        // filtrado. Se responde con redirect + aviso porque quien envía es un
        // formulario del navegador; el rechazo queda además en la bitácora.
        if (!DefenderApplication::puedeTransicionar($anterior, $nuevo)) {
            Auth::logActivity('TRANSICION_RECHAZADA', 'defender_applications', (int) $id,
                "Intento inválido {$anterior} → {$nuevo}");
            $this->flash('error', "No se permite pasar de {$anterior} a {$nuevo}. "
                . 'Una postulación acreditada no puede rechazarse desde aquí.');
            $this->redirect("applications/{$id}");
        }
        if (in_array($nuevo, ['RECHAZADA', 'CON_OBSERVACIONES'], true) && $notas === '') {
            $this->flash('error', 'Rechazar o pedir correcciones exige escribir el motivo.');
            $this->redirect("applications/{$id}");
        }

        $adminId = (int) Auth::currentUser()['id'];

        if ($nuevo === 'APROBADA') {
            // Aprobar deja la postulación lista para cobrar la anualidad
            $this->ensureAffiliate($app);
            $model->changeStatus((int) $id, 'PENDIENTE_PAGO', $adminId, $notas);
            Auth::logActivity('APROBAR_POSTULACION', 'defender_applications', (int) $id,
                "{$anterior} → PENDIENTE_PAGO");
            $this->flash('success', 'Postulación aprobada. Queda pendiente el pago de la anualidad en la app.');
            $this->redirect("applications/{$id}");
        }

        $model->changeStatus((int) $id, $nuevo, $adminId, $notas);
        Auth::logActivity('RESOLVER_POSTULACION', 'defender_applications', (int) $id,
            "{$anterior} → {$nuevo}");
        $this->flash('success', 'Postulación actualizada.');
        $this->redirect("applications/{$id}");
    }

    /** Crea el afiliado en estado PROSPECTO si la postulación aún no tiene uno */
    private function ensureAffiliate(array $app): void {
        if (!empty($app['affiliate_id'])) {
            return;
        }
        $affiliates = new Affiliate();
        $userId     = (int) $app['user_id'];

        $existing = $affiliates->findByUserId($userId);
        if ($existing) {
            $affiliateId = (int) $existing['id'];
        } else {
            $partes   = preg_split('/\s+/', trim((string) $app['user_name']), 2);
            $nombre   = $partes[0] ?? 'Sin nombre';
            $apellido = $partes[1] ?? '-';
            $affiliateId = $affiliates->create([
                'affiliate_number' => $affiliates->generateNumberForUser($userId),
                'user_id'    => $userId,
                'first_name' => $nombre,
                'last_name'  => $apellido,
                'email'      => $app['user_email'],
                'state'      => 'PROSPECTO',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Database::getInstance()->execute(
            "UPDATE defender_applications SET affiliate_id = ? WHERE id = ?",
            [$affiliateId, (int) $app['id']]
        );
    }
}
