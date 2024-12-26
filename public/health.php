<?php
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => getenv('APP_ENV'),
    'checks' => [
        'database' => [
            'status' => 'unknown',
            'error' => null,
            'config' => [
                'host' => getenv('DB_HOST'),
                'database' => getenv('DB_NAME'),
                'port' => getenv('DB_PORT') ?? '5432'
            ]
        ],
        'application' => [
            'status' => 'healthy',
            'version' => '1.0.0'
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
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $version,
        'config' => $health['checks']['database']['config']
    ];

} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'error',
        'error' => $e->getMessage(),
        'config' => $health['checks']['database']['config']
    ];
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(503);
}

echo json_encode($health, JSON_PRETTY_PRINT); 