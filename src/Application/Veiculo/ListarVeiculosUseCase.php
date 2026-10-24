<?php

declare(strict_types=1);

namespace Application\Veiculo;

use Domain\Entities\Veiculo;
use Domain\Repositories\VeiculoRepositoryInterface;

class ListarVeiculosUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $repository
    ) {}

    /**
     * @return Veiculo[]
     */
    public function executar(?int $clienteId = null): array
    {
        if ($clienteId !== null && $clienteId > 0) {
            return $this->repository->listarPorClienteId($clienteId);
        }

        return $this->repository->listarTodos();
    }
}
