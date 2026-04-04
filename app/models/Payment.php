<?php
require_once CORE_PATH . '/Model.php';

class Payment extends Model {
    protected string $table = 'payments';

    public function listAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        $sql = "SELECT p.*, CONCAT(a.first_name,' ',a.last_name) AS affiliate_name,
                       a.affiliate_number
                FROM payments p
                LEFT JOIN affiliates a ON a.id = p.affiliate_id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['type'])) {
            $sql .= " AND p.type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND p.payment_date >= :df";
            $params[':df'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND p.payment_date <= :dt";
            $params[':dt'] = $filters['date_to'];
        }
        $sql .= " ORDER BY p.payment_date DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function sumByType(string $dateFrom = '', string $dateTo = ''): array {
        $sql = "SELECT type, SUM(amount) AS total, COUNT(*) AS qty
                FROM payments WHERE 1=1";
        $params = [];
        if ($dateFrom) { $sql .= " AND payment_date >= ?"; $params[] = $dateFrom; }
        if ($dateTo)   { $sql .= " AND payment_date <= ?"; $params[] = $dateTo; }
        $sql .= " GROUP BY type";
        return $this->query($sql, $params);
    }

    public function monthlyIncome(int $months = 12): array {
        return $this->query(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month,
                    SUM(amount) AS total
             FROM payments
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month
             ORDER BY month",
            [$months]
        );
    }

    public function countPending(): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM payments WHERE method = 'PENDIENTE'"
        );
    }

    public function getAffiliatePayments(int $affiliateId): array {
        return $this->query(
            "SELECT * FROM payments WHERE affiliate_id = ? ORDER BY payment_date DESC",
            [$affiliateId]
        );
    }
}
