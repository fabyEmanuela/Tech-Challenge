<?php

namespace Application\Peca;

use Domain\Peca\Peca;
use Repositories\Peca\PecaRepositoryInterface;
use RuntimeException;

class BuscarPecaPorIdUseCase
{
    public function __construct(
        private PecaRepositoryInterface $repository
    ) {}

    public function executar(int $id): Peca
    {
        $peca = $this->repository->buscarPorId($id);

        if ($peca === null) {
            throw new RuntimeException("Peça com ID $id não encontrada.");
        }

        return $peca;
    }
}

