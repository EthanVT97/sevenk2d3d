<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Database\Connection;
use App\Exceptions\HttpException;
use App\Controllers\AuthController;
use App\Controllers\BetController;
use App\Controllers\TransactionController;
use App\Controllers\LotteryController;
use App\Utils\ResponseFormatter;
use ErrorException;

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Type: application/json; charset=utf-8');

if ($_ENV['APP_ENV'] === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required([
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
        'JWT_SECRET', 'APP_ENV'
    ])->notEmpty();

    // Set timezone
    date_default_timezone_set('Asia/Yangon');

    // Initialize database connection
    Connection::getInstance();

    // Initialize middleware stack with configurations
    $middleware = [
        new CorsMiddleware([
            'allowed_origins' => [$_ENV['CORS_ORIGIN']],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
        ]),
        new RateLimitMiddleware([
            'requests_per_minute' => (int)($_ENV['RATE_LIMIT'] ?? 60),
            'window_minutes' => (int)($_ENV['RATE_WINDOW'] ?? 1)
        ]),
        new AuthMiddleware([
            'exclude_paths' => [
                '/api/auth/login',
                '/api/auth/register',
                '/health'
            ]
        ])
    ];

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }

    // Parse request data
    $input = file_get_contents('php://input');
    $jsonData = !empty($input) ? json_decode($input, true) : [];
    $request = array_merge($_GET, $_POST, $jsonData ?? []);

    // Handle request through middleware
    $response = null;
    foreach ($middleware as $mw) {
        $response = $mw->handle($request, function($req) use (&$response) {
            return $response;
        });
    }

    // Route handling
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    // API Routes
    switch ($path) {
        case '/api/auth/login':
            $response = (new AuthController())->login($request);
            break;

        case '/api/auth/register':
            $response = (new AuthController())->register($request);
            break;

        case '/api/auth/logout':
            $response = (new AuthController())->logout($request);
            break;

        case '/api/bets':
            $controller = new BetController();
            switch ($method) {
                case 'GET':
                    $response = $controller->getBets($request);
                    break;
                case 'POST':
                    $response = $controller->placeBet($request);
                    break;
                default:
                    throw new HttpException('Method not allowed', 405);
            }
            break;

        case '/api/transactions':
            $controller = new TransactionController();
            switch ($method) {
                case 'GET':
                    $response = $controller->getTransactions($request);
                    break;
                case 'POST':
                    $response = $controller->createTransaction($request);
                    break;
                default:
                    throw new HttpException('Method not allowed', 405);
            }
            break;

        case '/api/lottery/results':
            $controller = new LotteryController();
            if ($method === 'GET') {
                $response = $controller->getResults($request);
            } else {
                throw new HttpException('Method not allowed', 405);
            }
            break;

        case '/health':
            $dbStatus = Connection::test();
            $response = [
                'status' => $dbStatus ? 'healthy' : 'unhealthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'environment' => $_ENV['APP_ENV'],
                'components' => [
                    'database' => [
                        'status' => $dbStatus ? 'connected' : 'disconnected',
                        'message' => $dbStatus ? 'Database connection successful' : 'Database connection failed'
                    ]
                ]
            ];
            break;

        default:
            throw new HttpException('Not Found', 404);
    }

    // Send response
    if (isset($response)) {
        echo ResponseFormatter::json($response);
    }

} catch (HttpException $e) {
    http_response_code($e->getCode());
    echo ResponseFormatter::error($e->getMessage(), $e->getCode());
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo ResponseFormatter::error('Internal Server Error', 500);
} finally {
    // Close database connection
    if (Connection::getInstance()) {
        Connection::close();
    }
}
