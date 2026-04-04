<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credencial – <?= htmlspecialchars($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .credential-card { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

<div class="no-print mb-4 flex gap-3">
    <a href="<?= htmlspecialchars($base_url) ?>/affiliates/<?= $affiliate['id'] ?>"
       class="text-sm text-indigo-600 hover:text-indigo-800">← Volver al perfil</a>
    <button onclick="window.print()"
            class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
        🖨️ Imprimir Credencial
    </button>
</div>

<!-- Credential Card -->
<div class="credential-card w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 px-6 py-4 text-white">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-indigo-700 font-black text-lg">CD</span>
            </div>
            <div>
                <p class="font-bold text-sm leading-tight">Consejo de Derechos Humanos</p>
                <p class="text-indigo-200 text-xs leading-tight">de Querétaro A.C.</p>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="px-6 py-5">
        <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div class="w-20 h-20 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-3xl flex-shrink-0">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($affiliate['first_name'], 0, 1))) ?>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-900 leading-tight">
                    <?= htmlspecialchars($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?>
                </h2>
                <p class="text-xs font-mono text-indigo-600 font-semibold mt-0.5">
                    <?= htmlspecialchars($affiliate['affiliate_number']) ?>
                </p>
                <?php if ($activeSub): ?>
                <div class="mt-2 inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">
                    ✓ AFILIADO ACTIVO
                </div>
                <?php else: ?>
                <div class="mt-2 inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-medium">
                    <?= htmlspecialchars($affiliate['state']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($activeSub): ?>
        <div class="mt-4 p-3 bg-gray-50 rounded-xl text-xs space-y-1 text-gray-600">
            <div class="flex justify-between">
                <span class="font-medium">Plan:</span>
                <span><?= htmlspecialchars($activeSub['plan_name']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Vigencia:</span>
                <span><?= htmlspecialchars($activeSub['start_date']) ?> al <?= htmlspecialchars($activeSub['end_date']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($affiliate['email']): ?>
        <p class="mt-3 text-xs text-gray-500"><?= htmlspecialchars($affiliate['email']) ?></p>
        <?php endif; ?>
    </div>

    <!-- QR Code -->
    <div class="border-t border-gray-100 px-6 py-4 flex items-center gap-4">
        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code" class="w-24 h-24 rounded-lg border border-gray-200">
        <div class="text-xs text-gray-500">
            <p class="font-medium text-gray-700 mb-1">Código de Verificación</p>
            <p>Escanea este código para verificar la autenticidad de esta credencial.</p>
            <p class="mt-2 text-indigo-600 break-all text-[10px]"><?= htmlspecialchars($base_url) ?>/affiliates/<?= $affiliate['id'] ?></p>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-indigo-50 px-6 py-3 text-center">
        <p class="text-xs text-indigo-500">Credencial válida con membresía vigente · Sistema CDHQ</p>
        <p class="text-xs text-gray-400 mt-0.5">Emitida el <?= date('d/m/Y') ?></p>
    </div>
</div>

</body>
</html>
