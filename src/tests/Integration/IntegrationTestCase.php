<?php

namespace Tests\Integration;

use Infrastructure\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Garante que a conexão aponte para as variáveis de ambiente do phpunit.xml
        $this->pdo = Connection::getInstance();

        // Inicia transação para que os dados criados no teste sejam desfeitos no final
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Reverte todas as inserções e alterações feitas durante o teste
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }
}

