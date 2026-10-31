<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\OrdemServico;
use Domain\Repositories\OrdemServicoRepositoryInterface;

class AprovarOrcamentoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository
    ) {}

    public function executar(int $id, ?string $observacao = null): OrdemServico
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        $statusAnterior = $os->status();
        $os->aprovarOrcamento();

        $obs = $observacao ?: 'Orçamento aprovado pelo cliente. Serviços iniciados em execução.';

        return $this->ordemServicoRepository->atualizar(
            $os,
            new HistoricoStatusOS(
                id: null,
                statusDe: $statusAnterior->value,
                statusPara: $os->status()->value,
                observacao: $obs,
            )
        );
    }
}
