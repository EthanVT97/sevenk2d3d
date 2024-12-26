<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Define allowed origins
$allowedOrigins = [
    'https://2d3d-lottery.onrender.com',
    'https://twod3d-lottery.onrender.com',
    'chrome-extension://majdfhpaihoncoakbjgbdhglocklcgno', // Allow your Chrome extension
    'null',  // Allow requests from local files
    '*'      // Allow all origins temporarily for testing
];

// Get the current origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

// Set CORS headers
header('Access-Control-Allow-Origin: *'); // Allow all origins temporarily for testing
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, Origin, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours
header('Vary: Origin'); // Ensure proper caching with CORS

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$startTime = microtime(true);
$requestId = uniqid('health_', true);

$health = [
    'name' => '2D3D Lottery API',
    'version' => '1.0.0',
    'status' => 'healthy',
    'environment' => getenv('APP_ENV') ?: 'production',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_id' => $requestId,
    'checks' => [
        'database' => [
            'status' => 'checking',
            'config' => [
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT') ?? '5432',
                'ssl_mode' => 'require'
            ]
        ],
        'application' => [
            'status' => 'healthy',
            'memory' => [
                'used' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ],
            'uptime' => time() - $_SERVER['REQUEST_TIME']
        ]
    ],
    'links' => [
        'frontend' => 'https://2d3d-lottery.onrender.com',
        'api_root' => 'https://twod3d-lottery-api-q68w.onrender.com',
        'status' => 'https://twod3d-lottery-api-q68w.onrender.com/api/status'
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
    
    $connectionTime = (microtime(true) - $startConnect) * 1000;
    
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $version,
        'latency' => round($connectionTime, 2) . 'ms',
        'config' => $health['checks']['database']['config']
    ];

} catch (Exception $e) {
    error_log(sprintf(
        "[Health Check] %s - Error: [%d] %s\nTrace: %s",
        $requestId,
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