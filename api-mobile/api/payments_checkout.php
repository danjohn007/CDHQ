<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/stripe.php';
require_once __DIR__ . '/membership_service.php';

requireMethod('POST');
$userId = currentUserId();
$settings = requireStripeConfiguration();
$planId = filter_var(jsonInput()['plan_id'] ?? null, FILTER_VALIDATE_INT);
if (!$planId) jsonResponse(['ok' => false, 'error' => 'INVALID_PLAN', 'message' => 'Selecciona un plan válido.'], 422);

$db = database();
$query = $db->prepare(
    'SELECT da.id, da.affiliate_id, u.email FROM defender_applications da
     JOIN users u ON u.id = da.user_id
     WHERE da.user_id = :user_id AND da.status IN ("APROBADA", "PENDIENTE_PAGO")
     ORDER BY da.created_at DESC LIMIT 1'
);
$query->execute(['user_id' => $userId]);
$application = $query->fetch();
if (!$application) {
    jsonResponse(['ok' => false, 'error' => 'APPLICATION_NOT_APPROVED', 'message' => 'Tu postulación debe ser aprobada antes de realizar el pago.'], 409);
}
$query = $db->prepare('SELECT id, name, description, price, duration_days FROM subscription_plans WHERE id = :id AND active = 1');
$query->execute(['id' => $planId]);
$plan = $query->fetch();
if (!$plan || (float) $plan['price'] <= 0) {
    jsonResponse(['ok' => false, 'error' => 'PLAN_NOT_AVAILABLE', 'message' => 'El plan no está disponible para pago.'], 422);
}

$db->beginTransaction();
try {
    $affiliateId = ensureAffiliateForApplication($db, (int) $application['id']);
    $db->prepare('UPDATE defender_applications SET status = "PENDIENTE_PAGO" WHERE id = :id')->execute(['id' => $application['id']]);
    $pending = $db->prepare(
        'SELECT id, folio, checkout_url FROM payment_orders
         WHERE affiliate_id = :affiliate_id AND application_id = :application_id AND plan_id = :plan_id
           AND status = "PENDIENTE" AND expires_at > NOW() AND checkout_url IS NOT NULL
         ORDER BY created_at DESC LIMIT 1 FOR UPDATE'
    );
    $pending->execute(['affiliate_id' => $affiliateId, 'application_id' => $application['id'], 'plan_id' => $planId]);
    $existing = $pending->fetch();
    if ($existing) {
        $db->commit();
        jsonResponse(['ok' => true, 'checkout_url' => $existing['checkout_url'], 'order' => ['id' => (int) $existing['id'], 'folio' => $existing['folio']]]);
    }
    $db->prepare(
        'UPDATE payment_orders SET status = "CANCELADA" WHERE affiliate_id = :affiliate_id
         AND application_id = :application_id AND status = "PENDIENTE" AND expires_at <= NOW()'
    )->execute(['affiliate_id' => $affiliateId, 'application_id' => $application['id']]);

    $folio = 'CDHQ-P-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $expiresAt = time() + 1800;
    $insert = $db->prepare(
        'INSERT INTO payment_orders
         (folio, affiliate_id, application_id, plan_id, amount_snapshot, currency, duration_days_snapshot,
          status, payment_provider, expires_at)
         VALUES (:folio, :affiliate_id, :application_id, :plan_id, :amount, "MXN", :days,
                 "PENDIENTE", "STRIPE", :expires_at)'
    );
    $insert->execute([
        'folio' => $folio, 'affiliate_id' => $affiliateId, 'application_id' => $application['id'],
        'plan_id' => $planId, 'amount' => $plan['price'], 'days' => $plan['duration_days'],
        'expires_at' => date('Y-m-d H:i:s', $expiresAt),
    ]);
    $orderId = (int) $db->lastInsertId();
    $session = stripeRequest('POST', '/v1/checkout/sessions', [
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'mxn',
                'unit_amount' => (int) round(((float) $plan['price']) * 100),
                'product_data' => ['name' => $plan['name'], 'description' => $plan['description'] ?: 'Cuota de recuperación CDHQ'],
            ],
            'quantity' => 1,
        ]],
        'customer_email' => $application['email'],
        'client_reference_id' => $folio,
        'metadata' => ['payment_order_id' => (string) $orderId, 'application_id' => (string) $application['id'], 'user_id' => (string) $userId],
        'success_url' => $settings['success_url'],
        'cancel_url' => $settings['cancel_url'],
        'expires_at' => $expiresAt,
        'locale' => 'es',
    ]);
    if (empty($session['id']) || empty($session['url'])) throw new RuntimeException('STRIPE_SESSION_INCOMPLETE');
    $db->prepare(
        'UPDATE payment_orders SET provider_checkout_session_id = :session_id, checkout_url = :url WHERE id = :id'
    )->execute(['session_id' => $session['id'], 'url' => $session['url'], 'id' => $orderId]);
    $db->commit();
    jsonResponse(['ok' => true, 'checkout_url' => $session['url'], 'order' => ['id' => $orderId, 'folio' => $folio]]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
