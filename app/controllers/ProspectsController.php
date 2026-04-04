<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Prospect.php';

class ProspectsController extends Controller {

    public function index(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS);
        $model  = new Prospect();
        $state  = trim($_GET['state']  ?? '');
        $search = trim($_GET['search'] ?? '');
        $prospects = $model->listAll($state, $search);
        $this->view('prospects/index', [
            'title'     => 'Prospectos',
            'prospects' => $prospects,
            'state'     => $state,
            'search'    => $search,
        ]);
    }

    public function create(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS);
        $this->view('prospects/create', ['title' => 'Nuevo Prospecto']);
    }

    public function store(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS);
        $this->verifyCsrf();

        $model = new Prospect();
        $data  = [
            'name'        => trim($_POST['name']   ?? ''),
            'email'       => trim($_POST['email']  ?? ''),
            'phone'       => trim($_POST['phone']  ?? ''),
            'source'      => trim($_POST['source'] ?? ''),
            'notes'       => trim($_POST['notes']  ?? ''),
            'state'       => 'NUEVO',
            'assigned_to' => (int)($_POST['assigned_to'] ?? 0) ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if (!$data['name']) {
            $this->flash('error', 'El nombre es obligatorio.');
            $this->redirect('prospects/create');
        }

        $id = $model->create($data);
        Auth::logActivity('CREATE', 'prospects', $id, "Created prospect: {$data['name']}");
        $this->flash('success', 'Prospecto registrado correctamente.');
        $this->redirect('prospects');
    }

    public function edit(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS);
        $model    = new Prospect();
        $prospect = $model->find((int)$id);
        if (!$prospect) { $this->flash('error', 'Prospecto no encontrado.'); $this->redirect('prospects'); }
        $notes    = $model->getNotes((int)$id);
        $this->view('prospects/edit', [
            'title'    => 'Editar Prospecto',
            'prospect' => $prospect,
            'notes'    => $notes,
        ]);
    }

    public function update(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS);
        $this->verifyCsrf();
        $model = new Prospect();
        $data  = [
            'name'       => trim($_POST['name']   ?? ''),
            'email'      => trim($_POST['email']  ?? ''),
            'phone'      => trim($_POST['phone']  ?? ''),
            'source'     => trim($_POST['source'] ?? ''),
            'notes'      => trim($_POST['notes']  ?? ''),
            'state'      => trim($_POST['state']  ?? 'NUEVO'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $model->update((int)$id, $data);
        Auth::logActivity('UPDATE', 'prospects', (int)$id, "Updated prospect ID {$id}");
        $this->flash('success', 'Prospecto actualizado.');
        $this->redirect("prospects/{$id}/edit");
    }

    public function addNote(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS);
        $this->verifyCsrf();
        $note = trim($_POST['note'] ?? '');
        if ($note) {
            $model = new Prospect();
            $model->addNote((int)$id, $note);
            $this->flash('success', 'Nota agregada.');
        }
        $this->redirect("prospects/{$id}/edit");
    }

    public function convert(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS);
        $this->verifyCsrf();
        $model       = new Prospect();
        $affiliateId = $model->convertToAffiliate((int)$id);
        if (!$affiliateId) {
            $this->flash('error', 'No se pudo convertir el prospecto.');
            $this->redirect('prospects');
        }
        Auth::logActivity('CONVERT', 'prospects', (int)$id, "Converted to affiliate ID {$affiliateId}");
        $this->flash('success', 'Prospecto convertido a afiliado exitosamente.');
        $this->redirect("affiliates/{$affiliateId}");
    }
}
