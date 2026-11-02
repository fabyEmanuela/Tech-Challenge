<?php

declare(strict_types=1);

namespace Tests\Unit\Servico;

use Domain\Servico\Servico;
use PHPUnit\Framework\TestCase;

class ServicoTest extends TestCase
{
    public function testDeveInstanciarServicoCorretamente(): void
    {
        $servico = new Servico(
            id:                 1,
            nome:               'Troca de Óleo',
            descricao:          'Troca de óleo do motor e anel de vedação',
            preco:              90.00,
            tempoEstimadoHoras: 0.5,
            ativo:              true,
            criadoEm:           '2026-11-01 10:00:00',
            atualizadoEm:       '2026-11-01 10:00:00',
        );

        $this->assertSame(1, $servico->getId());
        $this->assertSame('Troca de Óleo', $servico->getNome());
        $this->assertSame('Troca de óleo do motor e anel de vedação', $servico->getDescricao());
        $this->assertSame(90.00, $servico->getPreco());
        $this->assertSame(0.5, $servico->getTempoEstimadoHoras());
        $this->assertTrue($servico->isAtivo());
        $this->assertSame('2026-11-01 10:00:00', $servico->getCriadoEm());
        $this->assertSame('2026-11-01 10:00:00', $servico->getAtualizadoEm());
    }

    public function testDeveAlterarDadosViaSetters(): void
    {
        $servico = new Servico(
            id:                 null,
            nome:               'Alinhamento',
            descricao:          null,
            preco:              100.00,
            tempoEstimadoHoras: 1.0,
            ativo:              true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        $servico->setNome('Alinhamento 3D e Balanceamento');
        $servico->setDescricao('Alinhamento a laser nas 4 rodas');
        $servico->setPreco(180.00);
        $servico->setTempoEstimadoHoras(1.5);
        $servico->setAtivo(false);

        $this->assertSame('Alinhamento 3D e Balanceamento', $servico->getNome());
        $this->assertSame('Alinhamento a laser nas 4 rodas', $servico->getDescricao());
        $this->assertSame(180.00, $servico->getPreco());
        $this->assertSame(1.5, $servico->getTempoEstimadoHoras());
        $this->assertFalse($servico->isAtivo());
    }

    public function testDeveConverterParaArray(): void
    {
        $servico = new Servico(
            id:                 3,
            nome:               'Revisão Geral',
            descricao:          'Checkup completo de freios e suspensão',
            preco:              250.00,
            tempoEstimadoHoras: 2.0,
            ativo:              true,
            criadoEm:           '2026-11-01 14:00:00',
            atualizadoEm:       null,
        );

        $array = $servico->paraArray();

        $this->assertSame(3, $array['id']);
        $this->assertSame('Revisão Geral', $array['nome']);
        $this->assertSame('Checkup completo de freios e suspensão', $array['descricao']);
        $this->assertSame(250.00, $array['preco']);
        $this->assertSame(2.0, $array['tempo_estimado_horas']);
        $this->assertTrue($array['ativo']);
        $this->assertSame('2026-11-01 14:00:00', $array['criado_em']);
    }
}

