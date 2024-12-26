<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->safeLoad();

header('Content-Type: application/json');

try {
    // Check if required environment variables are set
    $required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $missing_vars = [];
    
    foreach ($required_vars as $var) {
        if (!isset($_ENV[$var])) {
            $missing_vars[] = $var;
        }
    }
    
    if (!empty($missing_vars)) {
        throw new Exception('Missing required environment variables: ' . implode(', ', $missing_vars));
    }

    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $port = $_ENV['DB_PORT'] ?? '5432';
    
    $dsn = sprintf('pgsql:host=%s;dbname=%s;port=%s', $host, $dbname, $port);
    
    // Add connection timeout
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
    
    // Test the connection with a simple query
    $stmt = $pdo->query('SELECT version()');
    $version = $stmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'details' => [
            'host' => $host,
            'database' => $dbname,
            'port' => $port,
            'version' => $version
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'details' => [
            'host' => $host ?? null,
            'database' => $dbname ?? null,
            'port' => $port ?? null,
            'error_code' => $e->getCode()
        ]
    ]);
} 