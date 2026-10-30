<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculo;

use Application\Veiculo\DeletarVeiculoUseCase;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use PHPUnit\Framework\TestCase;
use Repositories\Veiculo\VeiculoRepositoryInterface;
use RuntimeException;

class DeletarVeiculoUseCaseTest extends TestCase
{
    public function testDeveDeletarVeiculoComSucesso(): void
    {
        $repositoryMock = $this->createMock(VeiculoRepositoryInterface::class);
        $veiculo = new Veiculo(1, 10, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2022, 'Prata');

        $repositoryMock->expects($this->once())->method('buscarPorId')->with(1)->willReturn($veiculo);
        $repositoryMock->expects($this->once())->method('deletar')->with(1)->willReturn(true);

        $useCase = new DeletarVeiculoUseCase($repositoryMock);
        $useCase->executar(1);
    }

    public function testDeveLancarExcecaoAoDeletarVeiculoInexistente(): void
    {
        $repositoryMock = $this->createMock(VeiculoRepositoryInterface::class);
        $repositoryMock->expects($this->once())->method('buscarPorId')->with(99)->willReturn(null);

        $useCase = new DeletarVeiculoUseCase($repositoryMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Veículo com ID 99 não encontrado.');

        $useCase->executar(99);
    }
}

