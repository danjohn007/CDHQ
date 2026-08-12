<?php
require_once CORE_PATH . '/Model.php';

class Affiliate extends Model {
    protected string $table = 'affiliates';

    /**
     * Find the affiliate record linked to a system user.
     * Public wrapper: the base helpers are protected and cannot be reached
     * from a controller.
     */
    public function findByUserId(int $userId): ?array {
        return $this->queryOne("SELECT * FROM affiliates WHERE user_id = ?", [$userId]);
    }

    /*
     * Aquí vivían findByVerificationToken(), findByNumberAndLastName() y
     * ensureVerificationToken() cuando el token del QR estaba en la columna
     * `affiliates.verification_token`.
     *
     * En cdhq_mobile ese token vive en `credentials.verification_token` (64
     * hex) y cada renovación emite una credencial nueva conservando la
     * anterior. Esa lógica se mudó al modelo Credential: no la dupliques aquí.
     */

    /**
     * Folio del afiliado.
     *
     * Debe coincidir EXACTAMENTE con el formato que genera la app móvil en
     * api-mobile/api/membership_service.php:
     *     sprintf('CDHQ-%s-%06d', date('y'), $userId)
     * Si el portal usara otro formato, el mismo padrón tendría dos
     * numeraciones distintas conviviendo.
     */
    public function generateNumberForUser(int $userId): string {
        return sprintf('CDHQ-%s-%06d', date('y'), $userId);
    }

    /** Folio para altas manuales sin usuario de la app asociado */
    public function generateNumber(): string {
        $yy   = date('y');
        $next = (int) $this->queryColumn(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(affiliate_number,'-',-1) AS UNSIGNED)),0) + 1
             FROM affiliates WHERE affiliate_number LIKE ?",
            ["CDHQ-{$yy}-%"]
        );
        return sprintf('CDHQ-%s-%06d', $yy, max($next, 900001));
    }

    public function listWithSearch(string $search = '', string $status = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT a.*, u.name AS user_name,
                       (SELECT s.end_date FROM subscriptions s WHERE s.affiliate_id = a.id AND s.status = 'ACTIVA' ORDER BY s.end_date DESC LIMIT 1) AS sub_end
                FROM affiliates a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (a.first_name LIKE :s OR a.last_name LIKE :s OR a.email LIKE :s OR a.affiliate_number LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        if ($status) {
            $sql .= " AND a.state = :st";
            $params[':st'] = $status;
        }
        $sql .= " ORDER BY a.created_at DESC LIMIT :lim OFFSET :off";
        $params[':lim'] = $limit;
        $params[':off'] = $offset;
        // Use raw PDO for named params with LIMIT/OFFSET
        $stmt = $this->db->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($k, $v, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFiltered(string $search = '', string $status = ''): int {
        $sql = "SELECT COUNT(*) FROM affiliates WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (first_name LIKE :s OR last_name LIKE :s OR email LIKE :s OR affiliate_number LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        if ($status) {
            $sql .= " AND state = :st";
            $params[':st'] = $status;
        }
        $stmt = $this->db->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findWithDetails(int $id): ?array {
        return $this->queryOne(
            "SELECT a.*, u.name AS user_name
             FROM affiliates a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.id = ?", [$id]
        );
    }

    /**
     * Historial de cambios del afiliado.
     *
     * La tabla `affiliate_history` del sistema anterior NO existe en
     * cdhq_mobile. El historial se lee ahora de `activity_log`, que es la
     * bitácora que comparten el portal y la aplicación móvil.
     */
    public function getHistory(int $affiliateId): array {
        return $this->query(
            "SELECT al.action AS field_changed,
                    al.details AS reason,
                    al.created_at,
                    u.name AS changed_by_name
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.entity = 'affiliates' AND al.entity_id = ?
             ORDER BY al.created_at DESC",
            [$affiliateId]
        );
    }

    /** Registra un cambio del afiliado en la bitácora compartida */
    public function logHistory(int $affiliateId, string $field, string $oldVal, string $newVal, string $reason = ''): void {
        $detalle = trim(sprintf('%s: %s → %s%s',
            $field,
            $oldVal !== '' ? $oldVal : '(vacío)',
            $newVal,
            $reason !== '' ? ' · ' . $reason : ''
        ));
        Auth::logActivity('CAMBIO_AFILIADO', 'affiliates', $affiliateId, $detalle);
    }

    public function statsByState(): array {
        return $this->query(
            "SELECT state, COUNT(*) AS total FROM affiliates GROUP BY state ORDER BY total DESC"
        );
    }

    public function statsByEstadoMx(): array {
        return $this->query(
            "SELECT COALESCE(NULLIF(estado_mexico,''), 'Sin especificar') AS estado_mexico,
                    COUNT(*) AS total
             FROM affiliates
             GROUP BY COALESCE(NULLIF(estado_mexico,''), 'Sin especificar')
             ORDER BY total DESC
             LIMIT 15"
        );
    }

    public function monthlyRegistrations(int $months = 12): array {
        return $this->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
             FROM affiliates
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month
             ORDER BY month",
            [$months]
        );
    }
}
