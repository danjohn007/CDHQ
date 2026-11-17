<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/stripe.php';

requireMethod('POST');
$settings = stripeSettings();
$secret = trim((string) ($settings['webhook_secret'] ?? ''));
$payload = file_get_contents('php://input') ?: '';
$signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
if (!str_starts_with($secret, 'whsec_') || !verifyStripeWebhook($payload, $signature, $secret)) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_STRIPE_SIGNATURE'], 400);
}
$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['type'])) jsonResponse(['ok' => false, 'error' => 'INVALID_STRIPE_EVENT'], 400);
if (($event['livemode'] ?? true) !== false) jsonResponse(['ok' => false, 'error' => 'LIVE_PAYMENTS_DISABLED'], 400);

$db = database();
$db->beginTransaction();
try {
    $insertEvent = $db->prepare(
        'INSERT IGNORE INTO stripe_webhook_events (stripe_event_id, event_type, payload_sha256, processed_at)
         VALUES (:event_id, :event_type, :hash, NOW())'
    );
    $insertEvent->execute(['event_id' => $event['id'], 'event_type' => $event['type'], 'hash' => hash('sha256', $payload)]);
    if (!$insertEvent->rowCount()) {
        $db->commit();
        jsonResponse(['ok' => true, 'duplicate' => true]);
    }
    $session = $event['data']['object'] ?? [];
    if ($event['type'] === 'checkout.session.completed' && ($session['payment_status'] ?? '') === 'paid') {
        $orderId = filter_var($session['metadata']['payment_order_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$orderId) throw new RuntimeException('STRIPE_ORDER_METADATA_MISSING');
        $query = $db->prepare('SELECT * FROM payment_orders WHERE id = :id FOR UPDATE');
        $query->execute(['id' => $orderId]);
        $order = $query->fetch();
        if (!$order || $order['provider_checkout_session_id'] !== ($session['id'] ?? null)) throw new RuntimeException('STRIPE_ORDER_MISMATCH');
        $expectedCents = (int) round(((float) $order['amount_snapshot']) * 100);
        if ((int) ($session['amount_total'] ?? -1) !== $expectedCents || strtolower((string) ($session['currency'] ?? '')) !== strtolower($order['currency'])) {
            throw new RuntimeException('STRIPE_AMOUNT_MISMATCH');
        }
        if ($order['status'] !== 'PAGADA') {
            $paymentIntent = (string) ($session['payment_intent'] ?? $session['id']);
            $db->prepare(
                'INSERT INTO payments (affiliate_id, amount, type, method, reference, payment_date, notes)
                 VALUES (:affiliate_id, :amount, "MEMBRESIA", "TARJETA", :reference, CURDATE(), :notes)'
            )->execute([
                'affiliate_id' => $order['affiliate_id'], 'amount' => $order['amount_snapshot'], 'reference' => $paymentIntent,
                'notes' => 'Pago confirmado automáticamente por Stripe Checkout. Orden ' . $order['folio'],
            ]);
            $paymentId = (int) $db->lastInsertId();
            $start = new DateTimeImmutable('today');
            $latest = $db->prepare(
                'SELECT end_date FROM subscriptions WHERE affiliate_id = :affiliate_id AND end_date >= CURDATE()
                 ORDER BY end_date DESC LIMIT 1 FOR UPDATE'
            );
            $latest->execute(['affiliate_id' => $order['affiliate_id']]);
            if ($latestEnd = $latest->fetchColumn()) $start = (new DateTimeImmutable($latestEnd))->modify('+1 day');
            $days = max(1, (int) $order['duration_days_snapshot']);
            $end = $start->modify('+' . ($days - 1) . ' days');
            $db->prepare(
                'INSERT INTO subscriptions (affiliate_id, plan_id, start_date, end_date, status)
                 VALUES (:affiliate_id, :plan_id, :start_date, :end_date, "ACTIVA")'
            )->execute([
                'affiliate_id' => $order['affiliate_id'], 'plan_id' => $order['plan_id'],
                'start_date' => $start->format('Y-m-d'), 'end_date' => $end->format('Y-m-d'),
            ]);
            $subscriptionId = (int) $db->lastInsertId();
            $db->prepare('UPDATE credentials SET status = "VENCIDA" WHERE affiliate_id = :affiliate_id AND status = "VIGENTE"')
                ->execute(['affiliate_id' => $order['affiliate_id']]);
            $db->prepare(
                'INSERT INTO credentials
                 (affiliate_id, subscription_id, credential_number, verification_token, issued_at, expires_at, status)
                 VALUES (:affiliate_id, :subscription_id, :number, :token, NOW(), :expires_at, "VIGENTE")'
            )->execute([
                'affiliate_id' => $order['affiliate_id'], 'subscription_id' => $subscriptionId,
                'number' => sprintf('CDHQ-DH-%s-%09d', date('Y'), $orderId), 'token' => bin2hex(random_bytes(32)),
                'expires_at' => $end->format('Y-m-d 23:59:59'),
            ]);
            $credentialId = (int) $db->lastInsertId();
            $db->prepare(
                'INSERT INTO physical_credential_orders
                 (credential_id, payment_order_id, affiliate_id, source, status, delivery_method)
                 VALUES (:credential_id, :payment_order_id, :affiliate_id, "PAGO", "PENDIENTE_DATOS", "POR_DEFINIR")'
            )->execute([
                'credential_id' => $credentialId, 'payment_order_id' => $orderId,
                'affiliate_id' => $order['affiliate_id'],
            ]);
            $db->prepare('UPDATE affiliates SET state = "ACTIVO" WHERE id = :id')->execute(['id' => $order['affiliate_id']]);
            if ($order['application_id']) {
                $db->prepare('UPDATE defender_applications SET status = "ACREDITADA" WHERE id = :id')->execute(['id' => $order['application_id']]);
                $db->prepare(
                    'UPDATE credential_requests SET status = "CANCELADA", resolution_notes = "Afiliación activada mediante pago."
                     WHERE application_id = :application_id AND status IN ("PENDIENTE", "EN_REVISION")'
                )->execute(['application_id' => $order['application_id']]);
            }
            $db->prepare(
                'UPDATE payment_orders SET status = "PAGADA", confirmed_payment_id = :payment_id,
                 provider_payment_intent_id = :intent, paid_at = NOW(), validated_at = NOW() WHERE id = :id'
            )->execute(['payment_id' => $paymentId, 'intent' => $paymentIntent, 'id' => $orderId]);
        }
    } elseif ($event['type'] === 'checkout.session.expired' && !empty($session['id'])) {
        $db->prepare(
            'UPDATE payment_orders SET status = "CANCELADA" WHERE provider_checkout_session_id = :session_id AND status = "PENDIENTE"'
        )->execute(['session_id' => $session['id']]);
    }
    $db->commit();
    jsonResponse(['ok' => true]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Stripe webhook processing failed: ' . (string) $error);
    jsonResponse(['ok' => false, 'error' => 'WEBHOOK_PROCESSING_FAILED'], 500);
}
