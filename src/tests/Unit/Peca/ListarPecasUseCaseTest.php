<?php

namespace Tests\Unit\Peca;

use Application\Peca\ListarPecasUseCase;
use Domain\Peca\Peca;
use PHPUnit\Framework\TestCase;
use Repositories\Peca\PecaRepositoryInterface;

class ListarPecasUseCaseTest extends TestCase
{
    public function testDeveListarTodasAsPecasSemFiltro(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $pecas = [
            new Peca(1, 'Filtro de Óleo', null, 45.90, 10, 2, true, null, null),
            new Peca(2, 'Pastilha de Freio', null, 120.00, 5, 2, false, null, null),
        ];

        $repositoryMock->expects($this->once())->method('listarTodos')->with(null)->willReturn($pecas);

        $useCase = new ListarPecasUseCase($repositoryMock);
        $resultado = $useCase->executar();

        $this->assertCount(2, $resultado);
    }

    public function testDeveListarPecasFiltrandoPorAtivo(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $pecas = [
            new Peca(1, 'Filtro de Óleo', null, 45.90, 10, 2, true, null, null),
        ];

        $repositoryMock->expects($this->once())->method('listarTodos')->with(true)->willReturn($pecas);

        $useCase = new ListarPecasUseCase($repositoryMock);
        $resultado = $useCase->executar('true');

        $this->assertCount(1, $resultado);
        $this->assertTrue($resultado[0]->isAtivo());
    }
}

