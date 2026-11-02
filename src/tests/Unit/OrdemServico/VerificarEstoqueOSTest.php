<?php

declare(strict_types=1);

namespace Tests\Unit\OrdemServico;

use Application\OrdemServico\VerificarEstoqueOS;
use Domain\OrdemServico\ItemPecaOS;
use Domain\OrdemServico\OrdemServico;
use Domain\Peca\Peca;
use PHPUnit\Framework\TestCase;
use Repositories\Peca\PecaRepositoryInterface;

class VerificarEstoqueOSTest extends TestCase
{
    private function verificar(int $saldo, int $minimo, array $quantidades): array
    {
        $os = OrdemServico::criar('OS-TESTE', 1, 1);
        foreach ($quantidades as $quantidade) {
            $os->adicionarPeca(ItemPecaOS::criar(1, 'Filtro', $quantidade, 10));
        }
        $repo = $this->createMock(PecaRepositoryInterface::class);
        $repo->expects($this->once())->method('buscarPorId')->with(1)
            ->willReturn(new Peca(1, 'Filtro', null, 10, $saldo, $minimo, true, null, null));
        return (new VerificarEstoqueOS($repo))->executar($os);
    }

    public function testAvisaEstoqueZerado(): void
    {
        $resultado = $this->verificar(0, 2, [1]);
        $this->assertStringContainsString('zerado', $resultado['faltas'][0]);
    }

    public function testAlertaReposicaoAoAtingirMinimoSemBloquear(): void
    {
        $resultado = $this->verificar(3, 2, [1]);
        $this->assertSame([], $resultado['faltas']);
        $this->assertStringContainsString('repor', implode(' ', $resultado['avisos']));
    }

    public function testSomaItensRepetidosParaDetectarSaldoInsuficiente(): void
    {
        $resultado = $this->verificar(3, 0, [2, 2]);
        $this->assertStringContainsString('solicitado: 4', $resultado['faltas'][0]);
    }
}
