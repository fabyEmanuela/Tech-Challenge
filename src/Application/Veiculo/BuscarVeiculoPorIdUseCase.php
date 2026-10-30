<?php

namespace Application\Veiculo;

use Domain\Veiculo\Veiculo;
use Repositories\Veiculo\VeiculoRepositoryInterface;
use RuntimeException;

class BuscarVeiculoPorIdUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $repository
    ) {}

    public function executar(int $id): Veiculo
    {
        $veiculo = $this->repository->buscarPorId($id);

        if ($veiculo === null) {
            throw new RuntimeException("Veículo com ID $id não encontrado.");
        }

        return $veiculo;
    }
}
