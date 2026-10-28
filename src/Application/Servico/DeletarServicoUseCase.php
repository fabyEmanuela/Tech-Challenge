<?php

namespace Application\Servico;

use Repositories\Servico\ServicoRepositoryInterface;
use RuntimeException;

class DeletarServicoUseCase
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    public function executar(int $id): void
    {
        $servico = $this->repository->buscarPorId($id);

        if ($servico === null) {
            throw new RuntimeException("Serviço com ID $id não encontrado.");
        }

        $this->repository->deletar($id);
    }
}
