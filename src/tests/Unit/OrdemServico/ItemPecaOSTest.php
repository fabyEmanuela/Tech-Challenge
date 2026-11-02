<?php

declare(strict_types=1);

namespace Tests\Unit\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\ItemPecaOS;
use PHPUnit\Framework\TestCase;

class ItemPecaOSTest extends TestCase
{
    public function testDeveCriarItemPecaValido(): void
    {
        $item = ItemPecaOS::criar(
            pecaId:        10,
            descricao:     'Filtro de Óleo Fram',
            quantidade:    2.0,
            precoUnitario: 45.50
        );

        $this->assertNull($item->id());
        $this->assertSame(10, $item->pecaId());
        $this->assertSame('Filtro de Óleo Fram', $item->descricao());
        $this->assertSame(2.0, $item->quantidade());
        $this->assertSame(45.50, $item->precoUnitario());
        $this->assertSame(91.00, $item->subtotal());
        $this->assertNull($item->criadoEm());

        $item->definirId(99);
        $this->assertSame(99, $item->id());

        $array = $item->toArray();
        $this->assertSame(99, $array['id']);
        $this->assertSame(10, $array['peca_id']);
        $this->assertSame('Filtro de Óleo Fram', $array['descricao']);
        $this->assertSame(2.0, $array['quantidade']);
        $this->assertSame(45.50, $array['preco_unitario']);
        $this->assertSame(91.00, $array['subtotal']);
    }

    public function testDeveLancarExcecaoQuandoDescricaoForVazia(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('A descrição da peça/insumo é obrigatória.');

        ItemPecaOS::criar(1, '   ', 1.0, 10.0);
    }

    public function testDeveLancarExcecaoQuandoQuantidadeMenorOuIgualAZero(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('A quantidade da peça/insumo deve ser maior que zero.');

        ItemPecaOS::criar(1, 'Peça Teste', 0.0, 10.0);
    }

    public function testDeveLancarExcecaoQuandoPrecoUnitarioForNegativo(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('O preço unitário da peça/insumo não pode ser negativo.');

        ItemPecaOS::criar(1, 'Peça Teste', 1.0, -5.0);
    }
}

