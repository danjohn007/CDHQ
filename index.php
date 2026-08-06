<?php
declare(strict_types=1);

// ─── Bootstrap ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Router.php';

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

// Users
$router->get('users',         'UsersController', 'index');
$router->get('users/create',  'UsersController', 'create');
$router->post('users/create', 'UsersController', 'store');
$router->get('users/:id/edit','UsersController', 'edit');
$router->post('users/:id/edit','UsersController','update');
$router->post('users/:id/toggle','UsersController','toggle');
$router->post('users/:id/delete','UsersController','destroy');

// Affiliates
$router->get('affiliates',               'AffiliatesController', 'index');
$router->get('affiliates/create',        'AffiliatesController', 'create');
$router->post('affiliates/create',       'AffiliatesController', 'store');
$router->get('affiliates/:id',           'AffiliatesController', 'show');
$router->get('affiliates/:id/edit',      'AffiliatesController', 'edit');
$router->post('affiliates/:id/edit',     'AffiliatesController', 'update');
$router->post('affiliates/:id/status',   'AffiliatesController', 'changeStatus');

// Subscriptions
$router->get('subscriptions',            'SubscriptionsController', 'index');
$router->get('subscriptions/create',     'SubscriptionsController', 'create');
$router->post('subscriptions/create',    'SubscriptionsController', 'store');

// Financial
$router->get('financial',                'FinancialController', 'index');
$router->get('financial/create',         'FinancialController', 'create');
$router->post('financial/create',        'FinancialController', 'store');

// Events
$router->get('events',                   'EventsController', 'index');
$router->get('events/create',            'EventsController', 'create');
$router->post('events/create',           'EventsController', 'store');
$router->get('events/:id',               'EventsController', 'show');
$router->post('events/:id/register',     'EventsController', 'register');
$router->post('events/:id/status',       'EventsController', 'changeStatus');

// Prospects
$router->get('prospects',                'ProspectsController', 'index');
$router->get('prospects/create',         'ProspectsController', 'create');
$router->post('prospects/create',        'ProspectsController', 'store');
$router->get('prospects/:id/edit',       'ProspectsController', 'edit');
$router->post('prospects/:id/edit',      'ProspectsController', 'update');
$router->post('prospects/:id/note',      'ProspectsController', 'addNote');
$router->post('prospects/:id/convert',   'ProspectsController', 'convert');

// Reports
$router->get('reports',                  'ReportsController', 'index');

// Credentials
$router->get('credentials/:id',          'CredentialsController', 'show');

// Verificación PÚBLICA de credenciales (destino del código QR impreso).
// Es la única ruta del sistema que no exige sesión además de /login.
$router->get('verificar',                'CredentialsController', 'verifyForm');
$router->get('verificar/:token',         'CredentialsController', 'verify');
$router->get('verify/:token',            'CredentialsController', 'verify');

// Council
$router->get('council',                  'CouncilController', 'index');
$router->get('council/positions',        'CouncilController', 'positions');
$router->post('council/positions/create','CouncilController', 'createPosition');
$router->get('council/assignments',      'CouncilController', 'assignments');
$router->post('council/assignments/create','CouncilController','createAssignment');

// Config
$router->get('config',                   'ConfigController', 'index');
$router->post('config',                  'ConfigController', 'update');

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
