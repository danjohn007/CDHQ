<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Subscription.php';
require_once MODELS_PATH . '/Payment.php';
require_once MODELS_PATH . '/Event.php';
require_once MODELS_PATH . '/Prospect.php';

class DashboardController extends Controller {

    public function index(): void {
        Auth::requireAuth();

        $affiliateModel    = new Affiliate();
        $subscriptionModel = new Subscription();
        $paymentModel      = new Payment();
        $eventModel        = new Event();
        $prospectModel     = new Prospect();

        // Run subscription expiry check
        $subscriptionModel->checkExpired();

        $stats = [
            'total_affiliates'    => $affiliateModel->count(),
            'active_affiliates'   => $affiliateModel->count('state = ?', ['ACTIVO']),
            'active_subscriptions'=> $subscriptionModel->countActive(),
            'upcoming_events'     => $eventModel->countUpcoming(),
            'pending_payments'    => $paymentModel->countPending(),
            'new_prospects'       => $prospectModel->count('state = ?', ['NUEVO']),
        ];

        $affiliatesByState = $affiliateModel->statsByState();
        $monthlyIncome     = $paymentModel->monthlyIncome(12);
        $upcomingEvents    = $eventModel->listUpcoming(5);

        // Recent activity log
        $recentActivity = Database::getInstance()->fetchAll(
            "SELECT al.*, u.name AS user_name
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC LIMIT 10"
        );

        $this->view('dashboard/index', [
            'title'             => 'Panel Principal',
            'stats'             => $stats,
            'affiliatesByState' => $affiliatesByState,
            'monthlyIncome'     => $monthlyIncome,
            'upcomingEvents'    => $upcomingEvents,
            'recentActivity'    => $recentActivity,
        ]);
    }
}
