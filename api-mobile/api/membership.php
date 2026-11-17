<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/stripe.php';
$userId = currentUserId();
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = jsonInput();
    $motivation = trim((string) ($input['motivation'] ?? ''));
    $freeCredentialRequested = filter_var($input['free_credential_requested'] ?? false, FILTER_VALIDATE_BOOL);
    $credentialReason = trim((string) ($input['credential_reason'] ?? ''));
    $deliveryMethod = strtoupper(trim((string) ($input['delivery_method'] ?? 'RECOGER')));
    $shippingAddress = trim((string) ($input['shipping_address'] ?? ''));
    if (textLength($motivation) < 20) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_MOTIVATION', 'message' => 'Explica brevemente por qué deseas postularte.'], 422);
    }
    if ($freeCredentialRequested) {
        if (textLength($credentialReason) < 20 || textLength($credentialReason) > 1500) {
            jsonResponse(['ok' => false, 'error' => 'INVALID_CREDENTIAL_REASON', 'message' => 'Explica en al menos 20 caracteres por qué necesitas la credencial gratuita.'], 422);
        }
        if (!in_array($deliveryMethod, ['RECOGER', 'ENVIO'], true)) {
            jsonResponse(['ok' => false, 'error' => 'INVALID_DELIVERY_METHOD', 'message' => 'Selecciona una forma de entrega válida.'], 422);
        }
        if ($deliveryMethod === 'ENVIO' && (textLength($shippingAddress) < 20 || textLength($shippingAddress) > 1000)) {
            jsonResponse(['ok' => false, 'error' => 'INVALID_SHIPPING_ADDRESS', 'message' => 'Escribe el domicilio completo donde deberá enviarse la credencial.'], 422);
        }
    }
    $pending = $db->prepare(
        'SELECT id FROM defender_applications
         WHERE user_id = :user_id AND status NOT IN ("RECHAZADA", "CANCELADA", "ACREDITADA") LIMIT 1'
    );
    $pending->execute(['user_id' => $userId]);
    if ($pending->fetch()) {
        jsonResponse(['ok' => false, 'error' => 'APPLICATION_EXISTS', 'message' => 'Ya tienes una postulación activa.'], 409);
    }
    $db->beginTransaction();
    try {
        $insert = $db->prepare(
            'INSERT INTO defender_applications
             (user_id, motivation, education, experience, status, submitted_at, conduct_accepted_at, truth_accepted_at)
             VALUES (:user_id, :motivation, :education, :experience, "ENVIADA", NOW(), NOW(), NOW())'
        );
        $insert->execute([
            'user_id' => $userId,
            'motivation' => $motivation,
            'education' => trim((string) ($input['education'] ?? '')) ?: null,
            'experience' => trim((string) ($input['experience'] ?? '')) ?: null,
        ]);
        $applicationId = (int) $db->lastInsertId();
        if ($freeCredentialRequested) {
            $request = $db->prepare(
                'INSERT INTO credential_requests
                 (application_id, user_id, reason, delivery_method, shipping_address, status)
                 VALUES (:application_id, :user_id, :reason, :delivery_method, :shipping_address, "PENDIENTE")'
            );
            $request->execute([
                'application_id' => $applicationId,
                'user_id' => $userId,
                'reason' => $credentialReason,
                'delivery_method' => $deliveryMethod,
                'shipping_address' => $deliveryMethod === 'ENVIO' ? $shippingAddress : null,
            ]);
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }

    // Avisos posteriores al commit: la postulación ya quedó guardada aunque la
    // entrega de la notificación falle.
    $nameQuery = $db->prepare('SELECT name FROM users WHERE id = :id');
    $nameQuery->execute(['id' => $userId]);
    $applicantName = (string) ($nameQuery->fetchColumn() ?: 'Un usuario');

    notifyRoles(
        ['ASESOR', 'ADMINISTRADOR'],
        'POSTULACION_NUEVA',
        'Nueva postulación de defensor',
        $applicantName . ' envió una postulación para integrarse a la red de defensores.',
        '/membership'
    );
    if ($freeCredentialRequested) {
        notifyRoles(
            ['ASESOR', 'ADMINISTRADOR'],
            'CREDENCIAL_SOLICITADA',
            'Solicitud de credencial gratuita',
            $applicantName . ' solicitó una credencial gratuita. Revisa el motivo y la forma de entrega.',
            '/membership'
        );
        notifyUser(
            $userId,
            'CREDENCIAL_SOLICITADA',
            'Recibimos tu solicitud de credencial',
            'CDHQ revisará tu solicitud de credencial gratuita. Te avisaremos en cuanto haya una resolución.',
            '/membership'
        );
    } else {
        notifyUser(
            $userId,
            'POSTULACION_ENVIADA',
            'Recibimos tu postulación',
            'Tu postulación como defensor quedó registrada. Te avisaremos cuando el equipo la revise.',
            '/membership'
        );
    }
}

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$application = $db->prepare(
    'SELECT id, status, motivation, education, experience, review_notes, submitted_at, reviewed_at, created_at, updated_at
     FROM defender_applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1'
);
$application->execute(['user_id' => $userId]);
$affiliate = $db->prepare(
    'SELECT a.id, a.affiliate_number, a.state,
            s.status AS subscription_status, s.start_date, s.end_date,
            c.credential_number, c.status AS credential_status, c.issued_at, c.expires_at
     FROM affiliates a
     LEFT JOIN subscriptions s ON s.id = (SELECT s2.id FROM subscriptions s2 WHERE s2.affiliate_id = a.id ORDER BY s2.end_date DESC LIMIT 1)
     LEFT JOIN credentials c ON c.id = (SELECT c2.id FROM credentials c2 WHERE c2.affiliate_id = a.id ORDER BY c2.issued_at DESC LIMIT 1)
     WHERE a.user_id = :user_id LIMIT 1'
);
$affiliate->execute(['user_id' => $userId]);
$plans = $db->query(
    'SELECT id, name, description, price, duration_days FROM subscription_plans WHERE active = 1 ORDER BY duration_days DESC, price ASC'
)->fetchAll();
$paymentOrder = $db->prepare(
    'SELECT po.id, po.folio, po.status, po.amount_snapshot, po.currency, po.payment_provider,
            po.paid_at, po.expires_at, po.created_at, sp.name AS plan_name
     FROM payment_orders po
     JOIN affiliates a ON a.id = po.affiliate_id
     JOIN subscription_plans sp ON sp.id = po.plan_id
     WHERE a.user_id = :user_id
     ORDER BY po.created_at DESC LIMIT 1'
);
$paymentOrder->execute(['user_id' => $userId]);
$applicationData = $application->fetch() ?: null;
$affiliateData = $affiliate->fetch() ?: null;
$credentialRequest = null;
$physicalOrder = null;
if ($applicationData) {
    $credentialRequestQuery = $db->prepare(
        'SELECT id, reason, delivery_method, shipping_address, status, resolution_notes, reviewed_at, created_at
         FROM credential_requests WHERE application_id = :application_id LIMIT 1'
    );
    $credentialRequestQuery->execute(['application_id' => $applicationData['id']]);
    $credentialRequest = $credentialRequestQuery->fetch() ?: null;
}
if ($affiliateData) {
    $physicalOrderQuery = $db->prepare(
        'SELECT status, delivery_method, tracking_code, created_at, updated_at
         FROM physical_credential_orders WHERE affiliate_id = :affiliate_id ORDER BY created_at DESC LIMIT 1'
    );
    $physicalOrderQuery->execute(['affiliate_id' => $affiliateData['id']]);
    $physicalOrder = $physicalOrderQuery->fetch() ?: null;
}

jsonResponse([
    'ok' => true,
    'application' => $applicationData,
    'credential_request' => $credentialRequest,
    'physical_credential_order' => $physicalOrder,
    'affiliate' => $affiliateData,
    'plans' => $plans,
    'payment_order' => $paymentOrder->fetch() ?: null,
    'payments_enabled' => stripeIsConfigured() && in_array($applicationData['status'] ?? '', ['APROBADA', 'PENDIENTE_PAGO'], true),
    'payment_mode' => stripeIsConfigured() ? 'TEST' : null,
]);
