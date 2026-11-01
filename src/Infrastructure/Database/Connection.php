<?php

namespace Infrastructure\Database;

use PDO;
use PDOException;
use Exception;

class Connection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                // Credenciais padrão do WAMP
                $host = 'db';
                $db   = 'oficina_mecanica';
                $user = 'root';
                $pass = 'sua_senha';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                
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
}