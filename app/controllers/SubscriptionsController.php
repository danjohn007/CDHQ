<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Subscription.php';
require_once MODELS_PATH . '/Affiliate.php';

class SubscriptionsController extends Controller {

    public function index(): void {
        Auth::requireAdmin();
        $model = new Subscription();
        $subs  = $model->listAll(50, 0);
        $plans = $model->getPlans();
        $this->view('subscriptions/index', [
            'title' => 'Membresías',
            'subs'  => $subs,
            'plans' => $plans,
        ]);
    }

    public function create(): void {
        Auth::requireAdmin();
        $model = new Subscription();
        $affiliateModel = new Affiliate();
        $plans = $model->getPlans();
        $affiliates = $affiliateModel->listWithSearch('', '', 200, 0);
        $this->view('subscriptions/create', [
            'title'      => 'Nueva Membresía',
            'plans'      => $plans,
            'affiliates' => $affiliates,
        ]);
    }

    public function store(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $affiliateId = (int)($_POST['affiliate_id'] ?? 0);
        $planId      = (int)($_POST['plan_id']      ?? 0);
        $startDate   = trim($_POST['start_date']    ?? date('Y-m-d'));

        if (!$affiliateId || !$planId) {
            $this->flash('error', 'Afiliado y plan son obligatorios.');
            $this->redirect('subscriptions/create');
        }

        $model = new Subscription();
        $id    = $model->createSubscription($affiliateId, $planId, $startDate);

        // Record payment if amount provided
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount > 0) {
            require_once MODELS_PATH . '/Payment.php';
            $payModel = new Payment();
            $payModel->create([
                'affiliate_id'  => $affiliateId,
                'amount'        => $amount,
                'type'          => 'MEMBRESIA',
                'method'        => trim($_POST['method'] ?? 'EFECTIVO'),
                'reference'     => trim($_POST['reference'] ?? ''),
                'payment_date'  => $startDate,
                'notes'         => "Pago membresía sub #{$id}",
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        Auth::logActivity('CREATE', 'subscriptions', $id, "Created subscription ID {$id}");
        $this->flash('success', 'Membresía registrada correctamente.');
        $this->redirect('subscriptions');
    }
}
