<?php

namespace Repositories\Servico;

use Domain\Servico\Servico;
use PDO;

class ServicoPdoRepository implements ServicoRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function montarServico(array $linha): Servico
    {
        return new Servico(
            id:                 (int) $linha['id'],
            nome:               $linha['nome'],
            descricao:          $linha['descricao'] ?? null,
            preco:              (float) $linha['preco'],
            tempoEstimadoHoras: (float) $linha['tempo_estimado_horas'],
            ativo:              (bool) $linha['ativo'],
            criadoEm:           $linha['criado_em'] ?? null,
            atualizadoEm:       $linha['atualizado_em'] ?? null,
        );
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM servicos ORDER BY nome');
        return array_map(
            fn(array $linha) => $this->montarServico($linha),
            $stmt->fetchAll()
        );
    }

    public function buscarPorId(int $id): ?Servico
    {
        $stmt = $this->pdo->prepare('SELECT * FROM servicos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();

        return $linha ? $this->montarServico($linha) : null;
    }

    /**
     * Busca serviços cujo nome contenha o termo informado (busca parcial / não exata).
     */
    public function buscarPorNome(string $nome): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM servicos WHERE nome LIKE :nome ORDER BY nome');
        $stmt->execute([':nome' => '%' . $nome . '%']);
        return array_map(
            fn(array $linha) => $this->montarServico($linha),
            $stmt->fetchAll()
        );
    }

    // ------------------------------------------------------------------
    // Comandos
    // ------------------------------------------------------------------

    public function salvar(Servico $servico): Servico
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO servicos (nome, descricao, preco, tempo_estimado_horas, ativo)
             VALUES (:nome, :descricao, :preco, :tempo_estimado_horas, :ativo)'
        );

        $stmt->execute([
            ':nome'                 => $servico->getNome(),
            ':descricao'            => $servico->getDescricao(),
            ':preco'                => $servico->getPreco(),
            ':tempo_estimado_horas' => $servico->getTempoEstimadoHoras(),
            ':ativo'                => (int) $servico->isAtivo(),
        ]);

        return $this->buscarPorId((int) $this->pdo->lastInsertId());
    }

    public function atualizar(Servico $servico): Servico
    {
        $stmt = $this->pdo->prepare(
            'UPDATE servicos
             SET nome = :nome,
                 descricao = :descricao,
                 preco = :preco,
                 tempo_estimado_horas = :tempo_estimado_horas,
                 ativo = :ativo
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'                   => $servico->getId(),
            ':nome'                 => $servico->getNome(),
            ':descricao'            => $servico->getDescricao(),
            ':preco'                => $servico->getPreco(),
            ':tempo_estimado_horas' => $servico->getTempoEstimadoHoras(),
            ':ativo'                => (int) $servico->isAtivo(),
        ]);

        return $this->buscarPorId($servico->getId());
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM servicos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
