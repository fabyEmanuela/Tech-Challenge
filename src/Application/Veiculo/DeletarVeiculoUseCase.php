<?php

namespace Application\Veiculo;

use Repositories\Veiculo\VeiculoRepositoryInterface;
use RuntimeException;

class DeletarVeiculoUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $repository
    ) {}

    public function executar(int $id): void
    {
        $veiculo = $this->repository->buscarPorId($id);

        if ($veiculo === null) {
            throw new RuntimeException("Veículo com ID $id não encontrado.");
        }

        $this->repository->deletar($id);
    }
}
