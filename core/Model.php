<?php
abstract class Model {
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /** Find a record by primary key */
    public function find(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    /** Get all records */
    public function all(string $orderBy = ''): array {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->fetchAll($sql);
    }

    /** Count all records */
    public function count(string $where = '', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        return (int) $this->db->fetchColumn($sql, $params);
    }

    /** Insert a row and return its ID */
    public function create(array $data): int {
        $cols   = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        return $this->db->insert(
            "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$places})",
            array_values($data)
        );
    }

    /** Update a row by primary key */
    public function update(int $id, array $data): int {
        $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $params = array_merge(array_values($data), [$id]);
        return $this->db->execute(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ?",
            $params
        );
    }

    /** Delete a row by primary key */
    public function delete(int $id): int {
        return $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    /** Generic query helper */
    protected function query(string $sql, array $params = []): array {
        return $this->db->fetchAll($sql, $params);
    }

    protected function queryOne(string $sql, array $params = []): ?array {
        return $this->db->fetchOne($sql, $params);
    }

    protected function queryColumn(string $sql, array $params = []) {
        return $this->db->fetchColumn($sql, $params);
    }

    protected function exec(string $sql, array $params = []): int {
        return $this->db->execute($sql, $params);
    }
}
