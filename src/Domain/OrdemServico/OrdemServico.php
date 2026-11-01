<?php

declare(strict_types=1);

namespace Domain\OrdemServico;

use Domain\Exceptions\ValidacaoException;

final class OrdemServico
{
    /** @var ItemServicoOS[] */
    private array $servicos = [];

    /** @var ItemPecaOS[] */
    private array $pecas = [];

    /** @var HistoricoStatusOS[] */
    private array $historico = [];

    private float $valorServicos = 0.0;
    private float $valorPecas = 0.0;
    private float $valorTotal = 0.0;

    private ?array $dadosCliente = null;
    private ?array $dadosVeiculo = null;

    public function __construct(
        private ?int $id,
        private string $numeroOs,
        private int $clienteId,
        private int $veiculoId,
        private StatusOrdemServico $status,
        private ?string $descricaoProblema = null,
        private ?string $diagnostico = null,
        private ?string $orcamentoEnviadoEm = null,
        private ?string $orcamentoAprovadoEm = null,
        private ?string $finalizadoEm = null,
        private ?string $entregueEm = null,
        private ?string $criadoEm = null,
        private ?string $atualizadoEm = null,
    ) {
        $this->validar();
    }

    public static function criar(
        string $numeroOs,
        int $clienteId,
        int $veiculoId,
        ?string $descricaoProblema = null,
        ?string $diagnostico = null,
        StatusOrdemServico $status = StatusOrdemServico::RECEBIDA,
    ): self {
        return new self(
            id: null,
            numeroOs: trim($numeroOs),
            clienteId: $clienteId,
            veiculoId: $veiculoId,
            status: $status,
            descricaoProblema: $descricaoProblema !== null ? trim($descricaoProblema) : null,
            diagnostico: $diagnostico !== null ? trim($diagnostico) : null,
            orcamentoEnviadoEm: null,
            orcamentoAprovadoEm: null,
            finalizadoEm: null,
            entregueEm: null,
            criadoEm: null,
            atualizadoEm: null,
        );
    }

    private function validar(): void
    {
        if (trim($this->numeroOs) === '') {
            throw new ValidacaoException('O número da OS é obrigatório.');
        }

        if ($this->clienteId <= 0) {
            throw new ValidacaoException('A OS deve pertencer a um cliente válido.');
        }

        if ($this->veiculoId <= 0) {
            throw new ValidacaoException('A OS deve estar vinculada a um veículo válido.');
        }
    }

    // ------------------------------------------------------------------
    // Gestão de Itens e Orçamento
    // ------------------------------------------------------------------

    public function adicionarServico(ItemServicoOS $item): void
    {
        $this->servicos[] = $item;
        $this->recalcularTotais();
    }

    /**
     * @param ItemServicoOS[] $servicos
     */
    public function definirServicos(array $servicos): void
    {
        $this->servicos = $servicos;
        $this->recalcularTotais();
    }

    public function adicionarPeca(ItemPecaOS $item): void
    {
        $this->pecas[] = $item;
        $this->recalcularTotais();
    }

    /**
     * @param ItemPecaOS[] $pecas
     */
    public function definirPecas(array $pecas): void
    {
        $this->pecas = $pecas;
        $this->recalcularTotais();
    }

    /**
     * @param HistoricoStatusOS[] $historico
     */
    public function definirHistorico(array $historico): void
    {
        $this->historico = $historico;
    }

    public function adicionarHistorico(HistoricoStatusOS $item): void
    {
        $this->historico[] = $item;
    }

    public function recalcularTotais(): void
    {
        $totalServicos = 0.0;
        foreach ($this->servicos as $servico) {
            $totalServicos += $servico->subtotal();
        }

        $totalPecas = 0.0;
        foreach ($this->pecas as $peca) {
            $totalPecas += $peca->subtotal();
        }

        $this->valorServicos = round($totalServicos, 2);
        $this->valorPecas    = round($totalPecas, 2);
        $this->valorTotal    = round($this->valorServicos + $this->valorPecas, 2);
    }

    public function tempoEstimadoTotalHoras(): float
    {
        $totalHoras = 0.0;
        foreach ($this->servicos as $servico) {
            $totalHoras += $servico->tempoEstimadoHoras() * $servico->quantidade();
        }
        return round($totalHoras, 2);
    }

    // ------------------------------------------------------------------
    // Ações de Mudança de Status e Fluxo do Negócio
    // ------------------------------------------------------------------

