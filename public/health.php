<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;

$allowedOrigins = [
    'http://localhost:3000',
    'https://twod3d-lottery.onrender.com',
    'https://2d3d-lottery.onrender.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$status = [
    'status' => 'healthy',
    'timestamp' => time(),
    'database' => Connection::test() ? 'connected' : 'disconnected',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'json' => extension_loaded('json')
    ]
];

http_response_code($status['database'] === 'connected' ? 200 : 503);
echo json_encode($status);
