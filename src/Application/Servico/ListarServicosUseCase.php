<?php

namespace Application\Servico;

use Domain\Servico\Servico;
use Repositories\Servico\ServicoRepositoryInterface;

class ListarServicosUseCase
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    /**
     * @return Servico[]
     */
    public function executar(?string $nome = null): array
    {
        if ($nome !== null && trim($nome) !== '') {
            return $this->repository->buscarPorNome(trim($nome));
        }

        return $this->repository->listarTodos();
    }
}
