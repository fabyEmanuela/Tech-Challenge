<?php

namespace Application\Peca;

use Repositories\Peca\PecaRepositoryInterface;
use RuntimeException;

class DeletarPecaUseCase
{
    public function __construct(
        private PecaRepositoryInterface $repository
    ) {}

    public function executar(int $id): void
    {
        $peca = $this->repository->buscarPorId($id);

        if ($peca === null) {
            throw new RuntimeException("Peça com ID $id não encontrada.");
        }

        $this->repository->deletar($id);
    }
}

