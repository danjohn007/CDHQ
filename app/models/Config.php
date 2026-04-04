<?php
require_once CORE_PATH . '/Model.php';

class Config extends Model {
    protected string $table = 'configurations';
    protected string $primaryKey = 'id';

    public function getByKey(string $key): ?string {
        $row = $this->queryOne("SELECT value FROM configurations WHERE `key` = ?", [$key]);
        return $row ? $row['value'] : null;
    }

    public function getGroup(string $group): array {
        $rows = $this->query(
            "SELECT `key`, value, description FROM configurations WHERE `group` = ? ORDER BY `key`",
            [$group]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row;
        }
        return $result;
    }

    public function getAll(): array {
        $rows = $this->query(
            "SELECT `key`, value, `group`, description FROM configurations ORDER BY `group`, `key`"
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['group']][$row['key']] = $row;
        }
        return $result;
    }

    public function set(string $key, string $value): void {
        $exists = $this->queryColumn(
            "SELECT COUNT(*) FROM configurations WHERE `key` = ?", [$key]
        );
        if ($exists) {
            $this->exec("UPDATE configurations SET value = ?, updated_at = NOW() WHERE `key` = ?", [$value, $key]);
        } else {
            $this->exec(
                "INSERT INTO configurations (`key`, value, `group`, created_at, updated_at) VALUES (?, ?, 'general', NOW(), NOW())",
                [$key, $value]
            );
        }
    }

    public function setMultiple(array $data): void {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
