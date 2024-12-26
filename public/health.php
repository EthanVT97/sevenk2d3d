<?php
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$startTime = microtime(true);

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [
        'database' => [
            'status' => 'checking',
            'version' => null,
            'latency' => null
        ],
        'application' => [
            'status' => 'healthy',
            'version' => '1.0.0',
            'memory' => [
                'used' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ],
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
    
    // Get database version
    $stmt = $pdo->query('SELECT version()');
    $version = $stmt->fetch(PDO::FETCH_COLUMN);
    
    // Get connection count
    $stmt = $pdo->query('SELECT count(*) FROM pg_stat_activity');
    $connections = $stmt->fetch(PDO::FETCH_COLUMN);
    
    $connectionTime = (microtime(true) - $startConnect) * 1000; // Convert to milliseconds
    
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $version,
        'latency' => round($connectionTime, 2) . 'ms',
        'active_connections' => (int)$connections
    ];

    http_response_code(200);

} catch (Exception $e) {
    error_log(sprintf(
        "[Health Check] Database Error: [%d] %s",
        $e->getCode(),
        $e->getMessage()
    ));
    
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'error',
        'error' => 'Database connection failed',
        'error_code' => $e->getCode()
    ];
    
    http_response_code(503);
}

// Add total execution time
$health['execution_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';

echo json_encode($health, JSON_PRETTY_PRINT); 