    public function atualizarDiagnostico(?string $diagnostico): void
    {
        $this->diagnostico = $diagnostico !== null ? trim($diagnostico) : null;

        if ($this->status === StatusOrdemServico::RECEBIDA && $this->diagnostico !== null) {
            $this->status = StatusOrdemServico::EM_DIAGNOSTICO;
        }
    }

    public function atualizarDescricaoProblema(?string $descricao): void
    {
        $this->descricaoProblema = $descricao !== null ? trim($descricao) : null;
    }

    /**
     * Envia o orçamento gerado ao cliente para aprovação.
     * Altera o status automaticamente para Aguardando aprovação.
     */
    public function enviarOrcamentoParaAprovacao(): void
    {
        if ($this->status === StatusOrdemServico::ENTREGUE) {
            throw new ValidacaoException('Não é possível enviar orçamento para uma OS já entregue.');
        }

        if (count($this->servicos) === 0 && count($this->pecas) === 0) {
            throw new ValidacaoException('O orçamento deve conter ao menos um serviço ou peça para ser enviado.');
        }

        $this->recalcularTotais();
        $this->status = StatusOrdemServico::AGUARDANDO_APROVACAO;
        $this->orcamentoEnviadoEm = date('Y-m-d H:i:s');
    }

    /**
     * Aprovação do orçamento pelo cliente.
     * Transiciona o status automaticamente para Em execução.
     */
    public function aprovarOrcamento(): void
    {
        if ($this->status !== StatusOrdemServico::AGUARDANDO_APROVACAO) {
            throw new ValidacaoException(
                sprintf('Apenas ordens no status "Aguardando aprovação" podem ser aprovadas. Status atual: %s.', $this->status->rotulo())
            );
        }

        $this->status = StatusOrdemServico::EM_EXECUCAO;
        $this->orcamentoAprovadoEm = date('Y-m-d H:i:s');
    }

    /**
     * Finalização dos serviços pela equipe.
     * Transiciona o status automaticamente para Finalizada.
     */
    public function finalizar(): void
    {
        if ($this->status !== StatusOrdemServico::EM_EXECUCAO) {
            throw new ValidacaoException(
                sprintf('Apenas ordens no status "Em execução" podem ser finalizadas. Status atual: %s.', $this->status->rotulo())
            );
        }

        $this->status = StatusOrdemServico::FINALIZADA;
        $this->finalizadoEm = date('Y-m-d H:i:s');
    }

    /**
     * Entrega do veículo ao cliente.
     * Transiciona o status automaticamente para Entregue.
     */
    public function entregar(): void
    {
        if ($this->status !== StatusOrdemServico::FINALIZADA) {
            throw new ValidacaoException(
                sprintf('Apenas ordens no status "Finalizada" podem ser entregues. Status atual: %s.', $this->status->rotulo())
            );
        }

        $this->status = StatusOrdemServico::ENTREGUE;
        $this->entregueEm = date('Y-m-d H:i:s');
    }

    /**
     * Alteração direta de status validando transição permitida.
     */
    public function alterarStatus(StatusOrdemServico $novoStatus): void
    {
        if (!$this->status->podeAvancarPara($novoStatus)) {
            throw new ValidacaoException(
                sprintf('Transição não permitida do status "%s" para "%s".', $this->status->rotulo(), $novoStatus->rotulo())
            );
        }

        $this->status = $novoStatus;

        if ($novoStatus === StatusOrdemServico::AGUARDANDO_APROVACAO && $this->orcamentoEnviadoEm === null) {
            $this->orcamentoEnviadoEm = date('Y-m-d H:i:s');
        } elseif ($novoStatus === StatusOrdemServico::EM_EXECUCAO && $this->orcamentoAprovadoEm === null) {
            $this->orcamentoAprovadoEm = date('Y-m-d H:i:s');
        } elseif ($novoStatus === StatusOrdemServico::FINALIZADA && $this->finalizadoEm === null) {
            $this->finalizadoEm = date('Y-m-d H:i:s');
        } elseif ($novoStatus === StatusOrdemServico::ENTREGUE && $this->entregueEm === null) {
            $this->entregueEm = date('Y-m-d H:i:s');
        }
    }

    // ------------------------------------------------------------------
    // Carregamento de relacionamentos para exibição
    // ------------------------------------------------------------------

