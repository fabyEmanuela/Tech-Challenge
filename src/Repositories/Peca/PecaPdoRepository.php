<?php

namespace Repositories\Peca;

use Domain\Peca\Peca;
use PDO;

class PecaPdoRepository implements PecaRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function montarPeca(array $linha): Peca
    {
        return new Peca(
            id:                (int) $linha['id'],
            nome:              $linha['nome'],
            descricao:         $linha['descricao'] ?? null,
            valorUnitario:     (float) $linha['valor_unitario'],
            quantidadeEstoque: (int) $linha['quantidade_estoque'],
            estoqueMinimo:     (int) $linha['estoque_minimo'],
            ativo:             (bool) $linha['ativo'],
            criadoEm:          $linha['criado_em'] ?? null,
            atualizadoEm:      $linha['atualizado_em'] ?? null,
        );
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function listarTodos(?bool $ativo = null): array
    {
        if ($ativo !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM pecas WHERE ativo = :ativo ORDER BY nome'
            );
            $stmt->execute([':ativo' => (int) $ativo]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM pecas ORDER BY nome');
        }

        return array_map(
            fn(array $linha) => $this->montarPeca($linha),
            $stmt->fetchAll()
        );
    }

    public function buscarPorId(int $id): ?Peca
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pecas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();

        return $linha ? $this->montarPeca($linha) : null;
    }

    // ------------------------------------------------------------------
    // Comandos
    // ------------------------------------------------------------------

    public function salvar(Peca $peca): Peca
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pecas (nome, descricao, valor_unitario, quantidade_estoque, estoque_minimo, ativo)
             VALUES (:nome, :descricao, :valor_unitario, :quantidade_estoque, :estoque_minimo, :ativo)'
        );

        $stmt->execute([
            ':nome'               => $peca->getNome(),
            ':descricao'          => $peca->getDescricao(),
            ':valor_unitario'     => $peca->getValorUnitario(),
            ':quantidade_estoque' => $peca->getQuantidadeEstoque(),
            ':estoque_minimo'     => $peca->getEstoqueMinimo(),
            ':ativo'              => (int) $peca->isAtivo(),
        ]);

        return $this->buscarPorId((int) $this->pdo->lastInsertId());
    }

    public function atualizar(Peca $peca): Peca
    {
        $stmt = $this->pdo->prepare(
            'UPDATE pecas
             SET nome = :nome,
                 descricao = :descricao,
                 valor_unitario = :valor_unitario,
                 quantidade_estoque = :quantidade_estoque,
                 estoque_minimo = :estoque_minimo,
                 ativo = :ativo
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'                 => $peca->getId(),
            ':nome'               => $peca->getNome(),
            ':descricao'          => $peca->getDescricao(),
            ':valor_unitario'     => $peca->getValorUnitario(),
            ':quantidade_estoque' => $peca->getQuantidadeEstoque(),
            ':estoque_minimo'     => $peca->getEstoqueMinimo(),
            ':ativo'              => (int) $peca->isAtivo(),
        ]);

        return $this->buscarPorId($peca->getId());
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM pecas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
