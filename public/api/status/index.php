<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json');

// Initialize response
$status = [
    'status' => 'checking',
    'timestamp' => date('Y-m-d H:i:s'),
    'uptime' => time() - $_SERVER['REQUEST_TIME'],
    'services' => [
        'database' => [
            'status' => 'checking',
            'latency' => null
        ],
        'api' => [
            'status' => 'active',
            'version' => '1.0.0'
        ]
    ]
];

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
    
    $status['services']['database'] = [
        'status' => 'connected',
        'latency' => $latency . 'ms'
    ];
    
    // Set overall status
    $status['status'] = 'operational';
    http_response_code(200);

} catch (Exception $e) {
    error_log(sprintf(
        "[Status Check] Error: %s",
        $e->getMessage()
    ));
    
    $status['services']['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed'
    ];
    
    $status['status'] = 'degraded';
    http_response_code(503);
}

echo json_encode($status, JSON_PRETTY_PRINT); 