<?php

namespace Tests\Unit\Peca;

use Application\Peca\BuscarPecaPorIdUseCase;
use Domain\Peca\Peca;
use PHPUnit\Framework\TestCase;
use Repositories\Peca\PecaRepositoryInterface;
use RuntimeException;

class BuscarPecaPorIdUseCaseTest extends TestCase
{
    public function testDeveRetornarPecaQuandoEncontrada(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $peca = new Peca(1, 'Vela de Ignição', null, 35.00, 12, 4, true, null, null);

        $repositoryMock->expects($this->once())->method('buscarPorId')->with(1)->willReturn($peca);

        $useCase = new BuscarPecaPorIdUseCase($repositoryMock);
        $resultado = $useCase->executar(1);

        $this->assertSame(1, $resultado->getId());
        $this->assertSame('Vela de Ignição', $resultado->getNome());
    }

    public function testDeveLancarExcecaoQuandoPecaNaoEncontrada(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $repositoryMock->expects($this->once())->method('buscarPorId')->with(99)->willReturn(null);

        $useCase = new BuscarPecaPorIdUseCase($repositoryMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Peça com ID 99 não encontrada.');

        $useCase->executar(99);
    }
}

