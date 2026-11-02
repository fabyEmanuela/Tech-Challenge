<?php

declare(strict_types=1);

namespace Tests\Unit\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\ItemPecaOS;
use Domain\OrdemServico\ItemServicoOS;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use PHPUnit\Framework\TestCase;

class OrdemServicoTest extends TestCase
{
    public function testDeveCriarOrdemServicoValida(): void
    {
        $os = OrdemServico::criar(
            numeroOs:          'OS-20261101-0001',
            clienteId:         10,
            veiculoId:         20,
            descricaoProblema: 'Troca de pastilhas de freio dianteiras.',
            diagnostico:       'Discos em bom estado, troca imediata recomendada.',
            status:            StatusOrdemServico::RECEBIDA,
        );

        $this->assertNull($os->id());
        $this->assertSame('OS-20261101-0001', $os->numeroOs());
        $this->assertSame(10, $os->clienteId());
        $this->assertSame(20, $os->veiculoId());
        $this->assertSame(StatusOrdemServico::RECEBIDA, $os->status());
        $this->assertSame('Troca de pastilhas de freio dianteiras.', $os->descricaoProblema());
        $this->assertSame('Discos em bom estado, troca imediata recomendada.', $os->diagnostico());
        $this->assertSame(0.0, $os->valorServicos());
        $this->assertSame(0.0, $os->valorPecas());
        $this->assertSame(0.0, $os->valorTotal());
        $this->assertEmpty($os->servicos());
        $this->assertEmpty($os->pecas());

        $os->definirId(100);
        $this->assertSame(100, $os->id());
    }

    public function testDeveCalcularOrcamentoAutomaticamenteAoAdicionarItens(): void
    {
        $os = OrdemServico::criar(
            numeroOs:  'OS-20261101-0002',
            clienteId: 5,
            veiculoId: 8,
        );

        $os->adicionarServico(ItemServicoOS::criar(
            servicoId:          1,
            nome:               'Troca de Óleo e Filtro',
            descricao:          'Mão de obra',
            quantidade:         2,
            precoUnitario:      150.00,
            tempoEstimadoHoras: 1.0,
        ));

        $os->adicionarPeca(ItemPecaOS::criar(
            pecaId:        10,
            descricao:     'Litro Óleo 5W30 Sintético',
            quantidade:    3.0,
            precoUnitario: 50.00,
        ));

        $this->assertSame(300.00, $os->valorServicos());
        $this->assertSame(150.00, $os->valorPecas());
        $this->assertSame(450.00, $os->valorTotal());
        $this->assertSame(2.0, $os->tempoEstimadoTotalHoras());
        $this->assertCount(1, $os->servicos());
        $this->assertCount(1, $os->pecas());
    }

    public function testDeveDefinirListasDeItensEHistoricoEDadosAuxiliares(): void
    {
        $os = OrdemServico::criar('OS-20261101-0003', 1, 1);

        $servico = ItemServicoOS::criar(1, 'Revisão', null, 1, 200.0, 2.5);
        $peca = ItemPecaOS::criar(2, 'Vela de Ignição', 4.0, 25.0);
        $historico = new HistoricoStatusOS(1, 'recebida', 'em_diagnostico', 'Observação teste');

        $os->definirServicos([$servico]);
        $os->definirPecas([$peca]);
        $os->definirHistorico([$historico]);
        $os->adicionarHistorico(new HistoricoStatusOS(2, 'em_diagnostico', 'aguardando_aprovacao'));

        $os->definirDadosCliente(['id' => 1, 'nome' => 'Carlos Silva', 'cpf' => '111.444.777-35']);
        $os->definirDadosVeiculo(['id' => 1, 'placa' => 'ABC1D23', 'marca' => 'Ford', 'modelo' => 'Ka', 'ano' => 2020, 'cor' => 'Branco']);
        $os->definirTotais(200.0, 100.0, 300.0);

        $this->assertCount(1, $os->servicos());
        $this->assertCount(1, $os->pecas());
        $this->assertCount(2, $os->historico());
        $this->assertSame(300.0, $os->valorTotal());
    }

