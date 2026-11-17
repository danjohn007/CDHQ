<?php
declare(strict_types=1);

function stripeSettings(): array { return serverConfig()['stripe'] ?? []; }

function stripeIsConfigured(): bool
{
    $settings = stripeSettings();
    return str_starts_with(trim((string) ($settings['secret_key'] ?? '')), 'sk_test_')
        && str_starts_with(trim((string) ($settings['webhook_secret'] ?? '')), 'whsec_');
}

function requireStripeConfiguration(): array
{
    if (!stripeIsConfigured()) {
        jsonResponse(['ok' => false, 'error' => 'STRIPE_NOT_CONFIGURED', 'message' => 'Stripe todavía no está configurado en modo de prueba en el servidor.'], 503);
    }
    if (!function_exists('curl_init')) {
        jsonResponse(['ok' => false, 'error' => 'CURL_NOT_AVAILABLE', 'message' => 'El servidor PHP necesita la extensión cURL para comunicarse con Stripe.'], 503);
    }
    return stripeSettings();
}

function stripeRequest(string $method, string $path, array $fields = []): array
{
    $settings = requireStripeConfiguration();
    $handle = curl_init('https://api.stripe.com' . $path);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $settings['secret_key'], 'Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($fields) curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($fields));
    $raw = curl_exec($handle);
    $curlError = curl_error($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    if ($raw === false || $curlError !== '') {
        error_log('Stripe connection error: ' . $curlError);
        jsonResponse(['ok' => false, 'error' => 'STRIPE_UNAVAILABLE', 'message' => 'No fue posible conectar con Stripe.'], 502);
    }
    $payload = json_decode($raw, true);
    if (!is_array($payload) || $status < 200 || $status >= 300) {
        error_log('Stripe API error HTTP ' . $status . ': ' . $raw);
        $message = is_array($payload) ? ($payload['error']['message'] ?? null) : null;
        jsonResponse(['ok' => false, 'error' => 'STRIPE_REJECTED_REQUEST', 'message' => $message ?: 'Stripe rechazó la operación.'], 502);
    }
    return $payload;
}

function verifyStripeWebhook(string $payload, string $header, string $secret, int $tolerance = 300): bool
{
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        $pair = explode('=', trim($part), 2);
        if (count($pair) !== 2) continue;
        if ($pair[0] === 't') $timestamp = filter_var($pair[1], FILTER_VALIDATE_INT);
        if ($pair[0] === 'v1') $signatures[] = $pair[1];
    }
    if (!$timestamp || !$signatures || abs(time() - (int) $timestamp) > $tolerance) return false;
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) if (hash_equals($expected, $signature)) return true;
    return false;
}
