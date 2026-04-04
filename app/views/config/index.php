<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<!-- Tabs -->
<div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-0">
    <?php
    $tabs = [
        'general'  => '⚙️ General',
        'email'    => '📧 Email / SMTP',
        'payments' => '💳 Pagos',
        'iot'      => '🔧 IoT / Dispositivos',
        'advanced' => '🔬 Avanzado',
        'logs'     => '📋 Bitácora',
    ];
    $activeTab = $_GET['tab'] ?? 'general';
    foreach ($tabs as $key => $label):
    ?>
    <a href="?tab=<?= $key ?>"
       class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors -mb-px
              <?= $activeTab === $key ? 'bg-white border border-b-white border-gray-200 text-indigo-600' : 'text-gray-500 hover:text-gray-700' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="<?= $base_url ?>/config">
<input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">

<?php
// Helper to render a config field
function configField(string $key, string $label, array $allConfig, string $type = 'text', bool $required = false): void {
    $groups = array_merge(...array_values($allConfig));
    $val = $groups[$key]['value'] ?? '';
    $id  = htmlspecialchars($key);
    $v   = htmlspecialchars((string)$val);
    $req = $required ? 'required' : '';
    $inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none';
    echo "<div class='mb-4'>
        <label for='{$id}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}</label>
        <input type='{$type}' id='{$id}' name='{$id}' value='{$v}' {$req} class='{$inputClass}'>
    </div>";
}
?>

<?php if ($activeTab === 'general'): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Configuración General</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('site_name',    'Nombre del sitio',      $config) ?>
        <?php configField('logo_url',     'URL del logotipo',      $config) ?>
        <?php configField('contact_phone','Teléfono de contacto',  $config, 'tel') ?>
        <?php configField('contact_email','Correo de contacto',    $config, 'email') ?>
        <?php configField('office_hours', 'Horario de atención',   $config) ?>
        <?php configField('primary_color','Color primario (hex)',   $config, 'color') ?>
    </div>
</div>

<?php elseif ($activeTab === 'email'): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Configuración de Correo SMTP</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('smtp_host',     'Servidor SMTP',      $config) ?>
        <?php configField('smtp_port',     'Puerto SMTP',        $config, 'number') ?>
        <?php configField('smtp_user',     'Usuario SMTP',       $config, 'email') ?>
        <?php configField('smtp_password', 'Contraseña SMTP',    $config, 'password') ?>
        <?php configField('smtp_from',     'Correo remitente',   $config, 'email') ?>
        <?php configField('smtp_from_name','Nombre remitente',   $config) ?>
    </div>
</div>

<?php elseif ($activeTab === 'payments'): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Configuración de Pagos (PayPal)</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('paypal_client_id',    'PayPal Client ID',     $config) ?>
        <?php configField('paypal_client_secret','PayPal Client Secret', $config, 'password') ?>
        <?php configField('paypal_mode',         'Modo PayPal (sandbox/live)', $config) ?>
        <?php configField('currency',            'Moneda (MXN, USD…)',   $config) ?>
    </div>
    <h3 class="text-sm font-semibold text-gray-700 mt-6 mb-4">WhatsApp Chatbot</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('whatsapp_token',  'WhatsApp API Token',  $config, 'password') ?>
        <?php configField('whatsapp_number', 'Número WhatsApp',     $config, 'tel') ?>
    </div>
</div>

<?php elseif ($activeTab === 'iot'): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Dispositivos IoT – Shelly Cloud</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('shelly_server',  'Servidor Shelly Cloud',  $config) ?>
        <?php configField('shelly_auth_key','Shelly Auth Key',         $config, 'password') ?>
    </div>
    <h3 class="text-sm font-semibold text-gray-700 mt-6 mb-4">HikVision CCTV</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('hikvision_ip',       'IP HikVision',       $config) ?>
        <?php configField('hikvision_port',     'Puerto',             $config, 'number') ?>
        <?php configField('hikvision_user',     'Usuario',            $config) ?>
        <?php configField('hikvision_password', 'Contraseña',         $config, 'password') ?>
    </div>
    <h3 class="text-sm font-semibold text-gray-700 mt-6 mb-4">GPS Tracker</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('gps_api_url', 'URL API GPS',  $config) ?>
        <?php configField('gps_api_key', 'API Key GPS',  $config, 'password') ?>
    </div>
</div>

<?php elseif ($activeTab === 'advanced'): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Configuración Avanzada</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php configField('qr_api_url',      'URL API QR Code',        $config) ?>
        <?php configField('session_lifetime','Tiempo de sesión (seg)', $config, 'number') ?>
        <?php configField('items_per_page',  'Elementos por página',   $config, 'number') ?>
    </div>
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800 font-medium">⚠️ Credenciales de base de datos</p>
        <p class="text-xs text-yellow-600 mt-1">Edita el archivo <code class="font-mono">config/config.php</code> directamente para cambiar las credenciales de la base de datos.</p>
    </div>
</div>

<?php elseif ($activeTab === 'logs'): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Bitácora de Actividad (últimas 50 entradas)</h3>
    <div class="overflow-x-auto">
    <table class="min-w-full text-xs divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Fecha</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Usuario</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Acción</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Entidad</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Detalles</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($activityLog as $log): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-1.5 text-gray-500"><?= htmlspecialchars($log['created_at']) ?></td>
                <td class="px-3 py-1.5 font-medium text-gray-700"><?= htmlspecialchars($log['user_name'] ?? '—') ?></td>
                <td class="px-3 py-1.5">
                    <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 font-medium"><?= htmlspecialchars($log['action']) ?></span>
                </td>
                <td class="px-3 py-1.5 text-gray-600"><?= htmlspecialchars($log['entity']) ?></td>
                <td class="px-3 py-1.5 text-gray-500 max-w-xs truncate"><?= htmlspecialchars($log['details']) ?></td>
                <td class="px-3 py-1.5 font-mono text-gray-400"><?= htmlspecialchars($log['ip']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($activityLog)): ?>
            <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">Sin registros.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<!-- No save button for logs tab -->
<?php endif; ?>

<?php if ($activeTab !== 'logs'): ?>
<div class="mt-6 flex gap-3">
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
        💾 Guardar Configuración
    </button>
    <a href="<?= $base_url ?>/config?tab=<?= htmlspecialchars($activeTab) ?>"
       class="border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium px-6 py-2 rounded-lg text-sm transition-colors">
        Cancelar
    </a>
</div>
<?php endif; ?>

</form>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