    public function testDeveAtualizarDescricaoEDiagnostico(): void
    {
        $os = OrdemServico::criar('OS-20261101-0004', 1, 1);

        $os->atualizarDescricaoProblema('Barulho na suspensão dianteira');
        $this->assertSame('Barulho na suspensão dianteira', $os->descricaoProblema());

        // Atualizar diagnóstico quando em RECEBIDA avança para EM_DIAGNOSTICO
        $os->atualizarDiagnostico('Buchas da bandeja estouradas');
        $this->assertSame('Buchas da bandeja estouradas', $os->diagnostico());
        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO, $os->status());
    }

    public function testDeveAvancarStatusCorretamenteNoCicloDeVida(): void
    {
        $os = OrdemServico::criar('OS-20261101-0005', 1, 1);

        $os->adicionarServico(ItemServicoOS::criar(1, 'Troca de Óleo', null, 1, 100.0));

        $this->assertSame(StatusOrdemServico::RECEBIDA, $os->status());

        $os->alterarStatus(StatusOrdemServico::EM_DIAGNOSTICO);
        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO, $os->status());

        $os->enviarOrcamentoParaAprovacao();
        $this->assertSame(StatusOrdemServico::AGUARDANDO_APROVACAO, $os->status());
        $this->assertNotNull($os->orcamentoEnviadoEm());

        $os->aprovarOrcamento();
        $this->assertSame(StatusOrdemServico::EM_EXECUCAO, $os->status());
        $this->assertNotNull($os->orcamentoAprovadoEm());

        $os->finalizar();
        $this->assertSame(StatusOrdemServico::FINALIZADA, $os->status());
        $this->assertNotNull($os->finalizadoEm());

        $os->entregar();
        $this->assertSame(StatusOrdemServico::ENTREGUE, $os->status());
        $this->assertNotNull($os->entregueEm());
    }

    public function testDevePreencherTimestampsAoAlterarStatusDiretamente(): void
    {
        $os = OrdemServico::criar('OS-20261101-0006', 1, 1);
        $os->adicionarPeca(ItemPecaOS::criar(1, 'Filtro', 1.0, 50.0));

        $os->alterarStatus(StatusOrdemServico::EM_DIAGNOSTICO);
        $os->alterarStatus(StatusOrdemServico::AGUARDANDO_APROVACAO);
        $this->assertNotNull($os->orcamentoEnviadoEm());

        $os->alterarStatus(StatusOrdemServico::EM_EXECUCAO);
        $this->assertNotNull($os->orcamentoAprovadoEm());

        $os->alterarStatus(StatusOrdemServico::FINALIZADA);
        $this->assertNotNull($os->finalizadoEm());

        $os->alterarStatus(StatusOrdemServico::ENTREGUE);
        $this->assertNotNull($os->entregueEm());
    }

    public function testDeveExportarParaArrayEParaAcompanhamentoCliente(): void
    {
        $os = OrdemServico::criar(
            numeroOs:          'OS-20261101-0007',
            clienteId:         1,
            veiculoId:         2,
            descricaoProblema: 'Troca de pastilhas',
            diagnostico:       'Desgaste acentuado'
        );

        $os->definirId(10);
        $os->adicionarServico(ItemServicoOS::criar(1, 'Mão de obra', null, 1, 80.0, 1.0));
        $os->adicionarPeca(ItemPecaOS::criar(2, 'Pastilha Cobreq', 1.0, 120.0));
        $os->definirDadosCliente(['id' => 1, 'nome' => 'Ana Clara', 'cpf' => '111.444.777-35']);
        $os->definirDadosVeiculo(['id' => 2, 'placa' => 'ABC1D23', 'marca' => 'Fiat', 'modelo' => 'Pulse', 'ano' => 2023, 'cor' => 'Cinza']);

        $array = $os->toArray();
        $this->assertSame(10, $array['id']);
        $this->assertSame('OS-20261101-0007', $array['numero_os']);
        $this->assertSame(200.0, $array['orcamento']['valor_total']);
        $this->assertCount(1, $array['servicos']);
        $this->assertCount(1, $array['pecas']);

        $clienteView = $os->paraAcompanhamentoCliente();
        $this->assertSame('OS-20261101-0007', $clienteView['numero_os']);
        $this->assertSame('recebida', $clienteView['status_atual']);
        $this->assertGreaterThan(0, $clienteView['percentual_progresso']);
        $this->assertSame('Ana Clara', $clienteView['cliente']['nome']);
        $this->assertSame('ABC1D23', $clienteView['veiculo']['placa']);
        $this->assertNotEmpty($clienteView['linha_do_tempo']);
    }

    public function testDeveLancarExcecaoAoEnviarOrcamentoSemItens(): void
    {
        $os = OrdemServico::criar('OS-20261101-0008', 1, 1);

        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('O orçamento deve conter ao menos um serviço ou peça para ser enviado.');

        $os->enviarOrcamentoParaAprovacao();
    }

    public function testDeveLancarExcecaoAoEnviarOrcamentoParaOSJaEntregue(): void
    {
        $os = new OrdemServico(
            id: 1,
            numeroOs: 'OS-ENTREGUE',
            clienteId: 1,
            veiculoId: 1,
            status: StatusOrdemServico::ENTREGUE
        );

        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('Não é possível enviar orçamento para uma OS já entregue.');

        $os->enviarOrcamentoParaAprovacao();
    }

    public function testDeveLancarExcecaoAoAprovarOrcamentoComStatusIncompativel(): void
    {
        $os = OrdemServico::criar('OS-20261101-0009', 1, 1);

        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('Apenas ordens no status "Aguardando aprovação" podem ser aprovadas.');

        $os->aprovarOrcamento();
    }

    public function testDeveLancarExcecaoAoFinalizarOSComStatusIncompativel(): void
    {
        $os = OrdemServico::criar('OS-20261101-0010', 1, 1);

        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('Apenas ordens no status "Em execução" podem ser finalizadas.');

        $os->finalizar();
    }

    public function testDeveLancarExcecaoAoEntregarOSComStatusIncompativel(): void
    {
        $os = OrdemServico::criar('OS-20261101-0011', 1, 1);

        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('Apenas ordens no status "Finalizada" podem ser entregues.');

        $os->entregar();
    }

    public function testDeveLancarExcecaoAoTentarTransicaoDeStatusProibida(): void
    {
        $os = OrdemServico::criar('OS-20261101-0012', 1, 1);

        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('Transição não permitida do status "Recebida" para "Finalizada".');

        $os->alterarStatus(StatusOrdemServico::FINALIZADA);
    }

    public function testDeveLancarExcecaoQuandoNumeroOsForVazio(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('O número da OS é obrigatório.');

        OrdemServico::criar(numeroOs: '   ', clienteId: 1, veiculoId: 1);
    }

    public function testDeveLancarExcecaoQuandoClienteIdForInvalido(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('A OS deve pertencer a um cliente válido.');

        OrdemServico::criar(numeroOs: 'OS-20261101-0013', clienteId: 0, veiculoId: 1);
    }

    public function testDeveLancarExcecaoQuandoVeiculoIdForInvalido(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('A OS deve estar vinculada a um veículo válido.');

        OrdemServico::criar(numeroOs: 'OS-20261101-0014', clienteId: 1, veiculoId: -1);
    }
}
