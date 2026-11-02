<?php

namespace Tests\Integration\Repositories;

use Domain\Servico\Servico;
use Repositories\Servico\ServicoPdoRepository;
use Tests\Integration\IntegrationTestCase;

class ServicoPdoRepositoryTest extends IntegrationTestCase
{
    private ServicoPdoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ServicoPdoRepository($this->pdo);
    }

    public function testDeveInserirEBuscarServicoNoBancoReal(): void
    {
        $servico = new Servico(
            id:                 null,
            nome:               'Alinhamento e Balanceamento 3D',
            descricao:          'Alinhamento computadorizado com balanceamento das 4 rodas',
            preco:              180.00,
            tempoEstimadoHoras: 1.5,
            ativo:              true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        $salvo = $this->repository->salvar($servico);

        $this->assertNotNull($salvo->getId());
        $this->assertSame('Alinhamento e Balanceamento 3D', $salvo->getNome());
        $this->assertSame(180.00, $salvo->getPreco());
        $this->assertSame(1.5, $salvo->getTempoEstimadoHoras());
        $this->assertTrue($salvo->isAtivo());

        // Busca direto no MySQL real
        $buscado = $this->repository->buscarPorId($salvo->getId());

        $this->assertNotNull($buscado);
        $this->assertSame($salvo->getId(), $buscado->getId());
        $this->assertSame('Alinhamento e Balanceamento 3D', $buscado->getNome());
    }

    public function testDeveBuscarServicosPorNomeParcialNoBancoReal(): void
    {
        $servico1 = new Servico(
            id:                 null,
            nome:               'Troca de Óleo Motor',
            descricao:          'Substituição do óleo e filtro',
            preco:              90.00,
            tempoEstimadoHoras: 0.5,
            ativo:              true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        $servico2 = new Servico(
            id:                 null,
            nome:               'Troca de Óleo Câmbio',
            descricao:          'Substituição do fluido de transmissão',
            preco:              250.00,
            tempoEstimadoHoras: 2.0,
            ativo:              true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        $this->repository->salvar($servico1);
        $this->repository->salvar($servico2);

        $encontrados = $this->repository->buscarPorNome('Óleo');

        $this->assertGreaterThanOrEqual(2, count($encontrados));
        foreach ($encontrados as $item) {
            $this->assertStringContainsStringIgnoringCase('óleo', $item->getNome());
        }
    }

    public function testDeveAtualizarServicoNoBancoReal(): void
    {
        $servico = new Servico(
            id:                 null,
            nome:               'Higienização de Ar Condicionado',
            descricao:          'Limpeza com ozônio',
            preco:              120.00,
            tempoEstimadoHoras: 1.0,
            ativo:              true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        $salvo = $this->repository->salvar($servico);

        // Modifica os dados
        $salvo->setNome('Higienização de Ar Condicionado Completa');
        $salvo->setPreco(150.00);
        $salvo->setTempoEstimadoHoras(1.2);

        $atualizado = $this->repository->atualizar($salvo);

        $this->assertSame('Higienização de Ar Condicionado Completa', $atualizado->getNome());
        $this->assertSame(150.00, $atualizado->getPreco());
        $this->assertSame(1.2, $atualizado->getTempoEstimadoHoras());

        // Confirma buscando de novo no banco
        $buscado = $this->repository->buscarPorId($salvo->getId());
        $this->assertSame('Higienização de Ar Condicionado Completa', $buscado->getNome());
        $this->assertSame(150.00, $buscado->getPreco());
    }

    public function testDeveDeletarServicoDoBancoReal(): void
    {
        $servico = new Servico(
            id:                 null,
            nome:               'Polimento de Farol',
            descricao:          'Restauração de lentes opacas',
            preco:              80.00,
            tempoEstimadoHoras: 0.8,
            ativo:              true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        $salvo = $this->repository->salvar($servico);
        $id = $salvo->getId();

        $deletado = $this->repository->deletar($id);

        $this->assertTrue($deletado);
        $this->assertNull($this->repository->buscarPorId($id));
    }
}

