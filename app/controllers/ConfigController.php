<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/Config.php';

class ConfigController extends Controller {

    public function index(): void {
        Auth::requireAdmin();
        $model  = new Config();
        $config = $model->getAll();

        // Activity log
        $activityLog = Database::getInstance()->fetchAll(
            "SELECT al.*, u.name AS user_name
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC LIMIT 50"
        );

        $this->view('config/index', [
            'title'       => 'Configuración',
            'config'      => $config,
            'activityLog' => $activityLog,
        ]);
    }

    public function update(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model  = new Config();
        $data   = $_POST;
        unset($data[CSRF_TOKEN_NAME]);

        $model->setMultiple($data);
        Auth::logActivity('UPDATE', 'configurations', 0, 'Updated system configuration');
        $this->flash('success', 'Configuración guardada correctamente.');
        $this->redirect('config');
    }
}
