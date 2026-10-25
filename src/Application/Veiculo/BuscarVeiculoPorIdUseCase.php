<?php

declare(strict_types=1);

namespace Application\Veiculo;

use Domain\Entities\Veiculo;
use Domain\Repositories\VeiculoRepositoryInterface;

class BuscarVeiculoPorIdUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $repository
    ) {}

    public function executar(int $id): ?Veiculo
    {
        return $this->repository->buscarPorId($id);
    }
}
