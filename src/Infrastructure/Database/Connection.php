<?php

namespace Infrastructure\Database;

use Exception;
use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $host    = getenv('DB_HOST') ?: (PHP_OS_FAMILY === 'Windows' ? '127.0.0.1' : 'db');
                $port    = getenv('DB_PORT') ?: (PHP_OS_FAMILY === 'Windows' ? '3307' : '3306');
                $db      = getenv('DB_NAME') ?: 'oficina_mecanica';
                $user    = getenv('DB_USER') ?: 'root';
                $pass    = getenv('DB_PASS') ?: 'root_dev_password';
                $charset = 'utf8mb4';

                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                throw new Exception("Falha na conexão com o banco de dados: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
