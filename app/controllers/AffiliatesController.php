<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Affiliate.php';
require_once MODELS_PATH . '/Subscription.php';
require_once MODELS_PATH . '/Payment.php';

class AffiliatesController extends Controller {

    public function index(): void {
        Auth::requireAdmin();
        $model  = new Affiliate();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $affiliates = $model->listWithSearch($search, $status, $limit, $offset);
        $total      = $model->countFiltered($search, $status);
        $pages      = ceil($total / $limit);

        $this->view('affiliates/index', [
            'title'      => 'Afiliados',
            'affiliates' => $affiliates,
            'search'     => $search,
            'status'     => $status,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
        ]);
    }

    public function create(): void {
        Auth::requireAdmin();
        $this->view('affiliates/create', ['title' => 'Nuevo Afiliado']);
    }

    public function store(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model  = new Affiliate();
        $number = $model->generateNumber();

        // Ojo: en cdhq_mobile el token del QR NO vive en `affiliates`, sino en
        // `credentials.verification_token`, y se genera al emitir la credencial.
        $data = [
            'affiliate_number' => $number,
            'first_name'  => trim($_POST['first_name']  ?? ''),
            'last_name'   => trim($_POST['last_name']   ?? ''),
            'email'       => trim($_POST['email']       ?? ''),
            'phone'       => trim($_POST['phone']       ?? ''),
            'address'     => trim($_POST['address']     ?? ''),
            'municipio'   => trim($_POST['municipio']   ?? ''),
            'estado_mexico'=> trim($_POST['estado_mexico'] ?? ''),
            'state'       => 'PROSPECTO',
            'notes'       => trim($_POST['notes']       ?? ''),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if (!$data['first_name'] || !$data['last_name']) {
            $this->flash('error', 'Nombre y apellido son obligatorios.');
            $this->redirect('affiliates/create');
        }

        $id = $model->create($data);
        $model->logHistory($id, 'state', '', 'PROSPECTO', 'Registro inicial');
        Auth::logActivity('CREATE', 'affiliates', $id, "Created affiliate: {$number}");
        $this->flash('success', "Afiliado {$number} creado correctamente.");
        $this->redirect("affiliates/{$id}");
    }

    public function show(string $id): void {
        Auth::requireAdmin();
        $model    = new Affiliate();
        $affiliate = $model->findWithDetails((int)$id);
        if (!$affiliate) { $this->flash('error', 'Afiliado no encontrado.'); $this->redirect('affiliates'); }

        $subModel  = new Subscription();
        $payModel  = new Payment();
        $history   = $model->getHistory((int)$id);
        $subs      = $subModel->getAffiliateSubscriptions((int)$id);
        $payments  = $payModel->getAffiliatePayments((int)$id);
        $activeSub = $subModel->getActiveSubscription((int)$id);

        $this->view('affiliates/show', [
            'title'     => 'Perfil de Afiliado',
            'affiliate' => $affiliate,
            'history'   => $history,
            'subs'      => $subs,
            'payments'  => $payments,
            'activeSub' => $activeSub,
        ]);
    }

    public function edit(string $id): void {
        Auth::requireAdmin();
        $model    = new Affiliate();
        $affiliate = $model->find((int)$id);
        if (!$affiliate) { $this->flash('error', 'Afiliado no encontrado.'); $this->redirect('affiliates'); }
        $this->view('affiliates/edit', ['title' => 'Editar Afiliado', 'affiliate' => $affiliate]);
    }

    public function update(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();
        $model    = new Affiliate();
        $old      = $model->find((int)$id);
        if (!$old) { $this->flash('error', 'Afiliado no encontrado.'); $this->redirect('affiliates'); }

        $data = [
            'first_name'   => trim($_POST['first_name']    ?? ''),
            'last_name'    => trim($_POST['last_name']     ?? ''),
            'email'        => trim($_POST['email']         ?? ''),
            'phone'        => trim($_POST['phone']         ?? ''),
            'address'      => trim($_POST['address']       ?? ''),
            'municipio'    => trim($_POST['municipio']     ?? ''),
            'estado_mexico'=> trim($_POST['estado_mexico'] ?? ''),
            'notes'        => trim($_POST['notes']         ?? ''),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $model->update((int)$id, $data);
        Auth::logActivity('UPDATE', 'affiliates', (int)$id, "Updated affiliate ID {$id}");
        $this->flash('success', 'Datos del afiliado actualizados.');
        $this->redirect("affiliates/{$id}");
    }

    public function changeStatus(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();
        $model    = new Affiliate();
        $old      = $model->find((int)$id);
        if (!$old) { $this->flash('error', 'Afiliado no encontrado.'); $this->redirect('affiliates'); }

        $newState = trim($_POST['state']  ?? '');
        $reason   = trim($_POST['reason'] ?? '');
        $validStates = ['PROSPECTO','ACTIVO','SUSPENDIDO','VENCIDO'];

        if (!in_array($newState, $validStates, true)) {
            $this->flash('error', 'Estado no válido.');
            $this->redirect("affiliates/{$id}");
        }

        $model->update((int)$id, ['state' => $newState, 'updated_at' => date('Y-m-d H:i:s')]);
        $model->logHistory((int)$id, 'state', $old['state'], $newState, $reason);
        Auth::logActivity('STATUS', 'affiliates', (int)$id, "Status changed to {$newState}");
        $this->flash('success', 'Estado actualizado.');
        $this->redirect("affiliates/{$id}");
    }
}
