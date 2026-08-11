<?php
require_once CORE_PATH . '/Model.php';

/**
 * Órdenes de pago de Stripe. Tabla `payment_orders` de cdhq_mobile.
 *
 * El portal es SÓLO DE CONSULTA sobre estas órdenes: únicamente el webhook
 * firmado de Stripe puede marcarlas como pagadas. Por eso este modelo no
 * expone ningún método que cambie el estado a PAGADA.
 */
class PaymentOrder extends Model {
    protected string $table = 'payment_orders';

    public const ESTADOS = [
        'PENDIENTE', 'COMPROBANTE_ENVIADO', 'EN_VALIDACION',
        'PAGADA', 'RECHAZADA', 'CANCELADA', 'REEMBOLSADA',
    ];

    public function listWithFilters(array $f = [], int $limit = 40, int $offset = 0): array {
        [$where, $params] = $this->buildWhere($f);
        return $this->query(
            "SELECT po.*, a.affiliate_number,
                    CONCAT(a.first_name,' ',a.last_name) AS affiliate_name,
                    sp.name AS plan_name
             FROM payment_orders po
             LEFT JOIN affiliates a          ON a.id = po.affiliate_id
             LEFT JOIN subscription_plans sp ON sp.id = po.plan_id
             {$where}
             ORDER BY po.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countWithFilters(array $f = []): int {
        [$where, $params] = $this->buildWhere($f);
        return (int) $this->queryColumn("SELECT COUNT(*) FROM payment_orders po {$where}", $params);
    }

    private function buildWhere(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = "(po.folio LIKE :q OR po.provider_payment_intent_id LIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status'])) {
            $where[] = "po.status = :status";
            $params[':status'] = $f['status'];
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    /** Órdenes pendientes cuya fecha de expiración ya pasó */
    public function countExpired(): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM payment_orders
             WHERE status = 'PENDIENTE' AND expires_at IS NOT NULL AND expires_at < NOW()"
        );
    }

    public function totalCollected(int $days = 30): float {
        return (float) $this->queryColumn(
            "SELECT COALESCE(SUM(amount_snapshot),0) FROM payment_orders
             WHERE status = 'PAGADA' AND paid_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }

    public function countsByStatus(): array {
        $rows = $this->query("SELECT status, COUNT(*) AS total FROM payment_orders GROUP BY status");
        return array_column($rows, 'total', 'status');
    }

    /** Ingresos confirmados por mes, para la gráfica del panel */
    public function monthlyCollected(int $months = 12): array {
        return $this->query(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS periodo,
                    SUM(amount_snapshot) AS total,
                    COUNT(*) AS ordenes
             FROM payment_orders
             WHERE status = 'PAGADA' AND paid_at IS NOT NULL
               AND paid_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY periodo ORDER BY periodo",
            [$months]
        );
    }
}
