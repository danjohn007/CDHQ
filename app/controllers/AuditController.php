<?php
require_once CORE_PATH . '/Controller.php';

/**
 * Bitácora de auditoría. Sólo lectura: el plan del portal exige que el
 * historial no pueda editarse ni borrarse desde la interfaz.
 */
class AuditController extends Controller {

    private const PER_PAGE = 50;

    public function index(): void {
        Auth::requireAdmin();

        $q      = trim($_GET['q'] ?? '');
        $action = trim($_GET['action'] ?? '');
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $where  = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = "(al.details LIKE :q OR al.entity LIKE :q OR u.name LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if ($action !== '') {
            $where[] = "al.action = :action";
            $params[':action'] = $action;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $db    = Database::getInstance();
        $total = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM activity_log al LEFT JOIN users u ON u.id = al.user_id {$whereSql}",
            $params
        );
        $limit = self::PER_PAGE;

        $this->view('audit/index', [
            'title'   => 'Auditoría',
            'entries' => $db->fetchAll(
                "SELECT al.*, u.name AS user_name, u.email AS user_email
                 FROM activity_log al
                 LEFT JOIN users u ON u.id = al.user_id
                 {$whereSql}
                 ORDER BY al.created_at DESC
                 LIMIT {$limit} OFFSET {$offset}",
                $params
            ),
            'actions' => array_column(
                $db->fetchAll("SELECT DISTINCT action FROM activity_log ORDER BY action"),
                'action'
            ),
            'q'      => $q,
            'action' => $action,
            'page'   => $page,
            'total'  => $total,
            'pages'  => max(1, (int) ceil($total / self::PER_PAGE)),
        ]);
    }
}
