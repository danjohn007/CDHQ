<?php
/**
 * Página PÚBLICA de verificación de credenciales (destino del código QR).
 *
 * Muestra únicamente los datos mínimos para acreditar la credencial:
 * folio, nombre, estado y vigencia. Nunca correo, teléfono ni domicilio.
 */
$found    = !empty($credential);
$dbError  = !empty($dbError);
$fullName = $found ? trim($credential['first_name'] . ' ' . $credential['last_name']) : '';

if ($dbError) {
    $statusLabel = 'No se pudo verificar en este momento';
    $statusText  = 'El servicio de verificación no está disponible temporalmente. Vuelve a intentarlo en unos minutos o contacta al Consejo.';
    $statusIcon  = '!';
    $statusBg    = 'bg-slate-400';
} elseif (!$found) {
    $statusLabel = 'Credencial no encontrada';
    $statusText  = 'El código escaneado no corresponde a ninguna credencial emitida por el Consejo.';
    $statusIcon  = '&#10005;';
    $statusBg    = 'bg-red-500';
} elseif ($isValid) {
    $statusLabel = 'Credencial vigente';
    $statusText  = 'Esta credencial fue emitida por el Consejo de Derechos Humanos Querétaro y se encuentra vigente.';
    $statusIcon  = '&#10003;';
    $statusBg    = 'bg-leaf-500';
} else {
    $statusLabel = 'Credencial sin vigencia';
    $statusText  = 'La persona está registrada ante el Consejo, pero esta credencial no se encuentra vigente en este momento.';
    $statusIcon  = '!';
    $statusBg    = 'bg-amber-500';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0e85a5">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Verificación pública de credenciales del Consejo de Derechos Humanos Querétaro.">
    <title>Verificación de credencial | CDHQ</title>
    <link rel="icon" href="<?= SITE_URL ?>/CDHQ%20VERTICAL.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            brand: { 50:'#f0fbfd',100:'#dbf3fa',200:'#b6e7f2',300:'#82d5e8',400:'#3fb5d3',500:'#0e85a5',600:'#0c7290',700:'#0e5c75',800:'#124b60',900:'#143f51' },
            leaf:  { 50:'#eefbee',100:'#d7f6d7',200:'#aeeaae',300:'#75d975',400:'#33c433',500:'#00ac00',600:'#009300',700:'#047604',800:'#085d08',900:'#094c09' }
        } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; } body { overflow-x: hidden; }</style>
</head>
<body class="min-h-screen flex flex-col" style="background:linear-gradient(165deg,#ffffff 0%,#f2fbfd 45%,#edfaf1 100%);">

<header class="bg-white/90 backdrop-blur-md border-b border-brand-100 shadow-[0_2px_18px_-12px_rgba(14,133,165,0.6)]">
    <div class="container mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
        <a href="<?= SITE_URL ?>/" aria-label="CDHQ · Inicio">
            <img src="<?= SITE_URL ?>/CDHQ%20HORIZONTAL.png" alt="Consejo de Derechos Humanos Querétaro" class="h-9 sm:h-11 w-auto">
        </a>
        <span class="text-xs sm:text-sm text-slate-500 font-medium text-right">Verificación de credenciales</span>
    </div>
</header>

