<?php
require_once CORE_PATH . '/Model.php';

class Council extends Model {
    protected string $table = 'council_positions';

    public function getPositionsWithAssignments(): array {
        return $this->query(
            "SELECT cp.*,
                    ca.id AS assignment_id,
                    ca.start_date,
                    ca.end_date AS assign_end,
                    ca.status AS assign_status,
                    CONCAT(a.first_name,' ',a.last_name) AS holder_name,
                    a.id AS affiliate_id,
                    a.affiliate_number
             FROM council_positions cp
             LEFT JOIN council_assignments ca ON ca.position_id = cp.id AND ca.status = 'ACTIVO'
             LEFT JOIN affiliates a ON a.id = ca.affiliate_id
             WHERE cp.is_active = 1
             ORDER BY cp.level, cp.title"
        );
    }

    public function getAllAssignments(): array {
        return $this->query(
            "SELECT ca.*, cp.title AS position_title, cp.level,
                    CONCAT(a.first_name,' ',a.last_name) AS affiliate_name,
                    a.affiliate_number
             FROM council_assignments ca
             JOIN council_positions cp ON cp.id = ca.position_id
             JOIN affiliates a ON a.id = ca.affiliate_id
             ORDER BY ca.status DESC, cp.level, ca.start_date DESC"
        );
    }

    public function createAssignment(int $positionId, int $affiliateId, string $startDate): int {
        // End any current active assignment for unique positions
        $pos = $this->find($positionId);
        if ($pos && $pos['is_unique']) {
            $this->exec(
                "UPDATE council_assignments SET status = 'FINALIZADO', end_date = ? WHERE position_id = ? AND status = 'ACTIVO'",
                [date('Y-m-d'), $positionId]
            );
        }
        return $this->db->insert(
            "INSERT INTO council_assignments (position_id, affiliate_id, start_date, status) VALUES (?, ?, ?, 'ACTIVO')",
            [$positionId, $affiliateId, $startDate]
        );
    }
}
