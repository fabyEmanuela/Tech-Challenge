<?php

namespace Repositories\Servico;

use Domain\Servico\Servico;

interface ServicoRepositoryInterface
{
    /**
     * @return Servico[]
     */
    public function listarTodos(): array;

    public function buscarPorId(int $id): ?Servico;

    /**
     * Busca serviços cujo nome contenha o termo informado (busca parcial / não exata).
     *
     * @return Servico[]
     */
    public function buscarPorNome(string $nome): array;

    public function salvar(Servico $servico): Servico;

    public function atualizar(Servico $servico): Servico;

    public function deletar(int $id): bool;
}
