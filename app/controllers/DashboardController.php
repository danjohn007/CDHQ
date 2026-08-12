<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Subscription.php';
require_once MODELS_PATH . '/PaymentOrder.php';
require_once MODELS_PATH . '/SupportRequest.php';
require_once MODELS_PATH . '/DefenderApplication.php';
require_once MODELS_PATH . '/Credential.php';
require_once MODELS_PATH . '/User.php';

class DashboardController extends Controller {

    public function index(): void {
        Auth::requireAdmin();

        $affiliates   = new Affiliate();
        $orders       = new PaymentOrder();
        $requests     = new SupportRequest();
        $applications = new DefenderApplication();
        $credentials  = new Credential();
        $users        = new User();

        $reqByStatus = $requests->countsByStatus();
        $appByStatus = $applications->countsByStatus();
        $ordByStatus = $orders->countsByStatus();

        $stats = [
            'usuarios_activos'      => (int) ($users->countsByAppRole()[APP_ROLE_USUARIO] ?? 0),
            'usuarios_nuevos'       => $users->countNewSince(30),
            'solicitudes_abiertas'  => (int) (($reqByStatus['RECIBIDA'] ?? 0)
                                             + ($reqByStatus['EN_REVISION'] ?? 0)
                                             + ($reqByStatus['EN_ATENCION'] ?? 0)
                                             + ($reqByStatus['INFORMACION_REQUERIDA'] ?? 0)),
            'solicitudes_sin_asignar' => $requests->countWithFilters(['unassigned' => true]),
            'solicitudes_cerradas'  => (int) ($reqByStatus['CERRADA'] ?? 0),
            'postulaciones_nuevas'  => (int) ($appByStatus['ENVIADA'] ?? 0),
            'postulaciones_revision'=> (int) ($appByStatus['EN_REVISION'] ?? 0),
            'postulaciones_pago'    => (int) ($appByStatus['PENDIENTE_PAGO'] ?? 0),
            'defensores_acreditados'=> (int) ($appByStatus['ACREDITADA'] ?? 0),
            'credenciales_vigentes' => $credentials->countActive(),
            'credenciales_por_vencer'=> $credentials->countExpiringSoon(30),
            'ordenes_pendientes'    => (int) ($ordByStatus['PENDIENTE'] ?? 0),
            'ordenes_vencidas'      => $orders->countExpired(),
            'cobrado_30d'           => $orders->totalCollected(30),
            'afiliados_activos'     => $affiliates->count("state = ?", ['ACTIVO']),
        ];

        $this->view('dashboard/index', [
            'title'             => 'Panel general',
            'stats'             => $stats,
            'reqByStatus'       => $reqByStatus,
            'appByStatus'       => $appByStatus,
            'monthlyCollected'  => $orders->monthlyCollected(12),
            'urgentRequests'    => $requests->listWithFilters(['urgency' => 'EMERGENCIA'], 5),
            'pendingApps'       => $applications->listWithFilters(['status' => 'ENVIADA'], 5),
            'expiringCreds'     => $credentials->listExpiringSoon(30, 6),
            'recentActivity'    => Database::getInstance()->fetchAll(
                "SELECT al.*, u.name AS user_name
                 FROM activity_log al
                 LEFT JOIN users u ON u.id = al.user_id
                 ORDER BY al.created_at DESC LIMIT 12"
            ),
        ]);
    }
}
