<?php

namespace Tests\Unit\Peca;

use Application\Peca\DeletarPecaUseCase;
use Domain\Peca\Peca;
use PHPUnit\Framework\TestCase;
use Repositories\Peca\PecaRepositoryInterface;
use RuntimeException;

class DeletarPecaUseCaseTest extends TestCase
{
    public function testDeveDeletarPecaComSucesso(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $peca = new Peca(1, 'Filtro de Óleo', null, 45.90, 10, 2, true, null, null);

        $repositoryMock->expects($this->once())->method('buscarPorId')->with(1)->willReturn($peca);
        $repositoryMock->expects($this->once())->method('deletar')->with(1)->willReturn(true);

        $useCase = new DeletarPecaUseCase($repositoryMock);
        $useCase->executar(1);
    }

    public function testDeveLancarExcecaoAoDeletarPecaInexistente(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $repositoryMock->expects($this->once())->method('buscarPorId')->with(99)->willReturn(null);

        $useCase = new DeletarPecaUseCase($repositoryMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Peça com ID 99 não encontrada.');

        $useCase->executar(99);
    }
}

