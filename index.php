<?php

require_once __DIR__ . '/src/Infrastructure/Database/Connection.php';

use Infrastructure\Database\Connection;

try {
    $pdo = Connection::getInstance();

    echo 'Conexão realizada com sucesso!';

    $result = $pdo->query('SELECT DATABASE() AS banco');
    $row = $result->fetch();

    echo '<p>Banco conectado: '
        . htmlspecialchars($row['banco'])
        . '</p>';

} catch (Exception $e) {
    http_response_code(500);

    echo 'Falha na conexão com o banco de dados.';

    error_log($e->getMessage());
}