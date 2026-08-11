<?php
require_once CORE_PATH . '/Model.php';

class User extends Model {
    protected string $table = 'users';

    public function findByEmail(string $email): ?array {
        return $this->queryOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    /** Listado con filtros y actividad relacionada */
    public function listWithFilters(array $f = [], int $limit = 50, int $offset = 0): array {
        [$where, $params] = $this->buildWhere($f);
        return $this->query(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM affiliates a WHERE a.user_id = u.id)        AS affiliate_count,
                    (SELECT COUNT(*) FROM support_requests sr WHERE sr.user_id = u.id) AS request_count,
                    (SELECT MAX(al.created_at) FROM activity_log al WHERE al.user_id = u.id) AS last_activity
             FROM users u
             {$where}
             ORDER BY FIELD(u.app_role,'ADMINISTRADOR','ASESOR','USUARIO'), u.name
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countWithFilters(array $f = []): int {
        [$where, $params] = $this->buildWhere($f);
        return (int) $this->queryColumn("SELECT COUNT(*) FROM users u {$where}", $params);
    }

    private function buildWhere(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = "(u.name LIKE :q OR u.email LIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['app_role'])) {
            $where[] = "u.app_role = :app_role";
            $params[':app_role'] = $f['app_role'];
        }
        if (isset($f['active']) && $f['active'] !== '') {
            $where[] = "u.active = :active";
            $params[':active'] = (int) $f['active'];
        }
        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    /** Asesores activos, para asignar solicitudes */
    public function listAdvisors(): array {
        return $this->query(
            "SELECT id, name, email,
                    (SELECT COUNT(*) FROM support_requests sr
                     WHERE sr.assigned_to = users.id
                       AND sr.status NOT IN ('CERRADA','CANCELADA')) AS open_cases
             FROM users
             WHERE active = 1 AND app_role IN ('ASESOR','ADMINISTRADOR')
             ORDER BY name"
        );
    }

    public function countAdmins(): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM users WHERE active = 1 AND app_role = 'ADMINISTRADOR'"
        );
    }

    /**
     * ¿Se puede quitar el acceso administrativo a este usuario?
     * Bloquea dejar el sistema sin ningún administrador activo.
     */
    public function isLastActiveAdmin(int $userId): bool {
        $row = $this->queryOne("SELECT app_role, active FROM users WHERE id = ?", [$userId]);
        if (!$row || $row['app_role'] !== APP_ROLE_ADMINISTRADOR || !$row['active']) {
            return false;
        }
        return $this->countAdmins() <= 1;
    }

    public function createUser(array $data): int {
        $data['password']   = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }

    public function updateUser(int $id, array $data): int {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }

    public function toggleActive(int $id): void {
        $this->exec("UPDATE users SET active = NOT active, updated_at = NOW() WHERE id = ?", [$id]);
    }

    public function setAppRole(int $id, string $appRole): void {
        $this->exec("UPDATE users SET app_role = ?, updated_at = NOW() WHERE id = ?", [$appRole, $id]);
    }

    public function countsByAppRole(): array {
        $rows = $this->query(
            "SELECT app_role, COUNT(*) AS total FROM users WHERE active = 1 GROUP BY app_role"
        );
        return array_column($rows, 'total', 'app_role');
    }

    public function countNewSince(int $days = 30): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }
}