<main class="flex-1 flex items-center justify-center px-4 py-10 sm:py-14">
    <div class="w-full max-w-lg">
        <div class="bg-white rounded-3xl border border-brand-100 shadow-[0_30px_70px_-40px_rgba(14,133,165,0.65)] overflow-hidden">

            <div class="px-7 sm:px-9 pt-9 pb-7 text-center border-b border-slate-100">
                <div class="w-20 h-20 mx-auto rounded-full <?= $statusBg ?> text-white flex items-center justify-center text-4xl font-bold shadow-lg">
                    <?= $statusIcon ?>
                </div>
                <h1 class="text-2xl font-bold text-slate-800 mt-5"><?= e($statusLabel) ?></h1>
                <p class="text-slate-500 text-sm mt-3 leading-relaxed"><?= e($statusText) ?></p>
            </div>

            <?php if ($found): ?>
            <div class="px-7 sm:px-9 py-7">
                <dl class="space-y-4">
                    <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-4">
                        <dt class="text-sm font-medium text-slate-500">Nombre</dt>
                        <dd class="text-sm font-bold text-slate-800 text-right"><?= e($fullName) ?></dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-4">
                        <dt class="text-sm font-medium text-slate-500">Folio de afiliado</dt>
                        <dd class="text-sm font-mono font-bold text-slate-800"><?= e($credential['affiliate_number']) ?></dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-4">
                        <dt class="text-sm font-medium text-slate-500">Credencial</dt>
                        <dd class="text-sm font-mono text-slate-700"><?= e($credential['credential_number']) ?></dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-4">
                        <dt class="text-sm font-medium text-slate-500">Situación</dt>
                        <dd><?= estadoBadge($credential['status']) ?></dd>
                    </div>
                    <?php if (!empty($credential['plan_name'])): ?>
                    <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-4">
                        <dt class="text-sm font-medium text-slate-500">Tipo de afiliación</dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right"><?= e($credential['plan_name']) ?></dd>
                    </div>
                    <?php endif; ?>
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt class="text-sm font-medium text-slate-500">Vigencia</dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right">
                            <?= fechaCorta($credential['issued_at']) ?> &ndash; <?= fechaCorta($credential['expires_at']) ?>
                        </dd>
                    </div>
                </dl>
            </div>
            <?php endif; ?>

            <div class="bg-slate-50 border-t border-slate-200 px-7 sm:px-9 py-5">
                <p class="text-[11px] text-slate-600 leading-relaxed text-justify">
                    EL PORTADOR DE ESTA CREDENCIAL <strong>NO ES UN SERVIDOR PÚBLICO</strong> NI DEPENDE DE INSTITUCIÓN
                    OFICIAL ALGUNA; SU LABOR ES ESTRICTAMENTE APOYO VOLUNTARIO A FAVOR DE LA SOCIEDAD MEXICANA Y DE ESTE
                    ORGANISMO CIVIL. EL USO INDEBIDO DE ESTA CREDENCIAL SERÁ SANCIONADO CONFORME AL DERECHO.
                </p>
            </div>

            <div class="px-7 sm:px-9 py-5 text-white" style="background:linear-gradient(135deg,#0e85a5 0%,#0f8f52 100%);">
                <p class="text-[11px] font-semibold tracking-wide text-white/80 mb-2">ORGANISMO EMISOR</p>
                <p class="font-bold text-sm">Consejo de Derechos Humanos Querétaro</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 mt-3 text-[11px] text-white/90">
                    <span>Instrumento Público:</span><span class="font-mono text-right">30,397</span>
                    <span>R.F.C.:</span><span class="font-mono text-right">CDH220818EG2</span>
                    <span>Expediente:</span><span class="font-mono text-right">919-2022</span>
                </div>
            </div>
        </div>

        <div class="mt-7 text-center space-y-4">
            <p class="text-xs text-slate-500">Verificado el <?= date('d/m/Y \a \l\a\s H:i') ?> hrs.</p>
            <p class="text-sm text-slate-600">
                ¿Dudas sobre esta credencial?
                <a href="<?= SITE_URL ?>/contacto.html" class="text-brand-600 font-semibold hover:text-leaf-600 transition-colors">Contacta al Consejo</a>
            </p>
            <a href="<?= SITE_URL ?>/" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white px-6 py-3 rounded-full font-semibold text-sm transition-colors shadow-lg shadow-brand-500/25">
                Ir al sitio del CDHQ
            </a>
        </div>
    </div>
</main>

<footer class="py-6 text-center">
    <p class="text-xs text-slate-400">© <?= date('Y') ?> Consejo de Derechos Humanos Querétaro · Organismo civil independiente</p>
</footer>

</body>
</html>
