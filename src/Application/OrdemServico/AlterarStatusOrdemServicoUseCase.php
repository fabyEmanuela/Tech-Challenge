<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use Repositories\OrdemServico\OrdemServicoRepositoryInterface;

class AlterarStatusOrdemServicoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository,
        private ?NotificadorOrdemServicoInterface $notificador = null,
    ) {}

    public function executar(int $id, string $novoStatusValor, ?string $observacao = null): OrdemServico
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        $novoStatus = StatusOrdemServico::fromValor($novoStatusValor);
        $statusAnterior = $os->status();

        if ($statusAnterior === $novoStatus) {
            return $os;
        }

        // Se for ações específicas
        if ($novoStatus === StatusOrdemServico::AGUARDANDO_APROVACAO) {
            $os->enviarOrcamentoParaAprovacao();
        } elseif ($novoStatus === StatusOrdemServico::EM_EXECUCAO) {
            $os->aprovarOrcamento();
        } elseif ($novoStatus === StatusOrdemServico::FINALIZADA) {
            $os->finalizar();
        } elseif ($novoStatus === StatusOrdemServico::ENTREGUE) {
            $os->entregar();
        } else {
            $os->alterarStatus($novoStatus);
        }

        $obs = $observacao ?: sprintf(
            'Status alterado de "%s" para "%s".',
            $statusAnterior->rotulo(),
            $novoStatus->rotulo()
        );

        $resultado = $this->ordemServicoRepository->atualizar(
            $os,
            new HistoricoStatusOS(
                id: null,
                statusDe: $statusAnterior->value,
                statusPara: $os->status()->value,
                observacao: $obs,
            )
        );
        $this->notificador?->notificar($resultado, $statusAnterior);
        return $resultado;
    }
}
