<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set default CORS origin if not set
$corsOrigin = getenv('CORS_ORIGIN') ?: '*';
putenv("CORS_ORIGIN=$corsOrigin");

// Set CORS headers
header('Access-Control-Allow-Origin: ' . $corsOrigin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 204 No Content');
    exit();
}

// Log request information
error_log(sprintf(
    "[Request] %s %s - Origin: %s",
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
    $_SERVER['HTTP_ORIGIN'] ?? 'None'
));

// Rest of your application code... 