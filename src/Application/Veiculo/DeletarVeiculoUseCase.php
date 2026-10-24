<?php

declare(strict_types=1);

namespace Application\Veiculo;

use Domain\Exceptions\ValidacaoException;
use Domain\Repositories\VeiculoRepositoryInterface;

class DeletarVeiculoUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $repository
    ) {}

    public function executar(int $id): bool
    {
        $veiculo = $this->repository->buscarPorId($id);
        if ($veiculo === null) {
            throw new ValidacaoException("Veículo com ID {$id} não encontrado.");
        }

        return $this->repository->deletar($id);
    }
}
