<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\StatusOrdemServico;
use Domain\Repositories\OrdemServicoRepositoryInterface;

class DeletarOrdemServicoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository
    ) {}

    public function executar(int $id): bool
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        if (in_array($os->status(), [StatusOrdemServico::EM_EXECUCAO, StatusOrdemServico::FINALIZADA, StatusOrdemServico::ENTREGUE], true)) {
            throw new ValidacaoException(
                sprintf('Não é possível remover uma OS no status "%s". Apenas ordens canceladas ou ainda não executadas podem ser excluídas.', $os->status()->rotulo())
            );
        }

        return $this->ordemServicoRepository->deletar($id);
    }
}
