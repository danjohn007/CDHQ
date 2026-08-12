<?php
require_once CORE_PATH . '/Model.php';

/**
 * Credenciales emitidas. Tabla `credentials` de cdhq_mobile.
 *
 * Ojo: en el esquema nuevo el token de verificación que viaja en el QR vive
 * aquí (char(64)), no en `affiliates`. Cada renovación emite una credencial
 * nueva y conserva la anterior en el historial.
 */
class Credential extends Model {
    protected string $table = 'credentials';

    public const ESTADOS = ['VIGENTE', 'VENCIDA', 'SUSPENDIDA', 'REVOCADA'];

    /** Resuelve una credencial a partir del token público del QR */
    public function findByToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return null;
        }
        return $this->queryOne(
            "SELECT c.*, a.affiliate_number, a.first_name, a.last_name, a.state AS affiliate_state,
                    s.start_date, s.end_date, s.status AS subscription_status,
                    sp.name AS plan_name
             FROM credentials c
             JOIN affiliates a          ON a.id = c.affiliate_id
             LEFT JOIN subscriptions s  ON s.id = c.subscription_id
             LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
             WHERE c.verification_token = ?",
            [strtolower($token)]
        );
    }

    /**
     * Respaldo público para credenciales impresas: exige folio Y apellido para
     * que el padrón no pueda recorrerse probando folios consecutivos.
     */
    public function findByNumberAndLastName(string $number, string $lastName): ?array {
        $number   = trim($number);
        $lastName = trim($lastName);
        if ($number === '' || $lastName === '') {
            return null;
        }
        return $this->queryOne(
            "SELECT c.*, a.affiliate_number, a.first_name, a.last_name, a.state AS affiliate_state,
                    s.start_date, s.end_date, s.status AS subscription_status,
                    sp.name AS plan_name
             FROM credentials c
             JOIN affiliates a          ON a.id = c.affiliate_id
             LEFT JOIN subscriptions s  ON s.id = c.subscription_id
             LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
             WHERE (c.credential_number = ? OR a.affiliate_number = ?)
               AND a.last_name = ?
             ORDER BY c.issued_at DESC LIMIT 1",
            [$number, $number, $lastName]
        );
    }

    public function listWithFilters(array $f = [], int $limit = 40, int $offset = 0): array {
        [$where, $params] = $this->buildWhere($f);
        return $this->query(
            "SELECT c.*, a.affiliate_number,
                    CONCAT(a.first_name,' ',a.last_name) AS affiliate_name
             FROM credentials c
             JOIN affiliates a ON a.id = c.affiliate_id
             {$where}
             ORDER BY c.issued_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countWithFilters(array $f = []): int {
        [$where, $params] = $this->buildWhere($f);
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM credentials c JOIN affiliates a ON a.id = c.affiliate_id {$where}",
            $params
        );
    }

    private function buildWhere(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = "(c.credential_number LIKE :q OR a.affiliate_number LIKE :q
                         OR a.first_name LIKE :q OR a.last_name LIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status'])) {
            $where[] = "c.status = :status";
            $params[':status'] = $f['status'];
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    public function findDetailed(int $id): ?array {
        return $this->queryOne(
            "SELECT c.*, a.affiliate_number, a.first_name, a.last_name, a.state AS affiliate_state,
                    s.start_date, s.end_date, sp.name AS plan_name
             FROM credentials c
             JOIN affiliates a          ON a.id = c.affiliate_id
             LEFT JOIN subscriptions s  ON s.id = c.subscription_id
             LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
             WHERE c.id = ?",
            [$id]
        );
    }

    /** Suspender o revocar exige motivo; nunca se borra la credencial */
    public function changeStatus(int $id, string $status, string $reason): void {
        $this->exec(
            "UPDATE credentials SET status = ?, status_reason = ?, updated_at = NOW() WHERE id = ?",
            [$status, $reason, $id]
        );
    }

    public function countActive(): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM credentials WHERE status = 'VIGENTE' AND expires_at >= NOW()"
        );
    }

    public function countExpiringSoon(int $days = 30): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM credentials
             WHERE status = 'VIGENTE'
               AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }

    public function listExpiringSoon(int $days = 30, int $limit = 8): array {
        return $this->query(
            "SELECT c.credential_number, c.expires_at, a.affiliate_number,
                    CONCAT(a.first_name,' ',a.last_name) AS affiliate_name
             FROM credentials c
             JOIN affiliates a ON a.id = c.affiliate_id
             WHERE c.status = 'VIGENTE'
               AND c.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
             ORDER BY c.expires_at ASC LIMIT {$limit}",
            [$days]
        );
    }
}
