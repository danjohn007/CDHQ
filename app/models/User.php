<?php
require_once CORE_PATH . '/Model.php';

class User extends Model {
    protected string $table = 'users';

    public function findByEmail(string $email): ?array {
        return $this->queryOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public function allWithStats(): array {
        return $this->query(
            "SELECT u.*, 
                    (SELECT COUNT(*) FROM affiliates a WHERE a.user_id = u.id) AS affiliate_count
             FROM users u ORDER BY u.name"
        );
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
}
