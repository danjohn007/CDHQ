<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Payment.php';
require_once MODELS_PATH . '/Event.php';
require_once MODELS_PATH . '/Prospect.php';

class ReportsController extends Controller {

    public function index(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_ADMIN_FINANCIERO);

        $affiliateModel = new Affiliate();
        $paymentModel   = new Payment();
        $eventModel     = new Event();
        $prospectModel  = new Prospect();

        $dateFrom = trim($_GET['date_from'] ?? date('Y-01-01'));
        $dateTo   = trim($_GET['date_to']   ?? date('Y-m-d'));

        $affiliatesByState  = $affiliateModel->statsByState();
        $incomeByType       = $paymentModel->sumByType($dateFrom, $dateTo);
        $monthlyIncome      = $paymentModel->monthlyIncome(12);
        $eventStats         = $eventModel->participationStats();
        $prospectStats      = $prospectModel->conversionStats();

        $this->view('reports/index', [
            'title'             => 'Reportes',
            'affiliatesByState' => $affiliatesByState,
            'incomeByType'      => $incomeByType,
            'monthlyIncome'     => $monthlyIncome,
            'eventStats'        => $eventStats,
            'prospectStats'     => $prospectStats,
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
        ]);
    }
}
