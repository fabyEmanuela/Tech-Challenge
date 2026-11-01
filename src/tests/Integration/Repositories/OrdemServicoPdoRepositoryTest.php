<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\ItemPecaOS;
use Domain\OrdemServico\ItemServicoOS;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use Domain\Peca\Peca;
use Domain\Servico\Servico;
use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use Repositories\OrdemServico\OrdemServicoPdoRepository;
use Repositories\Peca\PecaPdoRepository;
use Repositories\Servico\ServicoPdoRepository;
use Repositories\Usuario\UsuarioPdoRepository;
use Repositories\Veiculo\VeiculoPdoRepository;
use Tests\Integration\IntegrationTestCase;

class OrdemServicoPdoRepositoryTest extends IntegrationTestCase
{
    private OrdemServicoPdoRepository $osRepository;
    private UsuarioPdoRepository      $usuarioRepository;
    private VeiculoPdoRepository      $veiculoRepository;
    private ServicoPdoRepository      $servicoRepository;
    private PecaPdoRepository         $pecaRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->osRepository      = new OrdemServicoPdoRepository($this->pdo);
        $this->usuarioRepository = new UsuarioPdoRepository($this->pdo);
        $this->veiculoRepository = new VeiculoPdoRepository($this->pdo);
        $this->servicoRepository = new ServicoPdoRepository($this->pdo);
        $this->pecaRepository    = new PecaPdoRepository($this->pdo);
    }

    private function criarClienteFixture(): Usuario
    {
        $cliente = new Usuario(
            id:           null,
            nome:         'Cliente Teste OS',
            email:        'cliente.os.' . uniqid() . '@gmail.com',
            telefone:     '(11) 97777-2222',
            cpf:          '448.241.690-30',
            tipoUsuario:  TipoUsuario::CLIENTE,
            senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        return $this->usuarioRepository->salvar($cliente);
    }

    private function criarVeiculoFixture(int $clienteId): Veiculo
    {
        $veiculo = new Veiculo(
            id:           null,
            clienteId:    $clienteId,
            placa:        new Placa('XYZ9B88'),
            marca:        'Honda',
            modelo:       'Civic Touring 1.5',
            ano:          2022,
            cor:          'Branco',
            criadoEm:     null,
            atualizadoEm: null,
        );

        return $this->veiculoRepository->salvar($veiculo);
    }

    private function criarServicoFixture(): Servico
    {
        $servico = new Servico(
            id:                   null,
            nome:                 'Alinhamento e Balanceamento 3D',
            descricao:            'Alinhamento completo 4 rodas',
            preco:                180.00,
            tempoEstimadoHoras:   1.5,
            ativo:                true,
            criadoEm:             null,
            atualizadoEm:         null,
        );

        return $this->servicoRepository->salvar($servico);
    }

    private function criarPecaFixture(): Peca
    {
        $peca = new Peca(
            id:                null,
            nome:              'Pastilha de Freio Cerâmica',
            descricao:         'Jogo dianteiro cerâmica',
            valorUnitario:     220.00,
            quantidadeEstoque: 10,
            estoqueMinimo:     2,
            ativo:             true,
            criadoEm:          null,
            atualizadoEm:      null,
        );

        return $this->pecaRepository->salvar($peca);
    }

    public function testDeveInserirEBuscarOrdemServicoComItensNoBancoReal(): void
    {
        $cliente = $this->criarClienteFixture();
        $veiculo = $this->criarVeiculoFixture($cliente->getId());
        $servico = $this->criarServicoFixture();
        $peca    = $this->criarPecaFixture();

        $numeroOs = $this->osRepository->gerarProximoNumeroOs();
        $this->assertNotEmpty($numeroOs);
        $this->assertStringStartsWith('OS-', $numeroOs);

        $os = OrdemServico::criar(
            numeroOs:          $numeroOs,
            clienteId:         $cliente->getId(),
            veiculoId:         $veiculo->getId(),
            descricaoProblema: 'Ruído metálico ao acionar os freios dianteiros.',
            diagnostico:       'Pastilhas gastas e desalinhamento moderado.',
            status:            StatusOrdemServico::RECEBIDA,
        );

        // Adiciona 1 serviço e 2 peças
        $os->adicionarServico(ItemServicoOS::criar(
            servicoId:          $servico->getId(),
            nome:               $servico->getNome(),
            descricao:          $servico->getDescricao(),
            quantidade:         1,
            precoUnitario:      $servico->getPreco(),
            tempoEstimadoHoras: $servico->getTempoEstimadoHoras(),
        ));

        $os->adicionarPeca(ItemPecaOS::criar(
            pecaId:        $peca->getId(),
            descricao:     $peca->getNome(),
            quantidade:    2.0,
            precoUnitario: $peca->getValorUnitario(),
        ));

        $salva = $this->osRepository->salvar($os);

        $this->assertNotNull($salva->id());
        $this->assertSame($numeroOs, $salva->numeroOs());
        $this->assertSame(StatusOrdemServico::RECEBIDA, $salva->status());
        $this->assertSame(180.00, $salva->valorServicos());
        $this->assertSame(440.00, $salva->valorPecas());
        $this->assertSame(620.00, $salva->valorTotal());

        // Busca por ID
        $buscadaPorId = $this->osRepository->buscarPorId($salva->id());
        $this->assertNotNull($buscadaPorId);
        $this->assertSame($salva->id(), $buscadaPorId->id());
        $this->assertCount(1, $buscadaPorId->servicos());
        $this->assertCount(1, $buscadaPorId->pecas());

        // Busca por Número da OS
        $buscadaPorNumero = $this->osRepository->buscarPorNumero($numeroOs);
        $this->assertNotNull($buscadaPorNumero);
        $this->assertSame($salva->id(), $buscadaPorNumero->id());

        // Histórico inicial deve existir
        $historico = $this->osRepository->buscarHistoricoPorOsId($salva->id());
        $this->assertNotEmpty($historico);
        $this->assertSame(StatusOrdemServico::RECEBIDA->value, $historico[0]->statusPara());
    }

    public function testDeveListarOrdensServicoAplicandoFiltros(): void
    {
        $cliente = $this->criarClienteFixture();
        $veiculo = $this->criarVeiculoFixture($cliente->getId());

        $numeroOs = $this->osRepository->gerarProximoNumeroOs();
        $os = OrdemServico::criar(
            numeroOs:          $numeroOs,
            clienteId:         $cliente->getId(),
            veiculoId:         $veiculo->getId(),
            descricaoProblema: 'Revisão preventiva 30.000km',
            status:            StatusOrdemServico::RECEBIDA,
        );

        $salva = $this->osRepository->salvar($os);

        // Filtro por status
        $listaPorStatus = $this->osRepository->listar(['status' => 'recebida']);
        $this->assertNotEmpty($listaPorStatus);

        // Filtro por cliente_id
        $listaPorCliente = $this->osRepository->listar(['cliente_id' => $cliente->getId()]);
        $this->assertCount(1, $listaPorCliente);
        $this->assertSame($salva->id(), $listaPorCliente[0]->id());

        // Filtro por veiculo_id
        $listaPorVeiculo = $this->osRepository->listar(['veiculo_id' => $veiculo->getId()]);
        $this->assertCount(1, $listaPorVeiculo);
        $this->assertSame($salva->id(), $listaPorVeiculo[0]->id());

        // Filtro por placa
        $listaPorPlaca = $this->osRepository->listar(['placa' => 'XYZ9B88']);
        $this->assertCount(1, $listaPorPlaca);
        $this->assertSame($salva->id(), $listaPorPlaca[0]->id());
    }

    public function testDeveAtualizarStatusEEtapasDaOrdemServico(): void
    {
        $cliente = $this->criarClienteFixture();
        $veiculo = $this->criarVeiculoFixture($cliente->getId());

        $numeroOs = $this->osRepository->gerarProximoNumeroOs();
        $os = OrdemServico::criar(
            numeroOs:  $numeroOs,
            clienteId: $cliente->getId(),
            veiculoId: $veiculo->getId(),
            status:    StatusOrdemServico::RECEBIDA,
        );

        $salva = $this->osRepository->salvar($os);

        // Atualiza para EM_DIAGNOSTICO
        $salva->alterarStatus(StatusOrdemServico::EM_DIAGNOSTICO);

        $historico = new HistoricoStatusOS(
            id:         null,
            statusDe:   StatusOrdemServico::RECEBIDA->value,
            statusPara: StatusOrdemServico::EM_DIAGNOSTICO->value,
            observacao: 'Iniciada análise na rampa',
            criadoEm:   null,
        );

        $atualizada = $this->osRepository->atualizar($salva, $historico);

        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO, $atualizada->status());

        $historicoCompleto = $this->osRepository->buscarHistoricoPorOsId($salva->id());
        $this->assertCount(2, $historicoCompleto);
        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO->value, $historicoCompleto[1]->statusPara());
    }

    public function testDeveDeletarOrdemServico(): void
    {
        $cliente = $this->criarClienteFixture();
        $veiculo = $this->criarVeiculoFixture($cliente->getId());
        $servico = $this->criarServicoFixture();

        $numeroOs = $this->osRepository->gerarProximoNumeroOs();
        $os = OrdemServico::criar(
            numeroOs:  $numeroOs,
            clienteId: $cliente->getId(),
            veiculoId: $veiculo->getId(),
            status:    StatusOrdemServico::RECEBIDA,
        );

        $os->adicionarServico(ItemServicoOS::criar(
            servicoId:     $servico->getId(),
            nome:          $servico->getNome(),
            descricao:     null,
            quantidade:    1,
            precoUnitario: $servico->getPreco(),
        ));

        $salva = $this->osRepository->salvar($os);
        $osId  = $salva->id();

        $removido = $this->osRepository->deletar($osId);
        $this->assertTrue($removido);

        // Não deve mais encontrar a OS
        $this->assertNull($this->osRepository->buscarPorId($osId));
    }
    
    private function criarOsParaBaixa(float $quantidade = 2): array
    {
        $cliente = $this->criarClienteFixture();
        $veiculo = $this->criarVeiculoFixture($cliente->getId());
        $peca = $this->criarPecaFixture();
        $os = OrdemServico::criar($this->osRepository->gerarProximoNumeroOs(), $cliente->getId(), $veiculo->getId());
        $os->adicionarPeca(ItemPecaOS::criar($peca->getId(), $peca->getNome(), $quantidade, 220));
        $os->enviarOrcamentoParaAprovacao();
        return [$this->osRepository->salvar($os), $peca];
    }

    public function testAprovacaoBaixaEstoqueUmaUnicaVez(): void
    {
        [$os, $peca] = $this->criarOsParaBaixa();
        $useCase = new \Application\OrdemServico\AprovarOrcamentoUseCase($this->osRepository);
        $resultado = $useCase->executar($os->id());
        $this->assertSame(StatusOrdemServico::EM_EXECUCAO, $resultado->status());
        $this->assertSame(8, $this->pecaRepository->buscarPorId($peca->getId())->getQuantidadeEstoque());
        $this->osRepository->atualizar($resultado);
        $this->assertSame(8, $this->pecaRepository->buscarPorId($peca->getId())->getQuantidadeEstoque());
        $resultado->alterarStatus(StatusOrdemServico::EM_DIAGNOSTICO);
        $this->osRepository->atualizar($resultado);
        $resultado->enviarOrcamentoParaAprovacao();
        $this->osRepository->atualizar($resultado);
        $useCase->executar($os->id());
        $this->assertSame(8, $this->pecaRepository->buscarPorId($peca->getId())->getQuantidadeEstoque());
    }

    public function testAprovacaoSemSaldoMantemInicioPendenteSemBaixaParcial(): void
    {
        [$os, $peca] = $this->criarOsParaBaixa(11);
        $resultado = (new \Application\OrdemServico\AprovarOrcamentoUseCase($this->osRepository))->executar($os->id());
        $this->assertTrue($resultado->inicioPendentePorEstoque());
        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO, $resultado->status());
        $this->assertNotNull($resultado->orcamentoAprovadoEm());
        $this->assertNotEmpty($resultado->avisosEstoque());
        $this->assertSame(10, $this->pecaRepository->buscarPorId($peca->getId())->getQuantidadeEstoque());
        $historico = $resultado->historico();
        $this->assertSame('em_diagnostico', end($historico)->statusPara());
    }

    public function testSomaPecasRepetidasEAlertaEstoqueMinimoPelaRotaDeStatus(): void
    {
        [$os, $peca] = $this->criarOsParaBaixa(4);
        $os->adicionarPeca(ItemPecaOS::criar($peca->getId(), $peca->getNome(), 4, 220));
        $this->osRepository->atualizar($os);
        $resultado = (new \Application\OrdemServico\AlterarStatusOrdemServicoUseCase($this->osRepository))->executar($os->id(), 'em_execucao');
        $this->assertSame(2, $this->pecaRepository->buscarPorId($peca->getId())->getQuantidadeEstoque());
        $this->assertStringContainsString('repor', implode(' ', $resultado->avisosEstoque()));
    }
}
