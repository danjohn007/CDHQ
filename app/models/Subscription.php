<?php
require_once CORE_PATH . '/Model.php';

class Subscription extends Model {
    protected string $table = 'subscriptions';

    public function getPlans(): array {
        return $this->query("SELECT * FROM subscription_plans WHERE active = 1 ORDER BY price");
    }

    public function getAffiliateSubscriptions(int $affiliateId): array {
        return $this->query(
            "SELECT s.*, sp.name AS plan_name, sp.price
             FROM subscriptions s
             JOIN subscription_plans sp ON sp.id = s.plan_id
             WHERE s.affiliate_id = ?
             ORDER BY s.start_date DESC",
            [$affiliateId]
        );
    }

    public function getActiveSubscription(int $affiliateId): ?array {
        return $this->queryOne(
            "SELECT s.*, sp.name AS plan_name, sp.price, sp.duration_days
             FROM subscriptions s
             JOIN subscription_plans sp ON sp.id = s.plan_id
             WHERE s.affiliate_id = ? AND s.status = 'ACTIVA'
             ORDER BY s.end_date DESC LIMIT 1",
            [$affiliateId]
        );
    }

    public function listAll(int $limit = 50, int $offset = 0): array {
        return $this->query(
            "SELECT s.*, sp.name AS plan_name,
                    CONCAT(a.first_name,' ',a.last_name) AS affiliate_name,
                    a.affiliate_number
             FROM subscriptions s
             JOIN subscription_plans sp ON sp.id = s.plan_id
             JOIN affiliates a ON a.id = s.affiliate_id
             ORDER BY s.start_date DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function createSubscription(int $affiliateId, int $planId, string $startDate): int {
        // Get plan duration
        $plan = $this->queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$planId]);
        $days = $plan['duration_days'] ?? 365;
        $endDate = date('Y-m-d', strtotime($startDate . " + {$days} days"));

        // Suspend any existing active subscriptions
        $this->exec(
            "UPDATE subscriptions SET status = 'SUSPENDIDA' WHERE affiliate_id = ? AND status = 'ACTIVA'",
            [$affiliateId]
        );

        $id = $this->create([
            'affiliate_id' => $affiliateId,
            'plan_id'      => $planId,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'status'       => 'ACTIVA',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        // Update affiliate state to ACTIVO
        $this->exec("UPDATE affiliates SET state = 'ACTIVO', updated_at = NOW() WHERE id = ?", [$affiliateId]);

        return $id;
    }

    public function countActive(): int {
        return (int) $this->queryColumn("SELECT COUNT(*) FROM subscriptions WHERE status = 'ACTIVA'");
    }

    public function checkExpired(): void {
        $this->exec(
            "UPDATE subscriptions SET status = 'VENCIDA' WHERE status = 'ACTIVA' AND end_date < CURDATE()"
        );
        // Also update affiliate state
        $this->exec(
            "UPDATE affiliates SET state = 'VENCIDO', updated_at = NOW()
             WHERE id IN (
                SELECT affiliate_id FROM subscriptions WHERE status = 'VENCIDA'
             ) AND state = 'ACTIVO'"
        );
    }
}
