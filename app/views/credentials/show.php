<?php
$fullName  = trim($credential['first_name'] . ' ' . $credential['last_name']);
$isValid   = $credential['status'] === 'VIGENTE' && strtotime($credential['expires_at']) >= time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e85a5">
    <meta name="robots" content="noindex, nofollow">
    <title>Credencial – <?= e($fullName) ?></title>
    <link rel="icon" href="<?= SITE_URL ?>/CDHQ%20VERTICAL.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            brand: { 50:'#f0fbfd',100:'#dbf3fa',200:'#b6e7f2',300:'#82d5e8',400:'#3fb5d3',500:'#0e85a5',600:'#0c7290',700:'#0e5c75',800:'#124b60',900:'#143f51' },
            leaf:  { 50:'#eefbee',100:'#d7f6d7',200:'#aeeaae',300:'#75d975',400:'#33c433',500:'#00ac00',600:'#009300',700:'#047604',800:'#085d08',900:'#094c09' }
        } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .credential-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 py-10" style="background:linear-gradient(165deg,#ffffff 0%,#f2fbfd 50%,#edfaf1 100%);">

<div class="no-print mb-6 flex flex-wrap gap-3 justify-center">
    <a href="<?= BASE_URL ?>/credentials" class="text-sm text-brand-600 hover:text-brand-800 font-medium inline-flex items-center gap-2">
        <span aria-hidden="true">&larr;</span> Volver a credenciales
    </a>
    <button onclick="window.print()"
            class="text-sm bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-full font-semibold transition-colors shadow-lg shadow-brand-500/25">
        Imprimir credencial
    </button>
</div>

<div class="credential-card w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
    <div class="bg-brand-900 px-5 py-3 flex items-center justify-between text-white">
        <span class="text-xs font-bold tracking-wide">FOLIO: <?= e($credential['affiliate_number']) ?></span>
        <span class="text-xs font-bold tracking-wide">EXPIRA: <?= e(date('d/m/Y', strtotime($credential['expires_at']))) ?></span>
    </div>

    <div class="bg-brand-700 px-5 py-1.5 text-center">
        <span class="text-[10px] text-white/90 tracking-wider font-medium">RED NACIONAL DE DEFENSORES DE DERECHOS HUMANOS</span>
    </div>

    <div class="bg-brand-900 px-5 pt-5 pb-4 text-center">
        <img src="<?= SITE_URL ?>/CDHQ%20HORIZONTAL%20B.png" alt="CDHQ" class="h-14 w-auto mx-auto">
        <p class="text-white text-[11px] tracking-wide mt-3 font-medium">CONSEJO DE DERECHOS HUMANOS QUERÉTARO</p>
    </div>

    <div class="bg-white px-5 py-4 flex items-start gap-4">
        <div class="w-24 h-28 shrink-0 rounded-lg bg-brand-50 border-2 border-brand-100 flex items-center justify-center text-brand-500 font-bold text-4xl">
            <?= e(mb_strtoupper(mb_substr($credential['first_name'], 0, 1) . mb_substr($credential['last_name'], 0, 1))) ?>
        </div>
        <div class="flex-1 min-w-0 text-[11px] leading-snug text-slate-700 space-y-1.5">
            <div><span class="font-bold text-slate-800">Instrumento Público:</span><br><span class="font-mono">30,397</span></div>
            <div><span class="font-bold text-slate-800">Registro Federal de Causantes:</span><br><span class="font-mono">CDH220818EG2</span></div>
            <div><span class="font-bold text-slate-800">Expediente:</span><br><span class="font-mono">919-2022</span></div>
            <div><span class="font-bold text-slate-800">Clave Sría. de Economía:</span><br><span class="font-mono break-all">A202205311617095887</span></div>
        </div>
    </div>

    <div class="bg-brand-800 px-5 py-4 text-white">
        <p class="text-[11px] font-semibold tracking-wide text-white/80">ESTE ORGANISMO ACREDITA:</p>
        <p class="text-base font-bold mt-1.5 uppercase leading-tight border-b-2 border-white/40 pb-2"><?= e($fullName) ?></p>
        <p class="text-[11px] font-semibold mt-2 uppercase leading-snug">
            Defensor de Derechos Humanos<?= !empty($credential['plan_name']) ? ' · ' . e($credential['plan_name']) : '' ?>
        </p>
        <p class="text-[10px] text-white/75 mt-1.5">
            Credencial <?= e($credential['credential_number']) ?>
        </p>
        <span class="inline-flex items-center gap-1 mt-3 text-[10px] font-bold px-2.5 py-1 rounded-full <?= $isValid ? 'bg-leaf-500 text-white' : 'bg-white/25 text-white' ?>">
            <?= $isValid ? 'CREDENCIAL VIGENTE' : e($credential['status']) ?>
        </span>
    </div>

    <div class="bg-white px-5 py-4 flex items-center gap-4">
        <div class="shrink-0">
            <canvas id="qrCanvas" width="104" height="104" class="rounded border border-slate-200"></canvas>
            <noscript><span class="block w-[104px] text-[9px] text-slate-500 text-center">Activa JavaScript para ver el QR</span></noscript>
        </div>
        <div class="min-w-0">
            <p class="font-bold text-brand-700 text-sm">www.cdhq.digital</p>
            <p class="text-[10px] text-slate-500 mt-1 leading-snug">Escanea el código para verificar la autenticidad de esta credencial en línea.</p>
            <p class="text-[9px] text-slate-400 mt-1.5 break-all font-mono"><?= e($verificationUrl) ?></p>
        </div>
    </div>

    <div class="bg-slate-50 border-t border-slate-200 px-5 py-3">
        <p class="text-[9px] text-slate-600 leading-relaxed text-justify">
            EL PORTADOR DE ESTA CREDENCIAL NO ES UN SERVIDOR PÚBLICO NI DEPENDE DE INSTITUCIÓN OFICIAL ALGUNA;
            SU LABOR ES ESTRICTAMENTE APOYO VOLUNTARIO A FAVOR DE LA SOCIEDAD MEXICANA Y DE ESTE ORGANISMO CIVIL.
            EL USO INDEBIDO DE ESTA CREDENCIAL SERÁ SANCIONADO CONFORME AL DERECHO.
        </p>
    </div>

    <div class="bg-brand-700 px-5 py-2 text-center">
        <p class="text-[9px] text-white/80">Emitida el <?= e(date('d/m/Y', strtotime($credential['issued_at']))) ?> · Sistema CDHQ</p>
    </div>
</div>

<!-- Suspender / revocar -->
<div class="no-print w-full max-w-sm mt-6 bg-white rounded-2xl border border-slate-200 p-5">
    <h3 class="font-bold text-slate-800 mb-1">Cambiar estado</h3>
    <p class="text-xs text-slate-400 mb-4">La credencial nunca se borra: cambia de estado y el motivo queda en la bitácora.</p>
    <form method="POST" action="<?= BASE_URL ?>/credentials/<?= (int) $credential['id'] ?>/status" class="space-y-3"
          onsubmit="return confirm('¿Confirmas el cambio de estado de esta credencial?');">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
        <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <?php foreach (Credential::ESTADOS as $estado): ?>
            <option value="<?= $estado ?>" <?= $credential['status'] === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="reason" placeholder="Motivo (obligatorio al suspender o revocar)"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
            Guardar estado
        </button>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script>
    // El QR se genera en el navegador: la API de Google Charts que se usaba
    // antes fue dada de baja y devolvía una imagen rota.
    (function () {
        var url = <?= json_encode($verificationUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var canvas = document.getElementById('qrCanvas');
        if (!canvas || typeof QRious === 'undefined') return;
        new QRious({ element: canvas, value: url, size: 104, level: 'M',
                     background: '#ffffff', foreground: '#143f51' });
    })();
</script>
</body>
</html>
