<?php

namespace Tests\Unit\Peca;

use Domain\Peca\Peca;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PecaTest extends TestCase
{
    public function testDeveInstanciarPecaEConverterParaArray(): void
    {
        $peca = new Peca(
            id: 1,
            nome: 'Filtro de Óleo',
            descricao: 'Motor 1.0 e 1.6',
            valorUnitario: 45.90,
            quantidadeEstoque: 10,
            estoqueMinimo: 2,
            ativo: true,
            criadoEm: '2026-10-28 10:00:00',
            atualizadoEm: '2026-10-28 10:00:00'
        );

        $this->assertSame(1, $peca->getId());
        $this->assertSame('Filtro de Óleo', $peca->getNome());
        $this->assertSame('Motor 1.0 e 1.6', $peca->getDescricao());
        $this->assertSame(45.90, $peca->getValorUnitario());
        $this->assertSame(10, $peca->getQuantidadeEstoque());
        $this->assertSame(2, $peca->getEstoqueMinimo());
        $this->assertTrue($peca->isAtivo());
        $this->assertFalse($peca->estaAbaixoDoEstoqueMinimo());
        $this->assertSame('2026-10-28 10:00:00', $peca->getCriadoEm());
        $this->assertSame('2026-10-28 10:00:00', $peca->getAtualizadoEm());

        $array = $peca->paraArray();
        $this->assertSame(1, $array['id']);
        $this->assertSame('Filtro de Óleo', $array['nome']);
        $this->assertFalse($array['abaixo_minimo']);
    }

    public function testDeveAlterarPropriedadesComSetters(): void
    {
        $peca = new Peca(1, 'Filtro', null, 30.00, 5, 1, true, null, null);

        $peca->setNome('Filtro de Ar');
        $peca->setDescricao('Filtro esportivo');
        $peca->setValorUnitario(55.50);
        $peca->setQuantidadeEstoque(15);
        $peca->setEstoqueMinimo(3);
        $peca->setAtivo(false);

        $this->assertSame('Filtro de Ar', $peca->getNome());
        $this->assertSame('Filtro esportivo', $peca->getDescricao());
        $this->assertSame(55.50, $peca->getValorUnitario());
        $this->assertSame(15, $peca->getQuantidadeEstoque());
        $this->assertSame(3, $peca->getEstoqueMinimo());
        $this->assertFalse($peca->isAtivo());
    }

    public function testDeveIdentificarQuandoEstoqueEstaAbaixoDoMinimo(): void
    {
        $peca = new Peca(1, 'Filtro de Óleo', 'Motor 1.0', 45.90, 3, 5, true, null, null);

        $this->assertTrue($peca->estaAbaixoDoEstoqueMinimo());
    }

    public function testDeveAdicionarEstoqueComSucesso(): void
    {
        $peca = new Peca(1, 'Filtro de Óleo', null, 45.90, 10, 2, true, null, null);
        $peca->alterarEstoque(5, adicionar: true);

        $this->assertSame(15, $peca->getQuantidadeEstoque());
    }

    public function testDeveBaixarEstoqueComSucesso(): void
    {
        $peca = new Peca(1, 'Filtro de Óleo', null, 45.90, 10, 2, true, null, null);
        $peca->alterarEstoque(4, adicionar: false);

        $this->assertSame(6, $peca->getQuantidadeEstoque());
    }

    public function testDeveLancarExcecaoQuandoQuantidadeMovimentacaoForZeroOuNegativa(): void
    {
        $peca = new Peca(1, 'Filtro de Óleo', null, 45.90, 10, 2, true, null, null);

        $this->expectException(InvalidArgumentException::class);
        $peca->alterarEstoque(0, adicionar: true);
    }

    public function testDeveLancarExcecaoQuandoEstoqueInsuficiente(): void
    {
        $peca = new Peca(1, 'Filtro de Óleo', null, 45.90, 2, 1, true, null, null);

        $this->expectException(InvalidArgumentException::class);
        $peca->alterarEstoque(5, adicionar: false);
    }
}
