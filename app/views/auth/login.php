<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión – Sistema CDHQ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-indigo-800 to-blue-900 min-h-screen flex items-center justify-center px-4">

<?php if (!empty($_SESSION['flash'])): ?>
<?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 
    <?= $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500' ?> text-white max-w-sm">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div class="w-full max-w-md">
    <!-- Logo / brand -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-xl mb-4">
            <div class="text-indigo-700 font-black text-3xl">CD</div>
        </div>
        <h1 class="text-white text-3xl font-bold tracking-tight">Sistema CDHQ</h1>
        <p class="text-indigo-300 text-sm mt-1">Sistema de Afiliación – Consejo de Derechos Humanos Querétaro</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-gray-800 text-xl font-semibold mb-6">Iniciar sesión</h2>

        <form action="<?= htmlspecialchars($base_url) ?>/login" method="POST" novalidate>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Correo electrónico
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="usuario@cdhq.org"
                    required
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Contraseña
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Ingresar al sistema
            </button>
        </form>
    </div>

    <p class="text-center text-indigo-300 text-xs mt-6">
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
