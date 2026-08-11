<?php
require_once CORE_PATH . '/Model.php';

/**
 * Postulaciones para defensor de derechos humanos.
 * Tabla `defender_applications` de cdhq_mobile.
 */
class DefenderApplication extends Model {
    protected string $table = 'defender_applications';

    public const ESTADOS = [
        'BORRADOR', 'ENVIADA', 'EN_REVISION', 'CON_OBSERVACIONES',
        'APROBADA', 'RECHAZADA', 'PENDIENTE_PAGO', 'ACREDITADA', 'CANCELADA',
    ];

    /**
     * Transiciones permitidas, según el plan del portal administrativo.
     *
     * Reglas que esto hace cumplir:
     *  · ACREDITADA es terminal: no puede rechazarse desde el portal.
     *  · PENDIENTE_PAGO no regresa a revisión; sólo avanza por webhook de Stripe.
     *  · RECHAZADA y CANCELADA son finales.
     */
    public const TRANSICIONES = [
        'BORRADOR'          => [],
        'ENVIADA'           => ['EN_REVISION', 'RECHAZADA'],
        'EN_REVISION'       => ['CON_OBSERVACIONES', 'APROBADA', 'RECHAZADA'],
        'CON_OBSERVACIONES' => ['EN_REVISION', 'RECHAZADA'],
        'APROBADA'          => ['PENDIENTE_PAGO'],
        'PENDIENTE_PAGO'    => [],
        'ACREDITADA'        => [],
        'RECHAZADA'         => [],
        'CANCELADA'         => [],
    ];

    public static function puedeTransicionar(string $desde, string $hacia): bool {
        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    public function listWithFilters(array $f = [], int $limit = 40, int $offset = 0): array {
        [$where, $params] = $this->buildWhere($f);
        return $this->query(
            "SELECT da.*, u.name AS user_name, u.email AS user_email,
                    rev.name AS reviewer_name, a.affiliate_number,
                    (SELECT COUNT(*) FROM application_documents ad WHERE ad.application_id = da.id) AS doc_count
             FROM defender_applications da
             LEFT JOIN users u   ON u.id = da.user_id
             LEFT JOIN users rev ON rev.id = da.reviewed_by
             LEFT JOIN affiliates a ON a.id = da.affiliate_id
             {$where}
             ORDER BY FIELD(da.status,'ENVIADA','EN_REVISION','CON_OBSERVACIONES','APROBADA','PENDIENTE_PAGO','ACREDITADA','RECHAZADA','CANCELADA','BORRADOR'),
                      da.submitted_at DESC, da.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countWithFilters(array $f = []): int {
        [$where, $params] = $this->buildWhere($f);
        return (int) $this->queryColumn("SELECT COUNT(*) FROM defender_applications da {$where}", $params);
    }

    private function buildWhere(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = "da.id IN (SELECT d2.id FROM defender_applications d2
                                  LEFT JOIN users u2 ON u2.id = d2.user_id
                                  WHERE u2.name LIKE :q OR u2.email LIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status'])) {
            $where[] = "da.status = :status";
            $params[':status'] = $f['status'];
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    public function findDetailed(int $id): ?array {
        return $this->queryOne(
            "SELECT da.*, u.name AS user_name, u.email AS user_email,
                    rev.name AS reviewer_name,
                    a.affiliate_number, a.state AS affiliate_state
             FROM defender_applications da
             LEFT JOIN users u   ON u.id = da.user_id
             LEFT JOIN users rev ON rev.id = da.reviewed_by
             LEFT JOIN affiliates a ON a.id = da.affiliate_id
             WHERE da.id = ?",
            [$id]
        );
    }

    public function getDocuments(int $applicationId): array {
        return $this->query(
            "SELECT * FROM application_documents WHERE application_id = ? ORDER BY created_at",
            [$applicationId]
        );
    }

    public function changeStatus(int $id, string $newStatus, int $reviewerId, string $notes = ''): void {
        $this->exec(
            "UPDATE defender_applications
             SET status = ?, reviewed_by = ?, reviewed_at = NOW(),
                 review_notes = CASE WHEN ? = '' THEN review_notes ELSE ? END,
                 updated_at = NOW()
             WHERE id = ?",
            [$newStatus, $reviewerId, $notes, $notes, $id]
        );
    }

    public function countsByStatus(): array {
        $rows = $this->query("SELECT status, COUNT(*) AS total FROM defender_applications GROUP BY status");
        return array_column($rows, 'total', 'status');
    }
}
