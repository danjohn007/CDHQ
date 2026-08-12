<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Payment.php';
require_once MODELS_PATH . '/Affiliate.php';

class FinancialController extends Controller {

    public function index(): void {
        Auth::requireAdmin();
        $model   = new Payment();
        $filters = [
            'type'      => trim($_GET['type']      ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to']   ?? ''),
        ];
        $payments  = $model->listAll($filters);
        $summary   = $model->sumByType($filters['date_from'], $filters['date_to']);
        $monthly   = $model->monthlyIncome(12);

        $this->view('financial/index', [
            'title'    => 'Finanzas',
            'payments' => $payments,
            'summary'  => $summary,
            'monthly'  => $monthly,
            'filters'  => $filters,
        ]);
    }

    public function create(): void {
        Auth::requireAdmin();
        $affiliateModel = new Affiliate();
        $affiliates = $affiliateModel->listWithSearch('', '', 200, 0);
        $this->view('financial/create', [
            'title'      => 'Registrar Pago',
            'affiliates' => $affiliates,
        ]);
    }

    public function store(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new Payment();
        $data  = [
            'affiliate_id'  => (int)($_POST['affiliate_id'] ?? 0) ?: null,
            'amount'        => (float)($_POST['amount']       ?? 0),
            'type'          => trim($_POST['type']            ?? ''),
            'method'        => trim($_POST['method']          ?? ''),
            'reference'     => trim($_POST['reference']       ?? ''),
            'payment_date'  => trim($_POST['payment_date']    ?? date('Y-m-d')),
            'notes'         => trim($_POST['notes']           ?? ''),
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        if ($data['amount'] <= 0 || !$data['type']) {
            $this->flash('error', 'Monto y tipo son obligatorios.');
            $this->redirect('financial/create');
        }

        $id = $model->create($data);
        Auth::logActivity('CREATE', 'payments', $id, "Recorded payment: {$data['type']} \${$data['amount']}");
        $this->flash('success', 'Pago registrado correctamente.');
        $this->redirect('financial');
    }
}
