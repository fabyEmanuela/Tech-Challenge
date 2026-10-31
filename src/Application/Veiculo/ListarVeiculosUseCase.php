<?php

namespace Application\Veiculo;

use Domain\Veiculo\Veiculo;
use Repositories\Veiculo\VeiculoRepositoryInterface;

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
