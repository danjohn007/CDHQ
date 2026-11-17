<?php
declare(strict_types=1);

function ensureAffiliateForApplication(PDO $db, int $applicationId): int
{
    $query = $db->prepare(
        'SELECT da.id, da.user_id, da.affiliate_id, u.name, u.email
         FROM defender_applications da JOIN users u ON u.id = da.user_id
         WHERE da.id = :id FOR UPDATE'
    );
    $query->execute(['id' => $applicationId]);
    $application = $query->fetch();
    if (!$application) throw new RuntimeException('APPLICATION_NOT_FOUND');
    if ($application['affiliate_id']) return (int) $application['affiliate_id'];

    $existing = $db->prepare('SELECT id FROM affiliates WHERE user_id = :user_id LIMIT 1 FOR UPDATE');
    $existing->execute(['user_id' => $application['user_id']]);
    $affiliateId = $existing->fetchColumn();
    if (!$affiliateId) {
        $parts = preg_split('/\s+/u', trim((string) $application['name']), 2) ?: [];
        $insert = $db->prepare(
            'INSERT INTO affiliates (affiliate_number, user_id, first_name, last_name, email, state)
             VALUES (:number, :user_id, :first_name, :last_name, :email, "PROSPECTO")'
        );
        $insert->execute([
            'number' => sprintf('CDHQ-%s-%06d', date('y'), (int) $application['user_id']),
            'user_id' => $application['user_id'],
            'first_name' => $parts[0] ?? 'Afiliado',
            'last_name' => $parts[1] ?? '-',
            'email' => $application['email'],
        ]);
        $affiliateId = (int) $db->lastInsertId();
    }
    $db->prepare('UPDATE defender_applications SET affiliate_id = :affiliate_id WHERE id = :id')
        ->execute(['affiliate_id' => $affiliateId, 'id' => $applicationId]);
    return (int) $affiliateId;
}
