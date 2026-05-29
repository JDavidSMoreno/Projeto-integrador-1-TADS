<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $configPath = dirname(__DIR__, 2) . '/config/database.php';
        $config = require $configPath;

        $driver = (string)($config['driver'] ?? 'mysql');
        $host = (string)($config['host'] ?? '127.0.0.1');
        $port = (string)($config['port'] ?? '3306');
        $database = (string)($config['database'] ?? '');
        $charset = (string)($config['charset'] ?? 'utf8mb4');

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $driver,
            $host,
            $port,
            $database,
            $charset
        );

        try {
            self::$instance = new PDO(
                $dsn,
                (string)($config['username'] ?? ''),
                (string)($config['password'] ?? ''),
                $config['options'] ?? []
            );
        } catch (PDOException $exception) {
            error_log('[Database] Connection error: ' . $exception->getMessage());

            throw new RuntimeException('Nao foi possivel conectar ao banco de dados.');
        }

        return self::$instance;
    }
}
