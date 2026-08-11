<?php
declare(strict_types=1);

// ─── Bootstrap ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/helpers.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Routes ──────────────────────────────────────────────────────────────────
$router = new Router();

// Auth
$router->get('login',         'AuthController', 'showLogin');
$router->post('login',        'AuthController', 'login');
$router->get('logout',        'AuthController', 'logout');

// Dashboard
$router->get('',              'DashboardController', 'index');
$router->get('dashboard',     'DashboardController', 'index');

// ─── Verificación PÚBLICA de credenciales ────────────────────────────────────
// Destino del código QR impreso. Junto con /login, son las únicas rutas que no
// exigen sesión. Se declaran primero para dejar claro que son la excepción.
$router->get('verificar',                'CredentialsController', 'verifyForm');
$router->get('verificar/:token',         'CredentialsController', 'verify');
$router->get('verify/:token',            'CredentialsController', 'verify');

// ─── Módulos administrativos ─────────────────────────────────────────────────
// Todo lo de abajo exige Auth::requireAdmin() dentro del controlador.

// Solicitudes de apoyo
$router->get('requests',                 'RequestsController', 'index');
$router->get('requests/:id',             'RequestsController', 'show');
$router->post('requests/:id/assign',     'RequestsController', 'assign');
$router->post('requests/:id/status',     'RequestsController', 'changeStatus');
$router->post('requests/:id/note',       'RequestsController', 'addNote');

// Postulaciones de defensores
$router->get('applications',             'ApplicationsController', 'index');
$router->get('applications/:id',         'ApplicationsController', 'show');
$router->post('applications/:id/decide', 'ApplicationsController', 'decide');

// Usuarios y roles
$router->get('users',         'UsersController', 'index');
$router->get('users/create',  'UsersController', 'create');
$router->post('users/create', 'UsersController', 'store');
$router->get('users/:id/edit','UsersController', 'edit');
$router->post('users/:id/edit','UsersController','update');
$router->post('users/:id/toggle','UsersController','toggle');

// Afiliados
$router->get('affiliates',               'AffiliatesController', 'index');
$router->get('affiliates/create',        'AffiliatesController', 'create');
$router->post('affiliates/create',       'AffiliatesController', 'store');
$router->get('affiliates/:id',           'AffiliatesController', 'show');
$router->get('affiliates/:id/edit',      'AffiliatesController', 'edit');
$router->post('affiliates/:id/edit',     'AffiliatesController', 'update');
$router->post('affiliates/:id/status',   'AffiliatesController', 'changeStatus');

// Credenciales
$router->get('credentials',               'CredentialsController', 'index');
$router->get('credentials/:id',           'CredentialsController', 'show');
$router->post('credentials/:id/status',   'CredentialsController', 'changeStatus');

// Planes y precios
$router->get('plans',                    'PlansController', 'index');
$router->post('plans/create',            'PlansController', 'store');
$router->post('plans/:id/edit',          'PlansController', 'update');
$router->post('plans/:id/toggle',        'PlansController', 'toggle');

// Pagos y órdenes de Stripe (sólo consulta)
$router->get('orders',                   'OrdersController', 'index');

// Subscriptions
$router->get('subscriptions',            'SubscriptionsController', 'index');
$router->get('subscriptions/create',     'SubscriptionsController', 'create');
$router->post('subscriptions/create',    'SubscriptionsController', 'store');

// Financial
$router->get('financial',                'FinancialController', 'index');
$router->get('financial/create',         'FinancialController', 'create');
$router->post('financial/create',        'FinancialController', 'store');

// ─── Comunicación con la aplicación móvil ────────────────────────────────────
// Estos dos módulos escriben en tablas que la app lee directamente
// (`content_posts` y `notifications`): lo que se publica aquí llega al teléfono
// sin ningún paso de sincronización.

// Noticias y eventos → pestaña «Novedades» y carrusel de inicio de la app.
// `content/create` va antes que `content/:id/...` porque el router resuelve por
// orden de registro y `:id` también casaría con la palabra «create».
$router->get('content',                   'ContentController', 'index');
$router->get('content/create',            'ContentController', 'create');
$router->post('content/create',           'ContentController', 'store');
$router->get('content/:id/edit',          'ContentController', 'edit');
$router->post('content/:id/edit',         'ContentController', 'update');
$router->post('content/:id/status',       'ContentController', 'changeStatus');
$router->post('content/:id/featured',     'ContentController', 'toggleFeatured');
$router->post('content/:id/image/delete', 'ContentController', 'removeImage');
$router->post('content/:id/archive',      'ContentController', 'archive');

// Avisos dirigidos a las personas usuarias de la app
$router->get('notifications',             'NotificationsController', 'index');
$router->post('notifications/preview',    'NotificationsController', 'preview');
$router->post('notifications/send',       'NotificationsController', 'send');

// Reports
$router->get('reports',                  'ReportsController', 'index');

// Bitácora de auditoría
$router->get('audit',                    'AuditController', 'index');

// Config
$router->get('config',                   'ConfigController', 'index');
$router->post('config',                  'ConfigController', 'update');

// ─── Módulos retirados ───────────────────────────────────────────────────────
// Eventos, prospectos y consejo dependían de tablas (events, event_registrations,
// prospects, prospect_notes, council_positions, council_assignments) que NO
// existen en cdhq_mobile. Sus rutas quedan fuera para que no revienten; los
// archivos siguen en el repositorio por si se decide recuperarlos.

// ─── Dispatch ────────────────────────────────────────────────────────────────
// The route is resolved from whichever source the server provides, so the app
// works with clean URLs (mod_rewrite), with the front controller in the path
// (/index.php/login) and with a plain query string (?r=login).
if (isset($_GET['r']) && $_GET['r'] !== '') {
    // 3) Explicit fallback: /index.php?r=login
    $url = (string) $_GET['r'];
} elseif (!empty($_SERVER['PATH_INFO'])) {
    // 2) Front controller in the path: /sistema/index.php/login
    $url = (string) $_SERVER['PATH_INFO'];
} else {
    // 1) Clean URL rewritten by .htaccess: /sistema/login
    $url = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (APP_BASE_PATH !== '' && strpos($url, APP_BASE_PATH) === 0) {
        $url = substr($url, strlen(APP_BASE_PATH));
    }
    // Drop the front controller when the server exposes it without PATH_INFO
    $url = ltrim($url, '/');
    if (strpos($url, 'index.php') === 0) {
        $url = substr($url, strlen('index.php'));
    }
}

$router->dispatch(trim($url, '/'));
