<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Event.php';
require_once MODELS_PATH . '/Affiliate.php';

class EventsController extends Controller {

    public function index(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_COORD_EVENTOS, ROLE_AFILIADO);
        $model  = new Event();
        $status = trim($_GET['status'] ?? '');
        $events = $model->listAll($status);
        $this->view('events/index', ['title' => 'Eventos', 'events' => $events, 'status' => $status]);
    }

    public function create(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS);
        $this->view('events/create', ['title' => 'Nuevo Evento']);
    }

    public function store(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS);
        $this->verifyCsrf();

        $model = new Event();
        $data  = [
            'name'        => trim($_POST['name']        ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'type'        => trim($_POST['type']        ?? 'PRESENCIAL'),
            'date'        => trim($_POST['date']        ?? ''),
            'end_date'    => trim($_POST['end_date']    ?? '') ?: null,
            'capacity'    => (int)($_POST['capacity']   ?? 0),
            'venue'       => trim($_POST['venue']       ?? ''),
            'status'      => 'PROGRAMADO',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if (!$data['name'] || !$data['date']) {
            $this->flash('error', 'Nombre y fecha son obligatorios.');
            $this->redirect('events/create');
        }

        $id = $model->create($data);
        Auth::logActivity('CREATE', 'events', $id, "Created event: {$data['name']}");
        $this->flash('success', 'Evento creado correctamente.');
        $this->redirect("events/{$id}");
    }

    public function show(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_COORD_EVENTOS, ROLE_AFILIADO);
        $model = new Event();
        $event = $model->getWithRegistrations((int)$id);
        if (!$event) { $this->flash('error', 'Evento no encontrado.'); $this->redirect('events'); }

        $affiliateModel = new Affiliate();
        $affiliates = $affiliateModel->listWithSearch('', 'ACTIVO', 200, 0);

        $this->view('events/show', [
            'title'      => $event['name'],
            'event'      => $event,
            'affiliates' => $affiliates,
        ]);
    }

    public function register(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS, ROLE_ADMIN_MEMBRESIAS);
        $this->verifyCsrf();

        $model       = new Event();
        $event       = $model->find((int)$id);
        $affiliateId = (int)($_POST['affiliate_id'] ?? 0);

        if (!$event || !$affiliateId) {
            $this->flash('error', 'Datos inválidos.');
            $this->redirect("events/{$id}");
        }

        $model->register((int)$id, $affiliateId);
        Auth::logActivity('REGISTER', 'events', (int)$id, "Registered affiliate {$affiliateId}");
        $this->flash('success', 'Asistente registrado.');
        $this->redirect("events/{$id}");
    }

    public function changeStatus(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS);
        $this->verifyCsrf();

        $status = trim($_POST['status'] ?? '');
        $valid  = ['PROGRAMADO','EN_CURSO','FINALIZADO','CANCELADO'];
        if (!in_array($status, $valid, true)) {
            $this->flash('error', 'Estado no válido.');
            $this->redirect("events/{$id}");
        }

        $model = new Event();
        $model->changeStatus((int)$id, $status);
        Auth::logActivity('STATUS', 'events', (int)$id, "Changed event status to {$status}");
        $this->flash('success', 'Estado del evento actualizado.');
        $this->redirect("events/{$id}");
    }
}
