<?php
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

// Initialize response
$health = [
    'status' => 'initializing',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => getenv('APP_ENV'),
    'request_id' => uniqid(),
    'checks' => [
        'database' => [
            'status' => 'checking',
            'error' => null,
            'config' => [
                'host' => getenv('DB_HOST'),
                'database' => getenv('DB_NAME'),
                'port' => getenv('DB_PORT') ?? '5432',
                'ssl_mode' => getenv('DB_SSL_MODE') ?? 'require'
            ]
        ],
        'application' => [
            'status' => 'healthy',
            'version' => '1.0.0',
            'cors' => [
                'origin' => getenv('CORS_ORIGIN') ?: '*',
                'methods' => 'GET, POST, OPTIONS, PUT, DELETE',
                'headers' => 'Content-Type, Authorization, X-Requested-With'
            ]
        ]
    ]
];

try {
    // Log health check start
    error_log("[Health Check] {$health['request_id']} - Starting health check");
    
    // Validate required environment variables
    $required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($required_vars as $var) {
        if (!getenv($var)) {
            throw new Exception("Required environment variable missing: {$var}");
        }
    }
    
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require', 
        getenv('DB_HOST'), 
        getenv('DB_PORT') ?? '5432',
        getenv('DB_NAME')
    );
    
    error_log("[Health Check] {$health['request_id']} - Attempting database connection");
    
    $pdo = new PDO($dsn, 
        getenv('DB_USER'), 
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_PERSISTENT => true
        ]
    );
    
    // Test the connection with a simple query
    $stmt = $pdo->query('SELECT version()');
    $version = $stmt->fetch(PDO::FETCH_COLUMN);
    
    error_log("[Health Check] {$health['request_id']} - Database connection successful");
    
    $health['status'] = 'healthy';
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $version,
        'config' => $health['checks']['database']['config'],
        'connection_time' => microtime(true)
    ];

} catch (Exception $e) {
    error_log("[Health Check] {$health['request_id']} - Error: " . $e->getMessage());
    
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'error',
        'error' => $e->getMessage(),
        'config' => $health['checks']['database']['config'],
        'error_time' => microtime(true)
    ];
    
    // Set appropriate status code for service unavailable
    http_response_code(503);
}

// Add response time
$health['response_time'] = microtime(true);

echo json_encode($health, JSON_PRETTY_PRINT); 