<?php

namespace Repositories\Veiculo;

use Domain\Exceptions\ValidacaoException;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use PDO;
use PDOException;

class VeiculoPdoRepository implements VeiculoRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function montarVeiculo(array $linha): Veiculo
    {
        $ano = isset($linha['ano_fabricacao'])
            ? (int) $linha['ano_fabricacao']
            : (int) ($linha['ano'] ?? 0);

        return new Veiculo(
            id:           (int) $linha['id'],
            clienteId:    (int) $linha['cliente_id'],
            placa:        new Placa($linha['placa']),
            marca:        $linha['marca'],
            modelo:       $linha['modelo'],
            ano:          $ano,
            cor:          $linha['cor'] ?? null,
            criadoEm:     $linha['criado_em'] ?? null,
            atualizadoEm: $linha['atualizado_em'] ?? null,
        );
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM veiculos ORDER BY id DESC');

        return array_map(
            fn(array $linha) => $this->montarVeiculo($linha),
            $stmt->fetchAll()
        );
    }

    public function listarPorClienteId(int $clienteId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM veiculos WHERE cliente_id = :cliente_id ORDER BY id DESC'
        );
        $stmt->execute([':cliente_id' => $clienteId]);

        return array_map(
            fn(array $linha) => $this->montarVeiculo($linha),
            $stmt->fetchAll()
        );
    }

    public function buscarPorId(int $id): ?Veiculo
    {
        $stmt = $this->pdo->prepare('SELECT * FROM veiculos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();

        return $linha ? $this->montarVeiculo($linha) : null;
    }

    public function buscarPorPlaca(Placa $placa): ?Veiculo
    {
        $stmt = $this->pdo->prepare('SELECT * FROM veiculos WHERE placa = :placa');
        $stmt->execute([':placa' => $placa->getValor()]);
        $linha = $stmt->fetch();

        return $linha ? $this->montarVeiculo($linha) : null;
    }

    public function placaJaCadastrada(Placa $placa, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM veiculos WHERE placa = :placa';
        $params = [':placa' => $placa->getValor()];

        if ($excluirId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ------------------------------------------------------------------
    // Comandos
    // ------------------------------------------------------------------

    public function salvar(Veiculo $veiculo): Veiculo
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO veiculos (cliente_id, placa, marca, modelo, ano_fabricacao, ano_modelo, cor)
             VALUES (:cliente_id, :placa, :marca, :modelo, :ano_fabricacao, :ano_modelo, :cor)'
        );

        $stmt->execute([
            ':cliente_id'     => $veiculo->getClienteId(),
            ':placa'          => $veiculo->getPlaca()->getValor(),
            ':marca'          => $veiculo->getMarca(),
            ':modelo'         => $veiculo->getModelo(),
            ':ano_fabricacao' => $veiculo->getAno(),
            ':ano_modelo'     => $veiculo->getAno(),
            ':cor'            => $veiculo->getCor(),
        ]);

        return $this->buscarPorId((int) $this->pdo->lastInsertId());
    }

    public function atualizar(Veiculo $veiculo): Veiculo
    {
        $stmt = $this->pdo->prepare(
            'UPDATE veiculos
             SET cliente_id = :cliente_id,
                 placa = :placa,
                 marca = :marca,
                 modelo = :modelo,
                 ano_fabricacao = :ano_fabricacao,
                 ano_modelo = :ano_modelo,
                 cor = :cor
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'             => $veiculo->getId(),
            ':cliente_id'     => $veiculo->getClienteId(),
            ':placa'          => $veiculo->getPlaca()->getValor(),
            ':marca'          => $veiculo->getMarca(),
            ':modelo'         => $veiculo->getModelo(),
            ':ano_fabricacao' => $veiculo->getAno(),
            ':ano_modelo'     => $veiculo->getAno(),
            ':cor'            => $veiculo->getCor(),
        ]);

        return $this->buscarPorId($veiculo->getId());
    }

    public function deletar(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculos WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1451)) {
                throw new ValidacaoException(
                    'Não é possível remover este veículo porque existem ordens de serviço vinculadas a ele.'
                );
            }
            throw $e;
        }
    }
}
