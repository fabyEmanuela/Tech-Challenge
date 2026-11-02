<?php

declare(strict_types=1);

namespace Tests\Unit\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\ItemServicoOS;
use PHPUnit\Framework\TestCase;

class ItemServicoOSTest extends TestCase
{
    public function testDeveCriarItemServicoValido(): void
    {
        $item = ItemServicoOS::criar(
            servicoId:          5,
            nome:               'Alinhamento e Balanceamento 3D',
            descricao:          'Serviço com calibragem a laser',
            quantidade:         1,
            precoUnitario:      120.00,
            tempoEstimadoHoras: 1.5
        );

        $this->assertNull($item->id());
        $this->assertSame(5, $item->servicoId());
        $this->assertSame('Alinhamento e Balanceamento 3D', $item->nome());
        $this->assertSame('Serviço com calibragem a laser', $item->descricao());
        $this->assertSame(1, $item->quantidade());
        $this->assertSame(120.00, $item->precoUnitario());
        $this->assertSame(1.5, $item->tempoEstimadoHoras());
        $this->assertSame(120.00, $item->subtotal());
        $this->assertNull($item->criadoEm());

        $item->definirId(42);
        $this->assertSame(42, $item->id());

        $array = $item->toArray();
        $this->assertSame(42, $array['id']);
        $this->assertSame(5, $array['servico_id']);
        $this->assertSame('Alinhamento e Balanceamento 3D', $array['nome']);
        $this->assertSame(120.00, $array['preco_unitario']);
        $this->assertSame(1.5, $array['tempo_estimado_horas']);
    }

    public function testDeveLancarExcecaoQuandoNomeForVazio(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('O nome do serviço é obrigatório.');

        ItemServicoOS::criar(null, '   ', null, 1, 100.0);
    }

    public function testDeveLancarExcecaoQuandoQuantidadeMenorQueUm(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('A quantidade de serviços deve ser no mínimo 1.');

        ItemServicoOS::criar(null, 'Serviço Teste', null, 0, 100.0);
    }

    public function testDeveLancarExcecaoQuandoPrecoUnitarioForNegativo(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('O preço unitário do serviço não pode ser negativo.');

        ItemServicoOS::criar(null, 'Serviço Teste', null, 1, -10.0);
    }

    public function testDeveLancarExcecaoQuandoTempoEstimadoForNegativo(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('O tempo estimado em horas não pode ser negativo.');

        ItemServicoOS::criar(null, 'Serviço Teste', null, 1, 100.0, -2.0);
    }
}

