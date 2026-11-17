<?php
declare(strict_types=1);

require_once __DIR__ . '/api/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$token = strtolower(trim((string) ($_GET['token'] ?? '')));
$credential = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $query = database()->prepare(
        'SELECT u.name, u.profile_photo_token, a.affiliate_number, a.state AS affiliate_state,
                c.credential_number, c.status, c.issued_at, c.expires_at
         FROM credentials c
         JOIN affiliates a ON a.id = c.affiliate_id
         JOIN users u ON u.id = a.user_id
         WHERE c.verification_token = :token
         LIMIT 1'
    );
    $query->execute(['token' => $token]);
    $credential = $query->fetch() ?: null;
    if ($credential && $credential['profile_photo_token']) {
        // Ruta relativa: evita bloqueo CSP cuando el sitio alterna entre
        // cdhq.digital y www.cdhq.digital.
        $credential['profile_photo_url'] = 'profile-photo.php?token=' . rawurlencode($credential['profile_photo_token']);
    }
}

if (!$credential) {
    http_response_code(404);
}

function escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function displayDate(?string $value): string
{
    if (!$value) {
        return 'No disponible';
    }
    return (new DateTimeImmutable($value))->format('d/m/Y');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
}

$status = $credential['status'] ?? 'NO_ENCONTRADA';
if ($credential && $status === 'VIGENTE' && new DateTimeImmutable($credential['expires_at']) < new DateTimeImmutable('today')) {
    $status = 'VENCIDA';
}
$isValid = $status === 'VIGENTE';
$statusLabels = [
    'VIGENTE' => 'Credencial vigente',
    'VENCIDA' => 'Credencial vencida',
    'SUSPENDIDA' => 'Credencial suspendida',
    'REVOCADA' => 'Credencial revocada',
    'NO_ENCONTRADA' => 'Credencial no encontrada',
];
$statusLabel = $statusLabels[$status] ?? 'Estado no reconocido';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= escape($statusLabel) ?> · CDHQ</title>
  <style>
    :root{--navy:#073b4c;--teal:#0f6b7b;--cyan:#1597b8;--green:#15803d;--red:#b42318;--ink:#101828;--muted:#667085;--line:#d0d5dd;--paper:#fff;--bg:#eef4f6}
    *{box-sizing:border-box}body{margin:0;background:linear-gradient(145deg,#e8f2f5,#f8fafc 48%,#dcecf0);color:var(--ink);font-family:Inter,Arial,sans-serif;min-height:100vh;padding:24px}
    main{width:min(100%,760px);margin:auto}.verified{display:flex;align-items:center;justify-content:center;gap:9px;margin:0 auto 16px;padding:11px 16px;border:1px solid #a6f4c5;border-radius:999px;background:#ecfdf3;color:#067647;font-size:14px;font-weight:800;width:max-content;max-width:100%}.verified.invalid{border-color:#fecdca;background:#fef3f2;color:var(--red)}
    .card{overflow:hidden;border:1px solid #c8d8dd;border-radius:24px;background:var(--paper);box-shadow:0 22px 60px #073b4c24}.topline{display:flex;justify-content:space-between;gap:18px;padding:18px 28px;background:var(--navy);color:#fff;font-size:15px;font-weight:900;text-transform:uppercase}.network{padding:8px 24px;background:var(--teal);color:#fff;text-align:center;font-size:13px;letter-spacing:.04em}.brand{padding:28px;background:var(--navy);color:#fff;text-align:center}.logo{font-size:54px;font-weight:900;letter-spacing:-.06em;color:var(--cyan)}.logo b{color:#20b719}.brand p{margin:8px 0 0;font-size:16px;letter-spacing:.02em}
    .identity{display:grid;grid-template-columns:150px 1fr;gap:24px;align-items:center;padding:28px}.portrait{width:150px;aspect-ratio:3/4;display:grid;place-items:center;overflow:hidden;border:4px solid var(--navy);background:#e8f2f5;color:var(--navy);font-size:42px;font-weight:900}.portrait img{width:100%;height:100%;object-fit:cover}.eyebrow{margin:0 0 8px;color:var(--teal);font-size:12px;font-weight:900;letter-spacing:.08em}.name{margin:0;font-size:28px;line-height:1.08;text-transform:uppercase}.role{margin:10px 0 22px;color:var(--muted);font-size:16px}.data{display:grid;grid-template-columns:auto 1fr;gap:8px 16px;margin:0}.data dt{color:var(--muted);font-size:13px}.data dd{margin:0;font-size:14px;font-weight:800;overflow-wrap:anywhere}
    .credential-state{padding:22px 28px;background:var(--navy);color:#fff;text-align:center}.credential-state strong{display:block;font-size:22px;text-transform:uppercase}.credential-state span{display:block;margin-top:6px;color:#b9e4ed;font-size:13px}.notice{padding:24px 28px;color:#475467;font-size:12px;line-height:1.6;text-align:center}.footer{height:20px;background:var(--teal)}.missing{padding:48px 30px;text-align:center}.missing h1{margin:0;color:var(--navy)}.missing p{color:var(--muted);line-height:1.6}
    @media(max-width:560px){body{padding:12px}.card{border-radius:18px}.topline{padding:14px 16px;font-size:12px}.network{font-size:10px}.brand{padding:22px 16px}.logo{font-size:44px}.brand p{font-size:12px}.identity{grid-template-columns:94px 1fr;gap:15px;padding:20px 16px}.portrait{width:94px;font-size:29px}.name{font-size:19px}.role{font-size:13px;margin-bottom:15px}.data{display:block}.data dt{margin-top:7px}.data dd{font-size:13px}.notice{padding:20px 16px}}
  </style>
</head>
<body>
<main>
  <div class="verified<?= $isValid ? '' : ' invalid' ?>" role="status"><?= $isValid ? '✓' : '!' ?> <?= escape($statusLabel) ?></div>
  <article class="card">
    <?php if ($credential): ?>
      <div class="topline"><span>Folio <?= escape($credential['credential_number']) ?></span><span>Expira <?= escape(displayDate($credential['expires_at'])) ?></span></div>
      <div class="network">RED NACIONAL DE DEFENSORES DE DERECHOS HUMANOS</div>
      <header class="brand"><div class="logo">CDH<b>Q</b></div><p>CONSEJO DE DERECHOS HUMANOS QUERÉTARO</p></header>
      <section class="identity">
        <div class="portrait" aria-label="Fotografía de la persona acreditada"><?php if (!empty($credential['profile_photo_url'])): ?><img src="<?= escape($credential['profile_photo_url']) ?>" alt="Fotografía de <?= escape($credential['name']) ?>"><?php else: ?><?= escape(initials($credential['name'])) ?><?php endif; ?></div>
        <div>
          <p class="eyebrow">ESTE ORGANISMO ACREDITA A</p>
          <h1 class="name"><?= escape($credential['name']) ?></h1>
          <p class="role">Defensor afiliado</p>
          <dl class="data">
            <dt>Número de afiliación</dt><dd><?= escape($credential['affiliate_number']) ?></dd>
            <dt>Emisión</dt><dd><?= escape(displayDate($credential['issued_at'])) ?></dd>
            <dt>Vigencia</dt><dd><?= escape(displayDate($credential['expires_at'])) ?></dd>
          </dl>
        </div>
      </section>
      <section class="credential-state"><strong><?= escape($statusLabel) ?></strong><span>Validación consultada directamente en el registro de CDHQ</span></section>
      <p class="notice">El portador de esta credencial no es servidor público ni depende de institución oficial alguna. La credencial acredita una afiliación civil interna y no confiere autoridad pública.</p>
      <div class="footer"></div>
    <?php else: ?>
      <section class="missing"><h1>Credencial no encontrada</h1><p>El código no corresponde a una credencial registrada por CDHQ o el enlace está incompleto.</p></section>
    <?php endif; ?>
  </article>
</main>
</body>
</html>
