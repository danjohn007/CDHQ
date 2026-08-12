<?php
/**
 * Formulario PÚBLICO de verificación manual de credenciales.
 * Respaldo para las credenciales impresas cuyo QR apunta al dominio anterior.
 */
$notFound = !empty($notFound);
$dbError  = !empty($dbError);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0e85a5">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Verifica la autenticidad de una credencial del Consejo de Derechos Humanos Querétaro.">
    <title>Verificar una credencial | CDHQ</title>
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
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl border border-brand-100 shadow-[0_30px_70px_-40px_rgba(14,133,165,0.65)] p-7 sm:p-9">

            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center text-2xl font-bold">
                    &#9906;
                </div>
                <h1 class="text-2xl font-bold text-slate-800 mt-4">Verificar una credencial</h1>
                <p class="text-slate-500 text-sm mt-2.5 leading-relaxed">
                    Escribe el folio impreso en la credencial y el apellido de la persona titular.
                </p>
            </div>

            <?php if ($notFound): ?>
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No encontramos ninguna credencial con ese folio y apellido. Revisa que ambos datos estén escritos
                tal como aparecen en la credencial.
            </div>
            <?php elseif ($dbError): ?>
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                El servicio de verificación no está disponible en este momento. Inténtalo de nuevo en unos minutos.
            </div>
            <?php endif; ?>

            <form method="GET" action="<?= htmlspecialchars(rtrim($base_url, '/')) ?>/verificar" class="mt-7 space-y-5">
                <div>
                    <label for="folio" class="block text-sm font-medium text-slate-700 mb-1.5">Folio de la credencial</label>
                    <input type="text" id="folio" name="folio" required
                           value="<?= htmlspecialchars($folio ?? '') ?>"
                           placeholder="CDHQ-2026-0001"
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none transition font-mono">
                </div>
                <div>
                    <label for="apellido" class="block text-sm font-medium text-slate-700 mb-1.5">Apellido del titular</label>
                    <input type="text" id="apellido" name="apellido" required
                           value="<?= htmlspecialchars($apellido ?? '') ?>"
                           placeholder="Tal como aparece en la credencial"
                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none transition">
                </div>
                <button type="submit"
                        class="w-full bg-brand-500 hover:bg-brand-600 text-white font-semibold py-3 px-5 rounded-full transition-colors shadow-lg shadow-brand-500/25">
                    Verificar credencial
                </button>
            </form>

            <p class="text-xs text-slate-400 text-center mt-6 leading-relaxed">
                Si la credencial tiene un código QR, escanearlo te lleva directamente al resultado.
            </p>
        </div>

        <div class="mt-7 text-center">
            <p class="text-sm text-slate-600">
                ¿Dudas sobre una credencial?
                <a href="<?= SITE_URL ?>/contacto.html" class="text-brand-600 font-semibold hover:text-leaf-600 transition-colors">Contacta al Consejo</a>
            </p>
        </div>
    </div>
</main>

<footer class="py-6 text-center">
    <p class="text-xs text-slate-400">
        © <?= date('Y') ?> Consejo de Derechos Humanos Querétaro · Organismo civil independiente
    </p>
</footer>

</body>
</html>
