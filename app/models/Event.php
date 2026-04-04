<?php
require_once CORE_PATH . '/Model.php';

class Event extends Model {
    protected string $table = 'events';

    public function listUpcoming(int $limit = 5): array {
        return $this->query(
            "SELECT * FROM events WHERE date >= CURDATE() AND status != 'CANCELADO'
             ORDER BY date ASC LIMIT ?",
            [$limit]
        );
    }

    public function listAll(string $status = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT e.*,
                       (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registrations
                FROM events e WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY e.date DESC LIMIT {$limit} OFFSET {$offset}";
        return $this->query($sql, $params);
    }

    public function getWithRegistrations(int $id): ?array {
        $event = $this->find($id);
        if (!$event) return null;
        $event['registrations'] = $this->query(
            "SELECT er.*, CONCAT(a.first_name,' ',a.last_name) AS affiliate_name,
                    a.affiliate_number
             FROM event_registrations er
             JOIN affiliates a ON a.id = er.affiliate_id
             WHERE er.event_id = ?
             ORDER BY er.registered_at",
            [$id]
        );
        return $event;
    }

    public function isRegistered(int $eventId, int $affiliateId): bool {
        return (bool) $this->queryColumn(
            "SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND affiliate_id = ?",
            [$eventId, $affiliateId]
        );
    }

    public function register(int $eventId, int $affiliateId): void {
        if ($this->isRegistered($eventId, $affiliateId)) return;
        $qr = md5("event-{$eventId}-affiliate-{$affiliateId}-" . time());
        $this->exec(
            "INSERT INTO event_registrations (event_id, affiliate_id, registered_at, attended, qr_code)
             VALUES (?, ?, NOW(), 0, ?)",
            [$eventId, $affiliateId, $qr]
        );
    }

    public function changeStatus(int $id, string $status): void {
        $this->exec("UPDATE events SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
    }

    public function countUpcoming(): int {
        return (int) $this->queryColumn(
            "SELECT COUNT(*) FROM events WHERE date >= CURDATE() AND status NOT IN ('CANCELADO','FINALIZADO')"
        );
    }

    public function participationStats(): array {
        return $this->query(
            "SELECT e.name, e.date,
                    (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registered,
                    (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id AND er.attended = 1) AS attended
             FROM events e
             WHERE e.status = 'FINALIZADO'
             ORDER BY e.date DESC
             LIMIT 10"
        );
    }
}
