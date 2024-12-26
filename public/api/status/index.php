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

try {
    // Quick database check
    $startTime = microtime(true);
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        getenv('DB_HOST'),
        getenv('DB_PORT') ?? '5432',
        getenv('DB_NAME')
    );

    $pdo = new PDO(
        $dsn,
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]
    );

    // Simple query to check database
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();
    
    $latency = round((microtime(true) - $startTime) * 1000, 2); // Convert to milliseconds
    
    $response = [
        'status' => 'API is running',
        'database' => 'connected',
        'latency' => $latency . 'ms',
        'environment' => getenv('APP_ENV') ?: 'production',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code(200);

} catch (Exception $e) {
    error_log(sprintf(
        "[Status Check] Error: %s",
        $e->getMessage()
    ));
    
    $response = [
        'status' => 'API is running',
        'database' => 'error',
        'message' => 'Database connection failed',
        'environment' => getenv('APP_ENV') ?: 'production',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Still return 200 if API is running but DB is down
    http_response_code(200);
}

echo json_encode($response, JSON_PRETTY_PRINT); 