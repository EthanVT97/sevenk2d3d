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
            'latency' => null,
            'config' => [
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT') ?? '5432',
                'ssl_mode' => getenv('DB_SSL_MODE') ?? 'require'
            ]
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
    
    $stmt = $pdo->query('SELECT count(*) FROM pg_stat_activity');
    $connections = $stmt->fetch(PDO::FETCH_COLUMN);
    
    $connectionTime = (microtime(true) - $startConnect) * 1000;
    
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $version,
        'latency' => round($connectionTime, 2) . 'ms',
        'active_connections' => (int)$connections,
        'config' => $health['checks']['database']['config']
    ];

} catch (Exception $e) {
    error_log(sprintf(
        "[Health Check] Database Error: [%d] %s\nTrace: %s",
        $e->getCode(),
        $e->getMessage(),
        $e->getTraceAsString()
    ));
    
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'error',
        'error' => 'Database connection failed',
        'error_code' => $e->getCode(),
        'config' => $health['checks']['database']['config']
    ];
    
    http_response_code(503);
}

$health['execution_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';

echo json_encode($health, JSON_PRETTY_PRINT); 