<?php

namespace Application\Servico;

use Domain\Servico\Servico;
use Repositories\Servico\ServicoRepositoryInterface;

class BuscarServicoPorNomeUseCase
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    /**
     * Retorna os serviços que contêm o termo informado em seu nome (busca parcial).
     *
     * @return Servico[]
     */
    public function executar(string $nome): array
    {
        return $this->repository->buscarPorNome($nome);
    }
}
