<?php

declare(strict_types=1);

namespace Infrastructure\Repositories;

use Domain\Entities\Veiculo;
use Domain\Repositories\VeiculoRepositoryInterface;
use Domain\ValueObjects\Placa;
use PDO;

class VeiculoPdoRepository implements VeiculoRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    private function hidratar(array $row): Veiculo
    {
        $ano = isset($row['ano_fabricacao']) ? (int) $row['ano_fabricacao'] : (int) ($row['ano'] ?? 0);

        return Veiculo::reconstituir(
            id:           (int) $row['id'],
            clienteId:    (int) $row['cliente_id'],
            placa:        new Placa($row['placa']),
            marca:        $row['marca'],
            modelo:       $row['modelo'],
            ano:          $ano,
            cor:          $row['cor'] ?? null,
            criadoEm:     $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null
        );
    }

    public function salvar(Veiculo $veiculo): Veiculo
    {
        $sql = "INSERT INTO veiculos (cliente_id, placa, marca, modelo, ano_fabricacao, ano_modelo, cor)
                VALUES (:cliente_id, :placa, :marca, :modelo, :ano_fabricacao, :ano_modelo, :cor)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cliente_id'      => $veiculo->clienteId(),
            ':placa'           => $veiculo->placa()->valor(),
            ':marca'           => $veiculo->marca(),
            ':modelo'          => $veiculo->modelo(),
            ':ano_fabricacao'  => $veiculo->ano(),
            ':ano_modelo'      => $veiculo->ano(),
            ':cor'             => $veiculo->cor(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $veiculo->definirId($id);

        return $this->buscarPorId($id) ?? $veiculo;
    }

    public function buscarPorId(int $id): ?Veiculo
    {
        $sql = "SELECT id, cliente_id, placa, marca, modelo, ano_fabricacao, ano_modelo, cor, criado_em, atualizado_em
                FROM veiculos
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hidratar($row) : null;
    }

    public function buscarPorPlaca(Placa $placa): ?Veiculo
    {
        $sql = "SELECT id, cliente_id, placa, marca, modelo, ano_fabricacao, ano_modelo, cor, criado_em, atualizado_em
                FROM veiculos
                WHERE placa = :placa
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':placa' => $placa->valor()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hidratar($row) : null;
    }

    public function listarTodos(): array
    {
        $sql = "SELECT id, cliente_id, placa, marca, modelo, ano_fabricacao, ano_modelo, cor, criado_em, atualizado_em
                FROM veiculos
                ORDER BY id DESC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hidratar'], $rows);
    }

    public function listarPorClienteId(int $clienteId): array
    {
        $sql = "SELECT id, cliente_id, placa, marca, modelo, ano_fabricacao, ano_modelo, cor, criado_em, atualizado_em
                FROM veiculos
                WHERE cliente_id = :cliente_id
                ORDER BY id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cliente_id' => $clienteId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hidratar'], $rows);
    }

    public function atualizar(Veiculo $veiculo): Veiculo
    {
        $sql = "UPDATE veiculos
                SET cliente_id = :cliente_id,
                    placa = :placa,
                    marca = :marca,
                    modelo = :modelo,
                    ano_fabricacao = :ano_fabricacao,
                    ano_modelo = :ano_modelo,
                    cor = :cor
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'              => $veiculo->id(),
            ':cliente_id'      => $veiculo->clienteId(),
            ':placa'           => $veiculo->placa()->valor(),
            ':marca'           => $veiculo->marca(),
            ':modelo'          => $veiculo->modelo(),
            ':ano_fabricacao'  => $veiculo->ano(),
            ':ano_modelo'      => $veiculo->ano(),
            ':cor'             => $veiculo->cor(),
        ]);

        return $this->buscarPorId((int) $veiculo->id()) ?? $veiculo;
    }

    public function deletar(int $id): bool
    {
        try {
            $sql = "DELETE FROM veiculos WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1451)) {
                throw new \Domain\Exceptions\ValidacaoException(
                    'Não é possível remover este veículo porque existem ordens de serviço vinculadas a ele.'
                );
            }
            throw $e;
        }
    }

    public function placaJaExiste(Placa $placa, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM veiculos WHERE placa = :placa";
        $params = [':placa' => $placa->valor()];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }
}
