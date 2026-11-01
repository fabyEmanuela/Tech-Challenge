<?php

namespace Tests\Integration\Repositories;

use Domain\Peca\Peca;
use Repositories\Peca\PecaPdoRepository;
use Tests\Integration\IntegrationTestCase;

class PecaPdoRepositoryTest extends IntegrationTestCase
{
    private PecaPdoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PecaPdoRepository($this->pdo);
    }

    public function testDeveInserirEBuscarPecaNoBancoReal(): void
    {
        $peca = new Peca(
            id:                null,
            nome:              'Pastilha de Freio Dianteira',
            descricao:         'Pastilha cerâmica para Gol',
            valorUnitario:     149.90,
            quantidadeEstoque: 10,
            estoqueMinimo:     2,
            ativo:             true,
            criadoEm:          null,
            atualizadoEm:      null,
        );

        $salva = $this->repository->salvar($peca);

        $this->assertNotNull($salva->getId());
        $this->assertSame('Pastilha de Freio Dianteira', $salva->getNome());
        $this->assertSame(149.90, $salva->getValorUnitario());
        $this->assertSame(10, $salva->getQuantidadeEstoque());

        // Busca direto no MySQL real
        $buscada = $this->repository->buscarPorId($salva->getId());

        $this->assertNotNull($buscada);
        $this->assertSame($salva->getId(), $buscada->getId());
        $this->assertSame('Pastilha de Freio Dianteira', $buscada->getNome());
    }

    public function testDeveAtualizarPecaNoBancoReal(): void
    {
        $peca = new Peca(
            id:                null,
            nome:              'Óleo 5W30 Sintético',
            descricao:         'Lubrificante para motor',
            valorUnitario:     45.00,
            quantidadeEstoque: 20,
            estoqueMinimo:     5,
            ativo:             true,
            criadoEm:          null,
            atualizadoEm:      null,
        );

        $salva = $this->repository->salvar($peca);

        // Modifica os dados
        $salva->setNome('Óleo 5W30 Sintético Premium');
        $salva->setValorUnitario(52.50);
        $salva->setQuantidadeEstoque(15);

        $atualizada = $this->repository->atualizar($salva);

        $this->assertSame('Óleo 5W30 Sintético Premium', $atualizada->getNome());
        $this->assertSame(52.50, $atualizada->getValorUnitario());
        $this->assertSame(15, $atualizada->getQuantidadeEstoque());
    }

    public function testDeveDeletarPecaDoBancoReal(): void
    {
        $peca = new Peca(
            id:                null,
            nome:              'Filtro de Ar Condicionado',
            descricao:         'Filtro de cabine',
            valorUnitario:     35.00,
            quantidadeEstoque: 8,
            estoqueMinimo:     1,
            ativo:             true,
            criadoEm:          null,
            atualizadoEm:      null,
        );

        $salva = $this->repository->salvar($peca);
        $id = $salva->getId();

        $deletado = $this->repository->deletar($id);

        $this->assertTrue($deletado);
        $this->assertNull($this->repository->buscarPorId($id));
    }
}

