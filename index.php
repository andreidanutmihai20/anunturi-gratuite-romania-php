<?php
<<<<<<< HEAD
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'php'    => PHP_VERSION,
    'port'   => getenv('PORT'),
]);
=======
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/jwt.php';
require_once __DIR__ . '/middleware/auth.php';

// Seteaza CORS headers
setCorsHeaders();

// Obtine calea URL (fara query string)
$requestUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName  = dirname($_SERVER['SCRIPT_NAME']);
$path        = '/' . trim(str_replace($scriptName, '', $requestUri), '/');
$method      = $_SERVER['REQUEST_METHOD'];

// Curata path-ul de slash-uri extra
$path = preg_replace('#/+#', '/', $path);

// ============ ROUTER ============

// Health check
if ($path === '/' || $path === '/health') {
    jsonSuccess(['status' => 'ok', 'message' => 'OLX Clone PHP API este activ']);
}

// Auth routes
if ($path === '/api/auth/register' && $method === 'POST') {
    require __DIR__ . '/api/auth/register.php';
    exit;
}

if ($path === '/api/auth/login' && $method === 'POST') {
    require __DIR__ . '/api/auth/login.php';
    exit;
}

if ($path === '/api/auth/me') {
    require __DIR__ . '/api/auth/me.php';
    exit;
}

if ($path === '/api/auth/profile' && $method === 'PUT') {
    require __DIR__ . '/api/auth/profile.php';
    exit;
}

// Categories
if ($path === '/api/categories' && $method === 'GET') {
    require __DIR__ . '/api/categories/index.php';
    exit;
}

// Listings routes
if ($path === '/api/listings' && in_array($method, ['GET', 'POST'])) {
    require __DIR__ . '/api/listings/index.php';
    exit;
}

if ($path === '/api/listings/mine' && $method === 'GET') {
    require __DIR__ . '/api/listings/mine.php';
    exit;
}

// Listing by ID: /api/listings/{id}
if (preg_match('#^/api/listings/([a-f0-9]{24})$#', $path, $matches)) {
    $_GET['id'] = $matches[1];
    if ($method === 'GET') {
        require __DIR__ . '/api/listings/show.php';
    } elseif ($method === 'PUT') {
        require __DIR__ . '/api/listings/update.php';
    } elseif ($method === 'DELETE') {
        require __DIR__ . '/api/listings/delete.php';
    } else {
        jsonError('Metoda nu este permisa.', 405);
    }
    exit;
}

// 404 - ruta negasita
jsonError('Endpoint inexistent: ' . $path, 404);
>>>>>>> 057697b (s)
