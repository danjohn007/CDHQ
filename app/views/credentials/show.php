<?php
$fullName  = trim($affiliate['first_name'] . ' ' . $affiliate['last_name']);
$isActive  = ($affiliate['state'] === 'ACTIVO') && $activeSub;
$expiraTxt = $activeSub ? date('d/m/Y', strtotime($activeSub['end_date'])) : 'SIN VIGENCIA';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e85a5">
    <title>Credencial – <?= htmlspecialchars($fullName) ?></title>
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
    <a href="<?= htmlspecialchars($base_url) ?>/affiliates/<?= (int)$affiliate['id'] ?>"
       class="text-sm text-brand-600 hover:text-brand-800 font-medium inline-flex items-center gap-2">
        <span aria-hidden="true">&larr;</span> Volver al perfil
    </a>
    <button onclick="window.print()"
            class="text-sm bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-full font-semibold transition-colors shadow-lg shadow-brand-500/25">
        Imprimir credencial
    </button>
</div>

<!-- Credencial -->
<div class="credential-card w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">

    <!-- Folio y vigencia -->
    <div class="bg-brand-900 px-5 py-3 flex items-center justify-between text-white">
        <span class="text-xs font-bold tracking-wide">
            FOLIO: <?= htmlspecialchars($affiliate['affiliate_number']) ?>
        </span>
        <span class="text-xs font-bold tracking-wide">
            EXPIRA: <?= htmlspecialchars($expiraTxt) ?>
        </span>
    </div>

    <!-- Red nacional -->
    <div class="bg-brand-700 px-5 py-1.5 text-center">
        <span class="text-[10px] text-white/90 tracking-wider font-medium">
            RED NACIONAL DE DEFENSORES DE DERECHOS HUMANOS
        </span>
    </div>

    <!-- Logotipo -->
    <div class="bg-brand-900 px-5 pt-5 pb-4 text-center">
        <img src="<?= SITE_URL ?>/CDHQ%20HORIZONTAL%20B.png" alt="CDHQ" class="h-14 w-auto mx-auto">
        <p class="text-white text-[11px] tracking-wide mt-3 font-medium">
            CONSEJO DE DERECHOS HUMANOS QUERÉTARO
        </p>
    </div>

    <!-- Foto y datos registrales -->
    <div class="bg-white px-5 py-4 flex items-start gap-4">
        <div class="w-24 h-28 shrink-0 rounded-lg bg-brand-50 border-2 border-brand-100 flex items-center justify-center text-brand-500 font-bold text-4xl">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($affiliate['first_name'], 0, 1) . mb_substr($affiliate['last_name'], 0, 1))) ?>
        </div>
        <div class="flex-1 min-w-0 text-[11px] leading-snug text-slate-700 space-y-1.5">
            <div>
                <span class="font-bold text-slate-800">Instrumento Público:</span><br>
                <span class="font-mono">30,397</span>
            </div>
            <div>
                <span class="font-bold text-slate-800">Registro Federal de Causantes:</span><br>
                <span class="font-mono">CDH220818EG2</span>
            </div>
            <div>
                <span class="font-bold text-slate-800">Expediente:</span><br>
                <span class="font-mono">919-2022</span>
            </div>
            <div>
                <span class="font-bold text-slate-800">Clave Sría. de Economía:</span><br>
                <span class="font-mono break-all">A202205311617095887</span>
            </div>
        </div>
    </div>

    <!-- Acreditación -->
    <div class="bg-brand-800 px-5 py-4 text-white">
        <p class="text-[11px] font-semibold tracking-wide text-white/80">ESTE ORGANISMO ACREDITA:</p>
        <p class="text-base font-bold mt-1.5 uppercase leading-tight border-b-2 border-white/40 pb-2">
            <?= htmlspecialchars($fullName) ?>
        </p>
        <p class="text-[11px] font-semibold mt-2 uppercase leading-snug">
            <?php if ($activeSub): ?>
                Afiliado · <?= htmlspecialchars($activeSub['plan_name']) ?>
            <?php else: ?>
                Estado: <?= htmlspecialchars($affiliate['state']) ?>
            <?php endif; ?>
        </p>
        <?php if ($activeSub): ?>
        <p class="text-[10px] text-white/75 mt-1.5">
            Vigencia: <?= htmlspecialchars(date('d/m/Y', strtotime($activeSub['start_date']))) ?>
            al <?= htmlspecialchars(date('d/m/Y', strtotime($activeSub['end_date']))) ?>
        </p>
        <?php endif; ?>
        <span class="inline-flex items-center gap-1 mt-3 text-[10px] font-bold px-2.5 py-1 rounded-full <?= $isActive ? 'bg-leaf-500 text-white' : 'bg-white/25 text-white' ?>">
            <?= $isActive ? 'CREDENCIAL VIGENTE' : 'SIN VIGENCIA ACTIVA' ?>
        </span>
    </div>

    <!-- QR de verificación -->
    <div class="bg-white px-5 py-4 flex items-center gap-4">
        <div class="shrink-0">
            <canvas id="qrCanvas" width="104" height="104" class="rounded border border-slate-200"></canvas>
            <noscript>
                <span class="block w-[104px] text-[9px] text-slate-500 text-center">Activa JavaScript para ver el código QR</span>
            </noscript>
        </div>
        <div class="min-w-0">
            <p class="font-bold text-brand-700 text-sm">www.cdhq.digital</p>
            <p class="text-[10px] text-slate-500 mt-1 leading-snug">
                Escanea el código para verificar la autenticidad de esta credencial en línea.
            </p>
            <p class="text-[9px] text-slate-400 mt-1.5 break-all font-mono"><?= htmlspecialchars($verificationUrl) ?></p>
        </div>
    </div>

    <!-- Leyenda legal -->
    <div class="bg-slate-50 border-t border-slate-200 px-5 py-3">
        <p class="text-[9px] text-slate-600 leading-relaxed text-justify">
            EL PORTADOR DE ESTA CREDENCIAL NO ES UN SERVIDOR PÚBLICO NI DEPENDE DE INSTITUCIÓN OFICIAL ALGUNA;
            SU LABOR ES ESTRICTAMENTE APOYO VOLUNTARIO A FAVOR DE LA SOCIEDAD MEXICANA Y DE ESTE ORGANISMO CIVIL.
            EL USO INDEBIDO DE ESTA CREDENCIAL SERÁ SANCIONADO CONFORME AL DERECHO.
        </p>
    </div>

    <div class="bg-brand-700 px-5 py-2 text-center">
        <p class="text-[9px] text-white/80">Emitida el <?= date('d/m/Y') ?> · Sistema CDHQ</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script>
    // El QR se genera en el navegador: la API de Google Charts que se usaba
    // antes fue dada de baja y devolvía una imagen rota.
    (function () {
        var url = <?= json_encode($verificationUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var canvas = document.getElementById('qrCanvas');
        if (!canvas || typeof QRious === 'undefined') return;
        new QRious({
            element: canvas,
            value: url,
            size: 104,
            level: 'M',
            background: '#ffffff',
            foreground: '#143f51'
        });
    })();
</script>
</body>
</html>
