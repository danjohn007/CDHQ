<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/membership_service.php';
$staff = requireRoles('ASESOR', 'ADMINISTRADOR');
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = jsonInput();
    $action = strtolower(trim((string) ($input['action'] ?? 'application_status')));

    if ($action === 'credential_request') {
        if ($staff['app_role'] !== 'ADMINISTRADOR') {
            jsonResponse(['ok' => false, 'error' => 'FORBIDDEN', 'message' => 'Sólo un administrador puede resolver solicitudes gratuitas.'], 403);
        }
        $requestId = filter_var($input['credential_request_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = strtoupper(trim((string) ($input['decision'] ?? '')));
        $resolutionNotes = trim((string) ($input['notes'] ?? ''));
        if (!$requestId || !in_array($decision, ['APROBAR', 'DENEGAR'], true)) {
            jsonResponse(['ok' => false, 'error' => 'INVALID_CREDENTIAL_DECISION'], 422);
        }
        if ($decision === 'DENEGAR' && textLength($resolutionNotes) < 5) {
            jsonResponse(['ok' => false, 'error' => 'RESOLUTION_NOTES_REQUIRED', 'message' => 'Escribe el motivo por el que se deniega la credencial gratuita.'], 422);
        }

        $db->beginTransaction();
        try {
            $requestQuery = $db->prepare(
                'SELECT cr.*, da.status AS application_status
                 FROM credential_requests cr
                 JOIN defender_applications da ON da.id = cr.application_id
                 WHERE cr.id = :id FOR UPDATE'
            );
            $requestQuery->execute(['id' => $requestId]);
            $credentialRequest = $requestQuery->fetch();
            if (!$credentialRequest) {
                $db->rollBack();
                jsonResponse(['ok' => false, 'error' => 'CREDENTIAL_REQUEST_NOT_FOUND'], 404);
            }
            if (!in_array($credentialRequest['status'], ['PENDIENTE', 'EN_REVISION'], true)) {
                $db->rollBack();
                jsonResponse(['ok' => false, 'error' => 'CREDENTIAL_REQUEST_ALREADY_RESOLVED', 'message' => 'Esta solicitud gratuita ya fue resuelta.'], 409);
            }

            if ($decision === 'DENEGAR') {
                $db->prepare(
                    'UPDATE credential_requests SET status = "DENEGADA", resolution_notes = :notes,
                     reviewed_by = :reviewer, reviewed_at = NOW() WHERE id = :id'
                )->execute(['notes' => $resolutionNotes, 'reviewer' => $staff['id'], 'id' => $requestId]);
                $activityAction = 'FREE_CREDENTIAL_DENIED';
            } else {
                if (!in_array($credentialRequest['application_status'], ['APROBADA', 'PENDIENTE_PAGO'], true)) {
                    $db->rollBack();
                    jsonResponse(['ok' => false, 'error' => 'APPLICATION_NOT_APPROVED', 'message' => 'Primero aprueba la postulación como defensor.'], 409);
                }
                $planQuery = $db->query(
                    'SELECT id, duration_days FROM subscription_plans WHERE active = 1
                     ORDER BY duration_days DESC, price ASC, id ASC LIMIT 1 FOR UPDATE'
                );
                $plan = $planQuery->fetch();
                if (!$plan) {
                    $db->rollBack();
                    jsonResponse(['ok' => false, 'error' => 'NO_ACTIVE_PLAN', 'message' => 'No existe un plan activo para definir la vigencia.'], 409);
                }
                $affiliateId = ensureAffiliateForApplication($db, (int) $credentialRequest['application_id']);
                $start = new DateTimeImmutable('today');
                $latest = $db->prepare(
                    'SELECT end_date FROM subscriptions WHERE affiliate_id = :affiliate_id AND end_date >= CURDATE()
                     ORDER BY end_date DESC LIMIT 1 FOR UPDATE'
                );
                $latest->execute(['affiliate_id' => $affiliateId]);
                if ($latestEnd = $latest->fetchColumn()) $start = (new DateTimeImmutable($latestEnd))->modify('+1 day');
                $days = max(1, (int) $plan['duration_days']);
                $end = $start->modify('+' . ($days - 1) . ' days');
                $db->prepare(
                    'INSERT INTO subscriptions (affiliate_id, plan_id, start_date, end_date, status)
                     VALUES (:affiliate_id, :plan_id, :start_date, :end_date, "ACTIVA")'
                )->execute([
                    'affiliate_id' => $affiliateId, 'plan_id' => $plan['id'],
                    'start_date' => $start->format('Y-m-d'), 'end_date' => $end->format('Y-m-d'),
                ]);
                $subscriptionId = (int) $db->lastInsertId();
                $db->prepare('UPDATE credentials SET status = "VENCIDA" WHERE affiliate_id = :affiliate_id AND status = "VIGENTE"')
                    ->execute(['affiliate_id' => $affiliateId]);
                $db->prepare(
                    'INSERT INTO credentials
                     (affiliate_id, subscription_id, credential_number, verification_token, issued_at, expires_at, status, issued_by)
                     VALUES (:affiliate_id, :subscription_id, :number, :token, NOW(), :expires_at, "VIGENTE", :issued_by)'
                )->execute([
                    'affiliate_id' => $affiliateId, 'subscription_id' => $subscriptionId,
                    'number' => sprintf('CDHQ-DH-G-%s-%08d', date('Y'), $requestId),
                    'token' => bin2hex(random_bytes(32)), 'expires_at' => $end->format('Y-m-d 23:59:59'),
                    'issued_by' => $staff['id'],
                ]);
                $credentialId = (int) $db->lastInsertId();
                $db->prepare(
                    'INSERT INTO physical_credential_orders
                     (credential_id, credential_request_id, affiliate_id, source, status, delivery_method, shipping_address)
                     VALUES (:credential_id, :request_id, :affiliate_id, "CORTESIA", "PENDIENTE_DATOS", :delivery_method, :shipping_address)'
                )->execute([
                    'credential_id' => $credentialId, 'request_id' => $requestId, 'affiliate_id' => $affiliateId,
                    'delivery_method' => $credentialRequest['delivery_method'],
                    'shipping_address' => $credentialRequest['shipping_address'],
                ]);
                $db->prepare(
                    'UPDATE credential_requests SET status = "APROBADA", resolution_notes = :notes,
                     reviewed_by = :reviewer, reviewed_at = NOW(), plan_id = :plan_id,
                     subscription_id = :subscription_id, credential_id = :credential_id WHERE id = :id'
                )->execute([
                    'notes' => $resolutionNotes !== '' ? $resolutionNotes : null, 'reviewer' => $staff['id'],
                    'plan_id' => $plan['id'], 'subscription_id' => $subscriptionId,
                    'credential_id' => $credentialId, 'id' => $requestId,
                ]);
                $db->prepare('UPDATE affiliates SET state = "ACTIVO" WHERE id = :id')->execute(['id' => $affiliateId]);
                $db->prepare('UPDATE defender_applications SET status = "ACREDITADA" WHERE id = :id')
                    ->execute(['id' => $credentialRequest['application_id']]);
                $activityAction = 'FREE_CREDENTIAL_APPROVED';
            }
            $db->prepare(
                'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
                 VALUES (:user_id, :action, "credential_requests", :entity_id, :details, :ip)'
            )->execute([
                'user_id' => $staff['id'], 'action' => $activityAction, 'entity_id' => $requestId,
                'details' => $resolutionNotes !== '' ? $resolutionNotes : 'Solicitud gratuita aprobada.',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $db->commit();

            // Se avisa después del commit: la resolución ya está guardada.
            if ($decision === 'DENEGAR') {
                notifyUser(
                    (int) $credentialRequest['user_id'],
                    'CREDENCIAL_RESUELTA',
                    'Tu solicitud de credencial fue denegada',
                    'CDHQ no aprobó la credencial gratuita. Motivo: ' . $resolutionNotes
                        . ' Puedes continuar con el pago normal de la anualidad.',
                    '/membership'
                );
            } else {
                notifyUser(
                    (int) $credentialRequest['user_id'],
                    'CREDENCIAL_RESUELTA',
                    'Tu credencial fue aprobada',
                    'CDHQ aprobó tu credencial gratuita. Ya puedes consultarla y mostrar su código QR desde la aplicación.',
                    '/credential'
                );
            }

            jsonResponse(['ok' => true]);
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
    }

    if ($action !== 'application_status') {
        jsonResponse(['ok' => false, 'error' => 'INVALID_ACTION'], 422);
    }
    $applicationId = filter_var($input['application_id'] ?? null, FILTER_VALIDATE_INT);
    $status = strtoupper((string) ($input['status'] ?? ''));
    $notes = trim((string) ($input['notes'] ?? ''));

    if (!$applicationId) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_APPLICATION'], 422);
    }

    $allowed = $staff['app_role'] === 'ADMINISTRADOR'
        ? ['EN_REVISION', 'CON_OBSERVACIONES', 'APROBADA', 'RECHAZADA', 'PENDIENTE_PAGO', 'CANCELADA']
        : ['EN_REVISION', 'CON_OBSERVACIONES'];

    if (!in_array($status, $allowed, true)) {
        jsonResponse([
            'ok' => false,
            'error' => 'STATUS_NOT_ALLOWED',
            'message' => 'Tu rol no permite asignar ese estado.',
        ], 403);
    }
    if ($status === 'CON_OBSERVACIONES' && textLength($notes) < 5) {
        jsonResponse([
            'ok' => false,
            'error' => 'REVIEW_NOTES_REQUIRED',
            'message' => 'Escribe las observaciones que deberá atender el postulante.',
        ], 422);
    }

    $db->beginTransaction();
    try {
        $exists = $db->prepare('SELECT id FROM defender_applications WHERE id = :id FOR UPDATE');
        $exists->execute(['id' => $applicationId]);
        if (!$exists->fetch()) {
            $db->rollBack();
            jsonResponse(['ok' => false, 'error' => 'APPLICATION_NOT_FOUND'], 404);
        }
        // Aprobar habilita el pago; la acreditación sólo ocurre tras el webhook de Stripe.
        $savedStatus = $status === 'APROBADA' ? 'PENDIENTE_PAGO' : $status;
        if (in_array($status, ['APROBADA', 'PENDIENTE_PAGO'], true)) ensureAffiliateForApplication($db, (int) $applicationId);
        $update = $db->prepare(
            'UPDATE defender_applications
             SET status = :status, review_notes = :notes, reviewed_by = :reviewer, reviewed_at = NOW()
             WHERE id = :id'
        );
        $update->execute([
            'status' => $savedStatus, 'notes' => $notes !== '' ? $notes : null,
            'reviewer' => $staff['id'], 'id' => $applicationId,
        ]);
        if (in_array($status, ['RECHAZADA', 'CANCELADA'], true)) {
            $db->prepare(
                'UPDATE credential_requests SET status = "CANCELADA", resolution_notes = COALESCE(:notes, resolution_notes),
                 reviewed_by = :reviewer, reviewed_at = NOW()
                 WHERE application_id = :application_id AND status IN ("PENDIENTE", "EN_REVISION")'
            )->execute([
                'notes' => $notes !== '' ? $notes : null, 'reviewer' => $staff['id'],
                'application_id' => $applicationId,
            ]);
        }
        $db->commit();

        $applicantQuery = $db->prepare('SELECT user_id FROM defender_applications WHERE id = :id');
        $applicantQuery->execute(['id' => $applicationId]);
        $applicantId = (int) ($applicantQuery->fetchColumn() ?: 0);

        if ($applicantId) {
            // Cada estado tiene un mensaje propio: el usuario debe entender qué
            // pasó y qué sigue sin tener que abrir la pantalla.
            $messages = [
                'EN_REVISION' => ['Tu postulación está en revisión', 'El equipo de CDHQ comenzó a revisar tu postulación como defensor.'],
                'CON_OBSERVACIONES' => ['Tu postulación tiene observaciones', 'CDHQ dejó observaciones sobre tu postulación: ' . $notes],
                'APROBADA' => ['Tu postulación fue aprobada', 'CDHQ aprobó tu postulación. El siguiente paso es cubrir la cuota de recuperación para activar tu afiliación.'],
                'PENDIENTE_PAGO' => ['Tu postulación fue aprobada', 'CDHQ aprobó tu postulación. El siguiente paso es cubrir la cuota de recuperación para activar tu afiliación.'],
                'RECHAZADA' => ['Tu postulación no fue aprobada', $notes !== '' ? 'Motivo: ' . $notes : 'CDHQ no aprobó tu postulación como defensor en esta ocasión.'],
                'CANCELADA' => ['Tu postulación fue cancelada', $notes !== '' ? 'Motivo: ' . $notes : 'CDHQ canceló tu postulación como defensor.'],
            ];
            if (isset($messages[$status])) {
                [$title, $body] = $messages[$status];
                notifyUser($applicantId, 'POSTULACION_ESTADO', $title, $body, '/membership');
            }
        }
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$query = $db->query(
    'SELECT da.id, da.status, da.motivation, da.education, da.experience,
            da.review_notes, da.submitted_at, da.reviewed_at, da.created_at,
            u.id AS user_id, u.name, u.email,
            reviewer.name AS reviewer_name,
            (SELECT COUNT(*) FROM application_documents ad WHERE ad.application_id = da.id) AS document_count,
            cr.id AS credential_request_id, cr.reason AS credential_request_reason,
            cr.delivery_method AS credential_delivery_method, cr.shipping_address AS credential_shipping_address,
            cr.status AS credential_request_status, cr.resolution_notes AS credential_resolution_notes,
            pco.status AS physical_credential_status
     FROM defender_applications da
     JOIN users u ON u.id = da.user_id
     LEFT JOIN users reviewer ON reviewer.id = da.reviewed_by
     LEFT JOIN credential_requests cr ON cr.application_id = da.id
     LEFT JOIN physical_credential_orders pco ON pco.credential_request_id = cr.id
     ORDER BY FIELD(da.status, "ENVIADA", "EN_REVISION", "CON_OBSERVACIONES", "APROBADA", "PENDIENTE_PAGO", "ACREDITADA", "RECHAZADA", "CANCELADA"),
              da.created_at DESC'
);

jsonResponse([
    'ok' => true,
    'viewer_role' => $staff['app_role'],
    'applications' => $query->fetchAll(),
]);
