<?php
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

// Rate limiting check
$rateLimit = 10; // requests per minute
$rateLimitWindow = 60; // seconds
$ipAddress = $_SERVER['REMOTE_ADDR'];
$cacheFile = sys_get_temp_dir() . '/health_check_' . md5($ipAddress) . '.json';

if (file_exists($cacheFile)) {
    $rateData = json_decode(file_get_contents($cacheFile), true);
    $now = time();
    // Clean old requests
    $rateData['requests'] = array_filter($rateData['requests'], function($time) use ($now, $rateLimitWindow) {
        return ($now - $time) < $rateLimitWindow;
    });
    
    if (count($rateData['requests']) >= $rateLimit) {
        http_response_code(429);
        echo json_encode([
            'status' => 'error',
            'message' => 'Rate limit exceeded. Please wait before making more requests.',
            'retry_after' => $rateLimitWindow - ($now - min($rateData['requests']))
        ]);
        exit;
    }
    
    $rateData['requests'][] = $now;
} else {
    $rateData = ['requests' => [time()]];
}
file_put_contents($cacheFile, json_encode($rateData));

// Initialize response
$health = [
    'status' => 'initializing',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => getenv('APP_ENV'),
    'request_id' => uniqid(),
    'request_info' => [
        'source_ip' => $ipAddress,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
        'rate_limit' => [
            'remaining' => $rateLimit - count($rateData['requests']),
            'reset' => time() + $rateLimitWindow
        ]
    ],
    'checks' => [
        'database' => [
            'status' => 'checking',
            'error' => null,
            'config' => [
                'host' => getenv('DB_HOST'),
                'database' => getenv('DB_NAME'),
                'port' => getenv('DB_PORT') ?? '5432',
                'ssl_mode' => getenv('DB_SSL_MODE') ?? 'require',
                'user' => getenv('DB_USER') ? 'set' : 'not_set',
                'password' => getenv('DB_PASS') ? 'set' : 'not_set'
            ]
        ],
        'application' => [
            'status' => 'checking',
            'version' => '1.0.0',
            'cors' => [
                'origin' => getenv('CORS_ORIGIN') ?: '*',
                'methods' => 'GET, POST, OPTIONS, PUT, DELETE',
                'headers' => 'Content-Type, Authorization, X-Requested-With'
            ],
            'environment_vars' => [
                'APP_ENV' => getenv('APP_ENV'),
                'APP_DEBUG' => getenv('APP_DEBUG') ? 'true' : 'false'
            ]
        ],
        'system' => [
            'status' => 'checking',
            'memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'php_version' => PHP_VERSION,
            'load_average' => sys_getloadavg(),
            'disk_free_space' => disk_free_space('/'),
            'disk_total_space' => disk_total_space('/')
        ]
    ]
];

// Set initial status code - will be overwritten if healthy
http_response_code(503);

try {
    // System checks
    $systemChecks = [
        'memory' => memory_get_usage(true) < 128 * 1024 * 1024, // Less than 128MB
        'disk_space' => (disk_free_space('/') / disk_total_space('/')) > 0.1, // More than 10% free
        'load' => sys_getloadavg()[0] < 5 // Load average below 5
    ];
    
    if (in_array(false, $systemChecks, true)) {
        throw new Exception("System resources critical: " . implode(', ', array_keys(array_filter($systemChecks, function($v) { return !$v; }))));
    }
    
    $health['checks']['system']['status'] = 'healthy';
    
    // Log health check start with detailed information
    error_log(sprintf(
        "[Health Check] %s - Starting health check - Environment: %s, Source: %s, Agent: %s, Memory: %s, Load: %s",
        $health['request_id'],
        getenv('APP_ENV'),
        $ipAddress,
        $health['request_info']['user_agent'],
        memory_get_usage(true),
        implode(',', sys_getloadavg())
    ));
    
    // Validate required environment variables
    $required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $missing_vars = [];
    foreach ($required_vars as $var) {
        if (!getenv($var)) {
            $missing_vars[] = $var;
        }
    }
    
    if (!empty($missing_vars)) {
        throw new Exception("Required environment variables missing: " . implode(', ', $missing_vars));
    }
    
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require', 
        getenv('DB_HOST'), 
        getenv('DB_PORT') ?? '5432',
        getenv('DB_NAME')
    );
    
    error_log("[Health Check] {$health['request_id']} - Attempting database connection to {$dsn}");
    
    $startTime = microtime(true);
    $pdo = new PDO($dsn, 
        getenv('DB_USER'), 
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_PERSISTENT => true
        ]
    );
    $connectionTime = microtime(true) - $startTime;
    
    if ($connectionTime > 1) {
        throw new Exception("Database connection time exceeded threshold: {$connectionTime}s");
    }
    
    // Test the connection with multiple queries
    $checks = [
        'version' => 'SELECT version()',
        'current_time' => 'SELECT NOW()',
        'connection_count' => 'SELECT count(*) FROM pg_stat_activity',
        'max_connections' => 'SHOW max_connections'
    ];
    
    $dbResults = [];
    foreach ($checks as $key => $query) {
        $stmt = $pdo->query($query);
        $dbResults[$key] = $stmt->fetch(PDO::FETCH_COLUMN);
    }
    
    // Check if we're approaching connection limit
    $connectionUsage = ($dbResults['connection_count'] / $dbResults['max_connections']) * 100;
    if ($connectionUsage > 80) {
        throw new Exception("High database connection usage: {$connectionUsage}%");
    }
    
    error_log(sprintf(
        "[Health Check] %s - Database connection successful - Time: %.4fs, Connections: %s/%s (%.1f%%)",
        $health['request_id'],
        $connectionTime,
        $dbResults['connection_count'],
        $dbResults['max_connections'],
        $connectionUsage
    ));
    
    $health['status'] = 'healthy';
    $health['checks']['database'] = [
        'status' => 'connected',
        'version' => $dbResults['version'],
        'server_time' => $dbResults['current_time'],
        'active_connections' => $dbResults['connection_count'],
        'max_connections' => $dbResults['max_connections'],
        'connection_usage' => round($connectionUsage, 2) . '%',
        'config' => $health['checks']['database']['config'],
        'connection_time' => round($connectionTime, 4),
        'latency' => round($connectionTime, 4)
    ];
    
    // Only set 200 status if ALL checks pass
    $allHealthy = true;
    foreach ($health['checks'] as $check) {
        if ($check['status'] !== 'healthy' && $check['status'] !== 'connected') {
            $allHealthy = false;
            break;
        }
    }
    
    if ($allHealthy) {
        http_response_code(200);
    }

} catch (Exception $e) {
    $errorTime = microtime(true);
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    
    error_log(sprintf(
        "[Health Check] %s - Error: [%d] %s\nTrace: %s",
        $health['request_id'],
        $errorCode,
        $errorMessage,
        $e->getTraceAsString()
    ));
    
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'error',
        'error' => $errorMessage,
        'error_code' => $errorCode,
        'config' => $health['checks']['database']['config'],
        'error_time' => $errorTime,
        'stack_trace' => explode("\n", $e->getTraceAsString())
    ];
}

// Add response time and final memory usage
$health['response_time'] = microtime(true);
$health['duration'] = round($health['response_time'] - $health['request_info']['request_time'], 4);
$health['checks']['system']['final_memory_usage'] = memory_get_usage(true);

// Cache the response for rate limiting
$health['request_info']['rate_limit']['remaining'] = $rateLimit - count($rateData['requests']);
$health['request_info']['rate_limit']['reset'] = time() + $rateLimitWindow;

echo json_encode($health, JSON_PRETTY_PRINT); 