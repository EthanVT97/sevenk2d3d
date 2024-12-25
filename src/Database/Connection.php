<?php
namespace App\Database;

use PDO;
use PDOException;

class Connection {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            try {
                if ($_ENV['DB_CONNECTION'] === 'sqlite') {
                    $dsn = 'sqlite:' . ($_ENV['DB_DATABASE'] === ':memory:' ? ':memory:' : $_ENV['DB_DATABASE']);
                    self::$instance = new PDO($dsn);
                } else {
                    $dbConfig = [
                        'host' => $_ENV['DB_HOST'] ?? 'dpg-ctm70o9opnds73fdciig-a.singapore-postgres.render.com',
                        'port' => $_ENV['DB_PORT'] ?? '5432',
                        'name' => $_ENV['DB_NAME'] ?? 'db_2d3d_lottery_db',
                        'user' => $_ENV['DB_USER'] ?? 'db_2d3d_lottery_db_user',
                        'pass' => $_ENV['DB_PASS'] ?? 'ZcV5s0MAJrFxPyYfQFr7lJFADwxFAn6b'
                    ];

                    $dsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s;",
                        $dbConfig['host'],
                        $dbConfig['port'],
                        $dbConfig['name']
                    );

                    self::$instance = new PDO(
                        $dsn,
                        $dbConfig['user'],
                        $dbConfig['pass'],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false
                        ]
                    );

                    // Set timezone and other PostgreSQL specific settings
                    self::$instance->exec("SET timezone = 'Asia/Yangon'");
                }

                // Common PDO settings
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$instance;
    }

    public static function test() {
        try {
            $instance = self::getInstance();
            $instance->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
