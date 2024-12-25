<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Logger;
use App\Config\Cache;
use App\Security\KeyGenerator;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize configurations
Logger::initialize();
Cache::initialize();

// Generate secure keys if not set
if (empty($_ENV['APP_KEY'])) {
    putenv('APP_KEY=' . KeyGenerator::generateSecureKey());
}
if (empty($_ENV['JWT_SECRET'])) {
    putenv('JWT_SECRET=' . KeyGenerator::generateJwtSecret());
}

// Verify critical extensions
$requiredExtensions = ['pdo', 'pdo_pgsql', 'json'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Server Configuration Error',
            'message' => "Required extension '$ext' is not loaded"
        ]);
        exit(1);
    }
}

// Set production error reporting
if ($_ENV['APP_ENV'] === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Handle request
require_once __DIR__ . '/../router.php';
?>