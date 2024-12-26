<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (getenv('CORS_ORIGIN') ?: '*'));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Simple response matching current live endpoint
$response = [
    'status' => 'API is running'
];

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT); 