    public function definirDadosCliente(?array $cliente): void
    {
        $this->dadosCliente = $cliente;
    }

    public function definirDadosVeiculo(?array $veiculo): void
    {
        $this->dadosVeiculo = $veiculo;
    }

    public function definirTotais(float $servicos, float $pecas, float $total): void
    {
        $this->valorServicos = $servicos;
        $this->valorPecas    = $pecas;
        $this->valorTotal    = $total;
    }

    // ------------------------------------------------------------------
    // Getters
    // ------------------------------------------------------------------

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        $this->id = $id;
    }

    public function numeroOs(): string
    {
        return $this->numeroOs;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function veiculoId(): int
    {
        return $this->veiculoId;
    }

    public function status(): StatusOrdemServico
    {
        return $this->status;
    }

    public function descricaoProblema(): ?string
    {
        return $this->descricaoProblema;
    }

    public function diagnostico(): ?string
    {
        return $this->diagnostico;
    }

    public function valorServicos(): float
    {
        return $this->valorServicos;
    }

    public function valorPecas(): float
    {
        return $this->valorPecas;
    }

    public function valorTotal(): float
    {
        return $this->valorTotal;
    }

    public function orcamentoEnviadoEm(): ?string
    {
        return $this->orcamentoEnviadoEm;
    }

    public function orcamentoAprovadoEm(): ?string
    {
        return $this->orcamentoAprovadoEm;
    }

    public function finalizadoEm(): ?string
    {
        return $this->finalizadoEm;
    }

    public function entregueEm(): ?string
    {
        return $this->entregueEm;
    }

    public function criadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function atualizadoEm(): ?string
    {
        return $this->atualizadoEm;
    }

    /**
     * @return ItemServicoOS[]
     */
    public function servicos(): array
    {
        return $this->servicos;
    }

    /**
     * @return ItemPecaOS[]
     */
    public function pecas(): array
    {
        return $this->pecas;
    }

    /**
     * @return HistoricoStatusOS[]
     */
    public function historico(): array
    {
        return $this->historico;
    }

    // ------------------------------------------------------------------
    // Serialização
    // ------------------------------------------------------------------

    private array $avisosEstoque = [];
    private bool $inicioPendentePorEstoque = false;

    public function definirAvisosEstoque(array $avisos, bool $pendente = false): void
    {
        $this->avisosEstoque = $avisos;
        $this->inicioPendentePorEstoque = $pendente;
    }

    public function avisosEstoque(): array { return $this->avisosEstoque; }

    public function inicioPendentePorEstoque(): bool { return $this->inicioPendentePorEstoque; }

    public function toArray(): array
    {
        return [
            'avisos_estoque'              => $this->avisosEstoque,
            'inicio_pendente_por_estoque' => $this->inicioPendentePorEstoque,
            'id'                   => $this->id,
            'numero_os'            => $this->numeroOs,
            'cliente_id'           => $this->clienteId,
            'cliente'              => $this->dadosCliente,
            'veiculo_id'           => $this->veiculoId,
            'veiculo'              => $this->dadosVeiculo,
            'status'               => $this->status->value,
            'status_rotulo'        => $this->status->rotulo(),
            'descricao_problema'   => $this->descricaoProblema,
            'diagnostico'          => $this->diagnostico,
            'orcamento'            => [
                'valor_servicos'       => $this->valorServicos,
                'valor_pecas'          => $this->valorPecas,
                'valor_total'          => $this->valorTotal,
                'tempo_estimado_horas' => $this->tempoEstimadoTotalHoras(),
                'enviado_ao_cliente'   => $this->orcamentoEnviadoEm !== null,
                'enviado_em'           => $this->orcamentoEnviadoEm,
                'aprovado_pelo_cliente'=> $this->orcamentoAprovadoEm !== null,
                'aprovado_em'          => $this->orcamentoAprovadoEm,
            ],
            'servicos'             => array_map(fn(ItemServicoOS $s) => $s->toArray(), $this->servicos),
            'pecas'                => array_map(fn(ItemPecaOS $p) => $p->toArray(), $this->pecas),
            'historico'            => array_map(fn(HistoricoStatusOS $h) => $h->toArray(), $this->historico),
            'datas'                => [
                'criado_em'            => $this->criadoEm,
                'atualizado_em'        => $this->atualizadoEm,
                'orcamento_enviado_em' => $this->orcamentoEnviadoEm,
                'orcamento_aprovado_em'=> $this->orcamentoAprovadoEm,
                'finalizado_em'        => $this->finalizadoEm,
                'entregue_em'          => $this->entregueEm,
            ],
        ];
    }

    /**
     * Visão formatada para acompanhamento da OS pelo cliente.
     */
    public function paraAcompanhamentoCliente(): array
    {
        $statusEnumList = [
            StatusOrdemServico::RECEBIDA,
            StatusOrdemServico::EM_DIAGNOSTICO,
            StatusOrdemServico::AGUARDANDO_APROVACAO,
            StatusOrdemServico::EM_EXECUCAO,
            StatusOrdemServico::FINALIZADA,
            StatusOrdemServico::ENTREGUE,
        ];

        $ordemAtual = $this->status->ordem();

        $progressoEtapas = array_map(function (StatusOrdemServico $st) use ($ordemAtual) {
            $ordem = $st->ordem();
            $concluido = $ordem < $ordemAtual || ($ordem === $ordemAtual && $st === StatusOrdemServico::ENTREGUE);
            $emAndamento = ($ordem === $ordemAtual && $st !== StatusOrdemServico::ENTREGUE);
            $pendente = $ordem > $ordemAtual;

            return [
                'status'       => $st->value,
                'nome'         => $st->rotulo(),
                'descricao'    => $st->descricao(),
                'ordem'        => $ordem,
                'concluido'    => $concluido,
                'em_andamento' => $emAndamento,
                'pendente'     => $pendente,
            ];
        }, $statusEnumList);

        $percentual = (int) round(($ordemAtual / count($statusEnumList)) * 100);

        return [
            'numero_os'           => $this->numeroOs,
            'status_atual'        => $this->status->value,
            'status_rotulo'       => $this->status->rotulo(),
            'status_descricao'    => $this->status->descricao(),
            'percentual_progresso'=> $percentual,
            'veiculo'             => $this->dadosVeiculo ? [
                'placa'   => $this->dadosVeiculo['placa'] ?? null,
                'marca'   => $this->dadosVeiculo['marca'] ?? null,
                'modelo'  => $this->dadosVeiculo['modelo'] ?? null,
                'ano'     => $this->dadosVeiculo['ano'] ?? $this->dadosVeiculo['ano_modelo'] ?? null,
                'cor'     => $this->dadosVeiculo['cor'] ?? null,
            ] : null,
            'cliente'             => $this->dadosCliente ? [
                'nome' => $this->dadosCliente['nome'] ?? null,
                'cpf'  => $this->dadosCliente['cpf'] ?? null,
            ] : null,
            'descricao_problema'  => $this->descricaoProblema,
            'diagnostico'         => $this->diagnostico,
            'orcamento'           => [
                'valor_servicos'       => $this->valorServicos,
                'valor_pecas'          => $this->valorPecas,
                'valor_total'          => $this->valorTotal,
                'tempo_estimado_horas' => $this->tempoEstimadoTotalHoras(),
                'aguardando_sua_aprovacao' => $this->status === StatusOrdemServico::AGUARDANDO_APROVACAO,
            ],
            'servicos'            => array_map(fn(ItemServicoOS $s) => [
                'nome'                 => $s->nome(),
                'descricao'            => $s->descricao(),
                'quantidade'           => $s->quantidade(),
                'preco_unitario'       => $s->precoUnitario(),
                'subtotal'             => $s->subtotal(),
                'tempo_estimado_horas' => $s->tempoEstimadoHoras(),
            ], $this->servicos),
            'pecas'               => array_map(fn(ItemPecaOS $p) => [
                'descricao'      => $p->descricao(),
                'quantidade'     => $p->quantidade(),
                'preco_unitario' => $p->precoUnitario(),
                'subtotal'       => $p->subtotal(),
            ], $this->pecas),
            'linha_do_tempo'      => $progressoEtapas,
            'historico_eventos'   => array_map(fn(HistoricoStatusOS $h) => $h->toArray(), $this->historico),
            'datas'               => [
                'abertura'             => $this->criadoEm,
                'orcamento_enviado'    => $this->orcamentoEnviadoEm,
                'orcamento_aprovado'   => $this->orcamentoAprovadoEm,
                'finalizacao'          => $this->finalizadoEm,
                'entrega'              => $this->entregueEm,
                'ultima_atualizacao'   => $this->atualizadoEm,
            ],
        ];
    }
}
