<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\OrdemServico;
use Repositories\OrdemServico\OrdemServicoRepositoryInterface;

class EnviarOrcamentoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository,
        private ?NotificadorOrdemServicoInterface $notificador = null,
    ) {}

    public function executar(int $id, ?string $observacao = null): OrdemServico
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        $statusAnterior = $os->status();
        $os->enviarOrcamentoParaAprovacao();

        $obs = $observacao ?: sprintf(
            'Orçamento gerado e enviado ao cliente. Valor total: R$ %s (Serviços: R$ %s, Peças: R$ %s).',
            number_format($os->valorTotal(), 2, ',', '.'),
            number_format($os->valorServicos(), 2, ',', '.'),
            number_format($os->valorPecas(), 2, ',', '.')
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
