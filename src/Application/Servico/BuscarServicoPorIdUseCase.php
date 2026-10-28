<?php

namespace Application\Servico;

use Domain\Servico\Servico;
use Repositories\Servico\ServicoRepositoryInterface;
use RuntimeException;

class BuscarServicoPorIdUseCase
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    public function executar(int $id): Servico
    {
        $servico = $this->repository->buscarPorId($id);

        if ($servico === null) {
            throw new RuntimeException("Serviço com ID $id não encontrado.");
        }

        return $servico;
    }
}
