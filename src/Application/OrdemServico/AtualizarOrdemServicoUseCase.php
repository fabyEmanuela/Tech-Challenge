<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\ItemPecaOS;
use Domain\OrdemServico\ItemServicoOS;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use Domain\Repositories\OrdemServicoRepositoryInterface;
use Repositories\Servico\ServicoRepositoryInterface;

class AtualizarOrdemServicoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository,
        private ServicoRepositoryInterface      $servicoRepository,
    ) {}

    /**
     * @param int $id
     * @param array{
     *     descricao_problema?: string,
     *     diagnostico?: string,
     *     servicos?: array,
     *     pecas?: array,
     *     enviar_orcamento?: bool
     * } $dados
     */
    public function executar(int $id, array $dados): OrdemServico
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        if ($os->status() === StatusOrdemServico::ENTREGUE) {
            throw new ValidacaoException('Não é possível alterar uma ordem de serviço que já foi entregue.');
        }

        $statusAnterior = $os->status();

        if (array_key_exists('descricao_problema', $dados)) {
            $os->atualizarDescricaoProblema($dados['descricao_problema']);
        }

        if (array_key_exists('diagnostico', $dados)) {
            $os->atualizarDiagnostico($dados['diagnostico']);
        }

        // Atualizar lista de serviços se fornecida
        if (isset($dados['servicos']) && is_array($dados['servicos'])) {
            $idsExistentes = array_filter(array_map(fn(ItemServicoOS $item) => $item->id(), $os->servicos()));
            $novosServicos = [];
            foreach ($dados['servicos'] as $itemS) {
                $novosServicos[] = $this->processarItemServico($itemS, $idsExistentes);
            }
            $os->definirServicos($novosServicos);
        }

        // Atualizar lista de peças se fornecida
        if (isset($dados['pecas']) && is_array($dados['pecas'])) {
            $idsExistentes = array_filter(array_map(fn(ItemPecaOS $item) => $item->id(), $os->pecas()));
            $novasPecas = [];
            foreach ($dados['pecas'] as $itemP) {
                $novasPecas[] = $this->processarItemPeca($itemP, $idsExistentes);
            }
            $os->definirPecas($novasPecas);
        }

        // Recalcular totais
        $os->recalcularTotais();

        // Se estava em RECEBIDA e ganhou diagnóstico/serviços/peças, avança para EM_DIAGNOSTICO
        if (
            $os->status() === StatusOrdemServico::RECEBIDA &&
            (!empty($os->diagnostico()) || count($os->servicos()) > 0 || count($os->pecas()) > 0)
        ) {
            $os->atualizarDiagnostico($os->diagnostico());
        }

        // Se solicitado envio do orçamento
        if (!empty($dados['enviar_orcamento']) && $dados['enviar_orcamento'] === true) {
            $os->enviarOrcamentoParaAprovacao();
        }

        // Se o status mudou automaticamente, registra no histórico
        if ($os->status() !== $statusAnterior) {
            $historico = new HistoricoStatusOS(
                null,
                $statusAnterior->value,
                $os->status()->value,
                'Status alterado automaticamente após atualização dos dados da OS.'
            );
        }

        return $this->ordemServicoRepository->atualizar($os, $historico ?? null);
    }

    private function processarItemServico(array $item, array $idsExistentes): ItemServicoOS
    {
        $servicoId = !empty($item['servico_id']) ? (int) $item['servico_id'] : null;
        if ($servicoId === null) {
            throw new ValidacaoException('O ID de um serviço cadastrado é obrigatório para incluí-lo na OS.');
        }

        $servicoDb = $this->servicoRepository->buscarPorId($servicoId);
        if ($servicoDb === null) {
            throw new ValidacaoException(sprintf('Serviço com ID %d não foi encontrado.', $servicoId));
        }

        $quantidade = isset($item['quantidade']) ? max(1, (int) $item['quantidade']) : 1;

        $itemServico = ItemServicoOS::criar(
            servicoId: $servicoId,
            nome: $servicoDb->getNome(),
            descricao: $servicoDb->getDescricao(),
            quantidade: $quantidade,
            precoUnitario: $servicoDb->getPreco(),
            tempoEstimadoHoras: $servicoDb->getTempoEstimadoHoras(),
        );

        $this->definirIdDoItemExistente($itemServico, $item, $idsExistentes);

        return $itemServico;
    }

    private function processarItemPeca(array $item, array $idsExistentes): ItemPecaOS
    {
        $descricao     = $item['descricao'] ?? null;
        $quantidade    = isset($item['quantidade']) ? (float) $item['quantidade'] : 1.0;
        $precoUnitario = isset($item['preco_unitario']) ? (float) $item['preco_unitario'] : null;
        $pecaId        = !empty($item['peca_id']) ? (int) $item['peca_id'] : null;

        if (!$descricao) {
            throw new ValidacaoException('A descrição da peça/insumo é obrigatória.');
        }

        if ($precoUnitario === null) {
            throw new ValidacaoException(sprintf('O preço unitário da peça/insumo "%s" é obrigatório.', $descricao));
        }

        $itemPeca = ItemPecaOS::criar(
            pecaId: $pecaId,
            descricao: $descricao,
            quantidade: $quantidade,
            precoUnitario: $precoUnitario,
        );

        $this->definirIdDoItemExistente($itemPeca, $item, $idsExistentes);

        return $itemPeca;
    }

    private function definirIdDoItemExistente(ItemServicoOS|ItemPecaOS $item, array $dados, array $idsExistentes): void
    {
        if (!isset($dados['id'])) {
            return;
        }

        $id = (int) $dados['id'];
        if (!in_array($id, $idsExistentes, true)) {
            throw new ValidacaoException('O item informado não pertence a esta ordem de serviço.');
        }

        $item->definirId($id);
    }
}
