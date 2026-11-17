<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/membership_service.php';
$admin = requireRoles('ADMINISTRADOR');
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = jsonInput();
    $entity = (string) ($input['entity'] ?? '');
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'INVALID_ID'], 422);

    if ($entity === 'application') {
        $status = strtoupper((string) ($input['status'] ?? ''));
        $allowed = ['EN_REVISION', 'CON_OBSERVACIONES', 'APROBADA', 'RECHAZADA', 'PENDIENTE_PAGO', 'CANCELADA'];
        if (!in_array($status, $allowed, true)) jsonResponse(['ok' => false, 'error' => 'INVALID_STATUS'], 422);
        $db->beginTransaction();
        try {
            $savedStatus = $status === 'APROBADA' ? 'PENDIENTE_PAGO' : $status;
            if (in_array($status, ['APROBADA', 'PENDIENTE_PAGO'], true)) ensureAffiliateForApplication($db, (int) $id);
            $query = $db->prepare(
                'UPDATE defender_applications SET status = :status, review_notes = :notes,
                 reviewed_by = :reviewer, reviewed_at = NOW() WHERE id = :id'
            );
            $query->execute(['status' => $savedStatus, 'notes' => trim((string) ($input['notes'] ?? '')) ?: null, 'reviewer' => $admin['id'], 'id' => $id]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
    } elseif ($entity === 'user') {
        $role = strtoupper((string) ($input['role'] ?? ''));
        if (!in_array($role, ['USUARIO', 'ASESOR', 'ADMINISTRADOR'], true)) jsonResponse(['ok' => false, 'error' => 'INVALID_ROLE'], 422);
        if ((int) $id === (int) $admin['id'] && $role !== 'ADMINISTRADOR') jsonResponse(['ok' => false, 'error' => 'CANNOT_DEMOTE_SELF', 'message' => 'No puedes quitarte tu propio acceso administrativo.'], 409);
        $legacy = ['USUARIO' => 'AFILIADO', 'ASESOR' => 'EJECUTIVO_PROSPECTOS', 'ADMINISTRADOR' => 'SUPER_ADMIN'][$role];
        $db->prepare('UPDATE users SET app_role = :role, role = :legacy WHERE id = :id')->execute(['role' => $role, 'legacy' => $legacy, 'id' => $id]);
    } elseif ($entity === 'plan') {
        $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0) jsonResponse(['ok' => false, 'error' => 'INVALID_PRICE'], 422);
        $db->prepare('UPDATE subscription_plans SET price = :price WHERE id = :id')->execute(['price' => $price, 'id' => $id]);
    } else {
        jsonResponse(['ok' => false, 'error' => 'INVALID_ENTITY'], 422);
    }
}

if (!in_array($method, ['GET', 'POST'], true)) { header('Allow: GET, POST'); jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405); }

$applications = $db->query(
    'SELECT da.id, da.status, da.motivation, da.education, da.experience, da.review_notes, da.submitted_at, u.name, u.email
     FROM defender_applications da JOIN users u ON u.id = da.user_id ORDER BY da.created_at DESC LIMIT 100'
)->fetchAll();
$users = $db->query('SELECT id, name, email, app_role, active, created_at FROM users ORDER BY created_at DESC LIMIT 200')->fetchAll();
$plans = $db->query('SELECT id, name, description, price, duration_days, active FROM subscription_plans ORDER BY id')->fetchAll();
jsonResponse(['ok' => true, 'applications' => $applications, 'users' => $users, 'plans' => $plans]);
