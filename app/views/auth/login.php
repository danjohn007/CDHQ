<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e85a5">
    <title>Iniciar Sesión – Sistema CDHQ</title>
    <link rel="icon" href="<?= SITE_URL ?>/CDHQ%20VERTICAL.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                brand: { 50:'#f0fbfd',100:'#dbf3fa',200:'#b6e7f2',300:'#82d5e8',400:'#3fb5d3',500:'#0e85a5',600:'#0c7290',700:'#0e5c75',800:'#124b60',900:'#143f51' },
                leaf:  { 50:'#eefbee',100:'#d7f6d7',200:'#aeeaae',300:'#75d975',400:'#33c433',500:'#00ac00',600:'#009300',700:'#047604',800:'#085d08',900:'#094c09' }
            } } }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .blob { animation: floatBlob 12s ease-in-out infinite; }
        .blob-2 { animation-duration: 15s; animation-delay: -5s; }
        @keyframes floatBlob {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(20px,-28px) scale(1.07); }
        }
        @media (prefers-reduced-motion: reduce) { .blob { animation: none; } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 relative overflow-hidden"
      style="background:linear-gradient(165deg,#ffffff 0%,#f2fbfd 45%,#edfaf1 100%);">

<div class="blob absolute -top-32 -left-24 w-96 h-96 bg-brand-200/50 rounded-full blur-3xl pointer-events-none"></div>
<div class="blob blob-2 absolute -bottom-32 -right-24 w-96 h-96 bg-leaf-200/45 rounded-full blur-3xl pointer-events-none"></div>

<?php if (!empty($_SESSION['flash'])): ?>
<?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="fixed top-4 right-4 px-4 py-3 rounded-xl shadow-lg z-50
    <?= $flash['type'] === 'success' ? 'bg-leaf-500' : 'bg-red-500' ?> text-white max-w-sm text-sm">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div class="w-full max-w-md relative">
    <!-- Logo / brand -->
    <div class="text-center mb-8">
        <img src="<?= SITE_URL ?>/CDHQ%20VERTICAL.png" alt="CDHQ" class="w-36 mx-auto mb-5">
        <h1 class="text-slate-800 text-2xl font-bold tracking-tight">Sistema de Afiliación</h1>
        <p class="text-slate-500 text-sm mt-1">Consejo de Derechos Humanos Querétaro</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white/90 backdrop-blur rounded-2xl shadow-[0_30px_70px_-35px_rgba(14,133,165,0.65)] border border-brand-100 p-8">
        <h2 class="text-slate-800 text-lg font-semibold mb-6">Iniciar sesión</h2>

        <form action="<?= htmlspecialchars($base_url) ?>/login" method="POST" novalidate>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                    Correo electrónico
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none transition"
                    placeholder="usuario@cdhq.org"
                    required
                    autocomplete="email"
                    autofocus
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                    Contraseña
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none transition"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-brand-500 hover:bg-brand-600 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 shadow-lg shadow-brand-500/25"
            >
                Ingresar al sistema
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <a href="<?= SITE_URL ?>/" class="text-sm text-brand-600 hover:text-leaf-600 transition-colors">
                &larr; Volver al sitio público
            </a>
        </div>
    </div>

    <p class="text-center text-slate-400 text-xs mt-6">
        © <?= date('Y') ?> Consejo de Derechos Humanos de Querétaro
    </p>
</div>

<script>
    // Auto-dismiss flash after 5s
    setTimeout(() => {
        const flash = document.querySelector('.fixed.top-4');
        if (flash) flash.style.display = 'none';
    }, 5000);
</script>
</body>
</html>
