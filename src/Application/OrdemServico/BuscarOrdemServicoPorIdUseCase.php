<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\OrdemServico;
use Domain\Repositories\OrdemServicoRepositoryInterface;

class BuscarOrdemServicoPorIdUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository
    ) {}

    public function executar(int $id): OrdemServico
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        return $os;
    }

    public function executarPorNumero(string $numeroOs): OrdemServico
    {
        $os = $this->ordemServicoRepository->buscarPorNumero($numeroOs);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com número "%s" não foi encontrada.', $numeroOs));
        }

        return $os;
    }
}
