<?php

declare(strict_types=1);

namespace Tests\Unit\OrdemServico;

use Domain\OrdemServico\HistoricoStatusOS;
use PHPUnit\Framework\TestCase;

class HistoricoStatusOSTest extends TestCase
{
    public function testDeveCriarEConverterParaArray(): void
    {
        $historico = new HistoricoStatusOS(
            id:         1,
            statusDe:   'recebida',
            statusPara: 'em_diagnostico',
            observacao: 'Iniciado diagnóstico mecânico.',
            criadoEm:   '2026-11-01 10:00:00'
        );

        $this->assertSame(1, $historico->id());
        $this->assertSame('recebida', $historico->statusDe());
        $this->assertSame('em_diagnostico', $historico->statusPara());
        $this->assertSame('Iniciado diagnóstico mecânico.', $historico->observacao());
        $this->assertSame('2026-11-01 10:00:00', $historico->criadoEm());

        $array = $historico->toArray();
        $this->assertSame(1, $array['id']);
        $this->assertSame('recebida', $array['status_de']);
        $this->assertSame('em_diagnostico', $array['status_para']);
        $this->assertSame('Iniciado diagnóstico mecânico.', $array['observacao']);
        $this->assertSame('2026-11-01 10:00:00', $array['criado_em']);
    }
}

