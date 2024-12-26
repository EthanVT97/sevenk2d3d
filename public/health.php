<?php
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$startTime = microtime(true);
$requestId = uniqid('health_', true);

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_id' => $requestId,
    'environment' => getenv('APP_ENV'),
    'checks' => [
        'database' => [
            'status' => 'unknown',
            'error' => null,
            'config' => [
                'host' => getenv('DB_HOST'),
                'database' => getenv('DB_NAME'),
                'port' => getenv('DB_PORT') ?? '5432',
                'ssl_mode' => 'require'
            ]
        ],
        'application' => [
            'status' => 'healthy',
            'version' => '1.0.0',
            'memory_usage' => memory_get_usage(true),
            'uptime' => time() - $_SERVER['REQUEST_TIME']
        ]
    ]
];

try {
    // Test PostgreSQL connection with SSL
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require', 
        getenv('DB_HOST'), 
        getenv('DB_PORT') ?? '5432',
        getenv('DB_NAME')
    );
    
    $startConnect = microtime(true);
    $pdo = new PDO($dsn, 
        getenv('DB_USER'), 
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_PERSISTENT => true
        ]
    );
    
    $stmt = $pdo->query('SELECT version()');
    $version = $stmt->fetch(PDO::FETCH_COLUMN);
    $connectionTime = microtime(true) - $startConnect;
    
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $version,
        'config' => $health['checks']['database']['config'],
        'latency_ms' => round($connectionTime * 1000, 2)
    ];

    // Set 200 status code only if all checks pass
    $allHealthy = true;
    foreach ($health['checks'] as $check) {
        if ($check['status'] !== 'healthy' && $check['status'] !== 'connected') {
            $allHealthy = false;
            break;
        }
    }
    
    if (!$allHealthy) {
        $health['status'] = 'degraded';
        http_response_code(503);
    } else {
        http_response_code(200);
    }

} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'error',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'config' => $health['checks']['database']['config']
    ];
    
    error_log(sprintf(
        "[Health Check] %s - Database Error: [%d] %s\nTrace: %s",
        $requestId,
        $e->getCode(),
        $e->getMessage(),
        $e->getTraceAsString()
    ));
    
    http_response_code(503);
}

// Add execution time
$health['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

echo json_encode($health, JSON_PRETTY_PRINT); 