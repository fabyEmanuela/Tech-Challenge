<?php

namespace Repositories\Peca;

use Domain\Peca\Peca;

interface PecaRepositoryInterface
{
    public function listarTodos(?bool $ativo = null): array;

    public function buscarPorId(int $id): ?Peca;

    public function salvar(Peca $peca): Peca;

    public function atualizar(Peca $peca): Peca;

    public function deletar(int $id): bool;
}
