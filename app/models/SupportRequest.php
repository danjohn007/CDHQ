<?php
require_once CORE_PATH . '/Model.php';

/**
 * Solicitudes de apoyo levantadas desde la aplicación móvil.
 * Tabla `support_requests` de cdhq_mobile.
 */
class SupportRequest extends Model {
    protected string $table = 'support_requests';

    public const ESTADOS = [
        'RECIBIDA', 'EN_REVISION', 'INFORMACION_REQUERIDA',
        'EN_ATENCION', 'CANALIZADA', 'CERRADA', 'CANCELADA',
    ];

    public const URGENCIAS = ['BAJA', 'MEDIA', 'ALTA', 'EMERGENCIA'];

    /**
     * Transiciones válidas. El portal sólo ofrece las permitidas y el servidor
     * vuelve a validarlas: la interfaz no es la fuente de verdad.
     */
    public const TRANSICIONES = [
        'RECIBIDA'              => ['EN_REVISION', 'CANCELADA'],
        'EN_REVISION'           => ['EN_ATENCION', 'INFORMACION_REQUERIDA', 'CANALIZADA', 'CANCELADA'],
        'INFORMACION_REQUERIDA' => ['EN_REVISION', 'EN_ATENCION', 'CANCELADA'],
        'EN_ATENCION'           => ['CANALIZADA', 'CERRADA', 'INFORMACION_REQUERIDA'],
        'CANALIZADA'            => ['CERRADA'],
        'CERRADA'               => [],
        'CANCELADA'             => [],
    ];

    public static function puedeTransicionar(string $desde, string $hacia): bool {
        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    /** Listado con filtros, ordenando primero lo urgente y sin asignar */
    public function listWithFilters(array $f = [], int $limit = 40, int $offset = 0): array {
        [$where, $params] = $this->buildWhere($f);
        return $this->query(
            "SELECT sr.*, u.name AS user_name, u.email AS user_email,
                    asesor.name AS asesor_name
             FROM support_requests sr
             LEFT JOIN users u      ON u.id = sr.user_id
             LEFT JOIN users asesor ON asesor.id = sr.assigned_to
             {$where}
             ORDER BY FIELD(sr.urgency,'EMERGENCIA','ALTA','MEDIA','BAJA'),
                      (sr.assigned_to IS NULL) DESC,
                      sr.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countWithFilters(array $f = []): int {
        [$where, $params] = $this->buildWhere($f);
        return (int) $this->queryColumn("SELECT COUNT(*) FROM support_requests sr {$where}", $params);
    }

    private function buildWhere(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = "(sr.folio LIKE :q OR sr.subject LIKE :q OR sr.description LIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status'])) {
            $where[] = "sr.status = :status";
            $params[':status'] = $f['status'];
        }
        if (!empty($f['urgency'])) {
            $where[] = "sr.urgency = :urgency";
            $params[':urgency'] = $f['urgency'];
        }
        if (!empty($f['unassigned'])) {
            $where[] = "sr.assigned_to IS NULL";
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    public function findDetailed(int $id): ?array {
        return $this->queryOne(
            "SELECT sr.*, u.name AS user_name, u.email AS user_email,
                    asesor.name AS asesor_name
             FROM support_requests sr
             LEFT JOIN users u      ON u.id = sr.user_id
             LEFT JOIN users asesor ON asesor.id = sr.assigned_to
             WHERE sr.id = ?",
            [$id]
        );
    }

    /** Historial visible + notas internas */
    public function getUpdates(int $requestId): array {
        return $this->query(
            "SELECT sru.*, u.name AS author_name
             FROM support_request_updates sru
             LEFT JOIN users u ON u.id = sru.author_id
             WHERE sru.support_request_id = ?
             ORDER BY sru.created_at DESC",
            [$requestId]
        );
    }

    public function addUpdate(int $requestId, ?int $authorId, string $type, string $message,
                              ?string $prev = null, ?string $new = null, bool $visible = true): void {
        $this->exec(
            "INSERT INTO support_request_updates
                (support_request_id, author_id, update_type, message, previous_status, new_status, visible_to_user, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$requestId, $authorId, $type, $message, $prev, $new, $visible ? 1 : 0]
        );
    }

    public function changeStatus(int $id, string $newStatus): void {
        $closed = ($newStatus === 'CERRADA') ? date('Y-m-d H:i:s') : null;
        $this->exec(
            "UPDATE support_requests SET status = ?, closed_at = ?, updated_at = NOW() WHERE id = ?",
            [$newStatus, $closed, $id]
        );
    }

    public function assign(int $id, ?int $asesorId): void {
        $this->exec(
            "UPDATE support_requests SET assigned_to = ?, updated_at = NOW() WHERE id = ?",
            [$asesorId, $id]
        );
    }

    /** Conteos por estado para el panel */
    public function countsByStatus(): array {
        $rows = $this->query("SELECT status, COUNT(*) AS total FROM support_requests GROUP BY status");
        return array_column($rows, 'total', 'status');
    }
}
