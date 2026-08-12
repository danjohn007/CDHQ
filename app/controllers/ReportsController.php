<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Payment.php';
require_once MODELS_PATH . '/PaymentOrder.php';
require_once MODELS_PATH . '/SupportRequest.php';
require_once MODELS_PATH . '/DefenderApplication.php';
require_once MODELS_PATH . '/Credential.php';

/**
 * Reportes del portal.
 *
 * Antes tiraba de eventos y prospectos; esas tablas no existen en cdhq_mobile,
 * así que los reportes ahora se arman con el dominio real: solicitudes,
 * postulaciones, credenciales y pagos de Stripe.
 */
class ReportsController extends Controller {

    public function index(): void {
        Auth::requireAdmin();

        $dateFrom = trim($_GET['date_from'] ?? date('Y-01-01'));
        $dateTo   = trim($_GET['date_to']   ?? date('Y-m-d'));

        $affiliates   = new Affiliate();
        $payments     = new Payment();
        $orders       = new PaymentOrder();
        $requests     = new SupportRequest();
        $applications = new DefenderApplication();
        $credentials  = new Credential();

        $db = Database::getInstance();

        // Tiempos de atención de las solicitudes cerradas en el periodo
        $atencion = $db->fetchOne(
            "SELECT COUNT(*) AS cerradas,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) AS horas_promedio
             FROM support_requests
             WHERE status = 'CERRADA' AND closed_at BETWEEN ? AND ?",
            [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
        );

        // Carga de trabajo por asesor
        $cargaAsesores = $db->fetchAll(
            "SELECT u.name,
                    SUM(sr.status NOT IN ('CERRADA','CANCELADA')) AS abiertas,
                    SUM(sr.status = 'CERRADA')                    AS cerradas,
                    COUNT(*)                                      AS total
             FROM support_requests sr
             JOIN users u ON u.id = sr.assigned_to
             GROUP BY u.id, u.name
             ORDER BY abiertas DESC, total DESC"
        );

        // Distribución por urgencia
        $porUrgencia = $db->fetchAll(
            "SELECT urgency, COUNT(*) AS total
             FROM support_requests
             WHERE created_at BETWEEN ? AND ?
             GROUP BY urgency
             ORDER BY FIELD(urgency,'EMERGENCIA','ALTA','MEDIA','BAJA')",
            [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
        );

        // Distribución geográfica de las solicitudes
        $porMunicipio = $db->fetchAll(
            "SELECT COALESCE(NULLIF(municipio,''),'Sin especificar') AS municipio,
                    COUNT(*) AS total
             FROM support_requests
             GROUP BY municipio ORDER BY total DESC LIMIT 12"
        );

        // Tasa de acreditación de postulaciones
        $appStats  = $applications->countsByStatus();
        $appTotal  = array_sum($appStats);
        $acreditadas = (int) ($appStats['ACREDITADA'] ?? 0);

        $this->view('reports/index', [
            'title'             => 'Reportes',
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
            'affiliatesByState' => $affiliates->statsByState(),
            'incomeByType'      => $payments->sumByType($dateFrom, $dateTo),
            'monthlyCollected'  => $orders->monthlyCollected(12),
            'ordersByStatus'    => $orders->countsByStatus(),
            'requestsByStatus'  => $requests->countsByStatus(),
            'appsByStatus'      => $appStats,
            'tasaAcreditacion'  => $appTotal > 0 ? round($acreditadas / $appTotal * 100, 1) : 0.0,
            'atencion'          => $atencion,
            'cargaAsesores'     => $cargaAsesores,
            'porUrgencia'       => $porUrgencia,
            'porMunicipio'      => $porMunicipio,
            'credencialesVigentes' => $credentials->countActive(),
            'credencialesPorVencer' => $credentials->countExpiringSoon(30),
        ]);
    }
}
