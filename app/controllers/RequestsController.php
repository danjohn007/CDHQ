<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/SupportRequest.php';
require_once MODELS_PATH . '/User.php';

/**
 * Solicitudes de apoyo levantadas desde la aplicación móvil.
 */
class RequestsController extends Controller {

    private const PER_PAGE = 25;

    public function index(): void {
        Auth::requireAdmin();

        $filters = [
            'q'          => trim($_GET['q'] ?? ''),
            'status'     => $_GET['status']  ?? '',
            'urgency'    => $_GET['urgency'] ?? '',
            'unassigned' => !empty($_GET['unassigned']),
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $model = new SupportRequest();
        $total = $model->countWithFilters($filters);

        $this->view('requests/index', [
            'title'    => 'Solicitudes de apoyo',
            'requests' => $model->listWithFilters($filters, self::PER_PAGE, $offset),
            'filters'  => $filters,
            'page'     => $page,
            'total'    => $total,
            'pages'    => max(1, (int) ceil($total / self::PER_PAGE)),
            'counts'   => $model->countsByStatus(),
        ]);
    }

    public function show(string $id): void {
        Auth::requireAdmin();
        $model   = new SupportRequest();
        $request = $model->findDetailed((int) $id);
        if (!$request) {
            $this->flash('error', 'Solicitud no encontrada.');
            $this->redirect('requests');
        }

        Auth::logActivity('CONSULTA_SOLICITUD', 'support_requests', (int) $id,
            'Consulta del expediente ' . $request['folio']);

        $this->view('requests/show', [
            'title'      => 'Solicitud ' . $request['folio'],
            'request'    => $request,
            'updates'    => $model->getUpdates((int) $id),
            'advisors'   => (new User())->listAdvisors(),
            'allowed'    => SupportRequest::TRANSICIONES[$request['status']] ?? [],
        ]);
    }

    /** Asignar o reasignar asesor */
    public function assign(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model   = new SupportRequest();
        $request = $model->findDetailed((int) $id);
        if (!$request) {
            $this->flash('error', 'Solicitud no encontrada.');
            $this->redirect('requests');
        }

        $asesorId = (int) ($_POST['assigned_to'] ?? 0);
        $motivo   = trim($_POST['reason'] ?? '');
        if ($motivo === '') {
            $this->flash('error', 'Indica el motivo de la asignación.');
            $this->redirect("requests/{$id}");
        }

        $model->assign((int) $id, $asesorId ?: null);
        $nombre = $asesorId
            ? ((new User())->find($asesorId)['name'] ?? 'asesor')
            : 'sin asignar';
        $model->addUpdate((int) $id, (int) Auth::currentUser()['id'], 'NOTA_INTERNA',
            "Asignación cambiada a: {$nombre}. Motivo: {$motivo}", null, null, false);

        Auth::logActivity('ASIGNAR_SOLICITUD', 'support_requests', (int) $id,
            "Asignada a {$nombre}");
        $this->flash('success', 'Asignación actualizada.');
        $this->redirect("requests/{$id}");
    }

    /** Cambiar el estado respetando las transiciones válidas */
    public function changeStatus(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model   = new SupportRequest();
        $request = $model->findDetailed((int) $id);
        if (!$request) {
            $this->flash('error', 'Solicitud no encontrada.');
            $this->redirect('requests');
        }

        $nuevo    = $_POST['status'] ?? '';
        $mensaje  = trim($_POST['message'] ?? '');
        $anterior = $request['status'];

        // El servidor revalida la transición aunque la interfaz ya la haya
        // filtrado. Se responde con redirect + aviso porque quien envía es un
        // formulario del navegador; el rechazo queda además en la bitácora.
        if (!SupportRequest::puedeTransicionar($anterior, $nuevo)) {
            Auth::logActivity('TRANSICION_RECHAZADA', 'support_requests', (int) $id,
                "Intento inválido {$anterior} → {$nuevo}");
            $this->flash('error', "No se permite pasar de {$anterior} a {$nuevo}.");
            $this->redirect("requests/{$id}");
        }
        if ($nuevo === 'CERRADA' && $mensaje === '') {
            $this->flash('error', 'Para cerrar una solicitud debes escribir la conclusión.');
            $this->redirect("requests/{$id}");
        }

        $model->changeStatus((int) $id, $nuevo);
        $model->addUpdate((int) $id, (int) Auth::currentUser()['id'], 'CAMBIO_ESTADO',
            $mensaje !== '' ? $mensaje : "Estado actualizado a {$nuevo}.", $anterior, $nuevo, true);

        Auth::logActivity('CAMBIO_ESTADO_SOLICITUD', 'support_requests', (int) $id,
            "{$anterior} → {$nuevo}");
        $this->flash('success', 'Estado actualizado.');
        $this->redirect("requests/{$id}");
    }

    /** Comentario visible para la persona usuaria o nota interna */
    public function addNote(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new SupportRequest();
        if (!$model->find((int) $id)) {
            $this->flash('error', 'Solicitud no encontrada.');
            $this->redirect('requests');
        }

        $mensaje = trim($_POST['message'] ?? '');
        if ($mensaje === '') {
            $this->flash('error', 'El mensaje no puede ir vacío.');
            $this->redirect("requests/{$id}");
        }
        $interna = !empty($_POST['internal']);

        $model->addUpdate((int) $id, (int) Auth::currentUser()['id'],
            $interna ? 'NOTA_INTERNA' : 'MENSAJE', $mensaje, null, null, !$interna);

        Auth::logActivity('NOTA_SOLICITUD', 'support_requests', (int) $id,
            $interna ? 'Nota interna' : 'Mensaje al usuario');
        $this->flash('success', $interna ? 'Nota interna registrada.' : 'Mensaje enviado.');
        $this->redirect("requests/{$id}");
    }
}
