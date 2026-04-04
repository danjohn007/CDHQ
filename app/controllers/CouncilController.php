<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Council.php';
require_once MODELS_PATH . '/Affiliate.php';

class CouncilController extends Controller {

    public function index(): void {
        Auth::requireAuth();
        $model    = new Council();
        $orgChart = $model->getPositionsWithAssignments();
        $this->view('council/index', ['title' => 'Estructura del Consejo', 'orgChart' => $orgChart]);
    }

    public function positions(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS);
        $model     = new Council();
        $positions = $model->all('level, title');
        $this->view('council/positions', ['title' => 'Cargos del Consejo', 'positions' => $positions]);
    }

    public function createPosition(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->verifyCsrf();
        $model = new Council();
        $data  = [
            'title'       => trim($_POST['title']       ?? ''),
            'level'       => (int)($_POST['level']      ?? 1),
            'is_unique'   => isset($_POST['is_unique']) ? 1 : 0,
            'is_active'   => 1,
            'description' => trim($_POST['description'] ?? ''),
        ];
        if (!$data['title']) {
            $this->flash('error', 'El título es obligatorio.');
            $this->redirect('council/positions');
        }
        $model->create($data);
        $this->flash('success', 'Cargo creado correctamente.');
        $this->redirect('council/positions');
    }

    public function assignments(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS);
        $model        = new Council();
        $affiliateModel = new Affiliate();
        $assignments  = $model->getAllAssignments();
        $positions    = $model->all('level, title');
        $affiliates   = $affiliateModel->listWithSearch('', 'ACTIVO', 200, 0);
        $this->view('council/assignments', [
            'title'       => 'Asignaciones al Consejo',
            'assignments' => $assignments,
            'positions'   => $positions,
            'affiliates'  => $affiliates,
        ]);
    }

    public function createAssignment(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS);
        $this->verifyCsrf();
        $positionId  = (int)($_POST['position_id']  ?? 0);
        $affiliateId = (int)($_POST['affiliate_id'] ?? 0);
        $startDate   = trim($_POST['start_date']    ?? date('Y-m-d'));

        if (!$positionId || !$affiliateId) {
            $this->flash('error', 'Cargo y afiliado son obligatorios.');
            $this->redirect('council/assignments');
        }
        $model = new Council();
        $model->createAssignment($positionId, $affiliateId, $startDate);
        Auth::logActivity('CREATE', 'council_assignments', 0, "Assigned affiliate {$affiliateId} to position {$positionId}");
        $this->flash('success', 'Asignación creada correctamente.');
        $this->redirect('council/assignments');
    }
}
