<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Define allowed origins
$allowedOrigins = [
    'https://2d3d-lottery.onrender.com',
    'https://twod3d-lottery.onrender.com'
];

// Get the current origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Set CORS headers based on origin
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]); // Default to primary domain
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$response = [
    'name' => '2D3D Lottery API',
    'version' => '1.0.0',
    'status' => 'running',
    'environment' => getenv('APP_ENV') ?: 'production',
    'endpoints' => [
        'health' => '/health',
        'status' => '/api/status',
        'documentation' => '/docs'
    ],
    'timestamp' => date('Y-m-d H:i:s'),
    'contact' => [
        'website' => 'https://2d3d-lottery.onrender.com',
        'support' => 'support@2d3d-lottery.onrender.com'
    ],
    'links' => [
        'frontend' => 'https://2d3d-lottery.onrender.com',
        'health_check' => 'https://twod3d-lottery-api-q68w.onrender.com/health',
        'status' => 'https://twod3d-lottery-api-q68w.onrender.com/api/status'
    ]
];

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT); 