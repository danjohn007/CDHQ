<?php
require_once CORE_PATH . '/Model.php';

class Prospect extends Model {
    protected string $table = 'prospects';

    public function listAll(string $state = '', string $search = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT p.*, u.name AS assigned_name
                FROM prospects p
                LEFT JOIN users u ON u.id = p.assigned_to
                WHERE 1=1";
        $params = [];
        if ($state) {
            $sql .= " AND p.state = :st";
            $params[':st'] = $state;
        }
        if ($search) {
            $sql .= " AND (p.name LIKE :s OR p.email LIKE :s OR p.phone LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        $sql .= " ORDER BY p.created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getNotes(int $prospectId): array {
        return $this->query(
            "SELECT pn.*, u.name AS author
             FROM prospect_notes pn
             LEFT JOIN users u ON u.id = pn.created_by
             WHERE pn.prospect_id = ?
             ORDER BY pn.created_at DESC",
            [$prospectId]
        );
    }

    public function addNote(int $prospectId, string $note): void {
        $this->exec(
            "INSERT INTO prospect_notes (prospect_id, note, created_by, created_at) VALUES (?, ?, ?, NOW())",
            [$prospectId, $note, $_SESSION['user_id'] ?? 0]
        );
        // Update state to SEGUIMIENTO if currently CONTACTADO
        $p = $this->find($prospectId);
        if ($p && $p['state'] === 'CONTACTADO') {
            $this->update($prospectId, ['state' => 'SEGUIMIENTO', 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function convertToAffiliate(int $prospectId): ?int {
        $prospect = $this->find($prospectId);
        if (!$prospect) return null;

        require_once MODELS_PATH . '/Affiliate.php';
        $affiliateModel = new Affiliate();
        $number = $affiliateModel->generateNumber();

        $parts = explode(' ', trim($prospect['name']), 2);
        $firstName = $parts[0];
        $lastName  = $parts[1] ?? '';

        $affiliateId = $affiliateModel->create([
            'affiliate_number' => $number,
            'user_id'          => null,
            'first_name'       => $firstName,
            'last_name'        => $lastName,
            'email'            => $prospect['email'],
            'phone'            => $prospect['phone'],
            'state'            => 'PROSPECTO',
            'notes'            => $prospect['notes'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        // Mark prospect as CONVERTIDO
        $this->update($prospectId, [
            'state'      => 'CONVERTIDO',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $affiliateId;
    }

    public function conversionStats(): array {
        return $this->query(
            "SELECT state, COUNT(*) AS total FROM prospects GROUP BY state ORDER BY total DESC"
        );
    }
}
