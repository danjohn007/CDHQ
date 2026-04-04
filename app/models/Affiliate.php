<?php
require_once CORE_PATH . '/Model.php';

class Affiliate extends Model {
    protected string $table = 'affiliates';

    /** Generate next affiliate number CDHQ-YYYY-NNNN */
    public function generateNumber(): string {
        $year = date('Y');
        $count = (int) $this->queryColumn(
            "SELECT COUNT(*) FROM affiliates WHERE affiliate_number LIKE ?",
            ["CDHQ-{$year}-%"]
        );
        return sprintf('CDHQ-%s-%04d', $year, $count + 1);
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

    public function getHistory(int $affiliateId): array {
        return $this->query(
            "SELECT ah.*, u.name AS changed_by_name
             FROM affiliate_history ah
             LEFT JOIN users u ON u.id = ah.changed_by
             WHERE ah.affiliate_id = ?
             ORDER BY ah.created_at DESC", [$affiliateId]
        );
    }

    public function logHistory(int $affiliateId, string $field, string $oldVal, string $newVal, string $reason = ''): void {
        $this->exec(
            "INSERT INTO affiliate_history (affiliate_id, field_changed, old_value, new_value, reason, changed_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$affiliateId, $field, $oldVal, $newVal, $reason, $_SESSION['user_id'] ?? 0]
        );
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
