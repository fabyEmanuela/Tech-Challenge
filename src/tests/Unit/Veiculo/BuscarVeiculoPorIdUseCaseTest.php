<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculo;

use Application\Veiculo\BuscarVeiculoPorIdUseCase;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use PHPUnit\Framework\TestCase;
use Repositories\Veiculo\VeiculoRepositoryInterface;
use RuntimeException;

class BuscarVeiculoPorIdUseCaseTest extends TestCase
{
    public function testDeveRetornarVeiculoQuandoEncontrado(): void
    {
        $repositoryMock = $this->createMock(VeiculoRepositoryInterface::class);
        $veiculo = new Veiculo(1, 10, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2022, 'Prata');

        $repositoryMock
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(1)
            ->willReturn($veiculo);

        $useCase   = new BuscarVeiculoPorIdUseCase($repositoryMock);
        $resultado = $useCase->executar(1);

        $this->assertSame(1, $resultado->getId());
        $this->assertSame('ABC1D23', $resultado->getPlaca()->getValor());
        $this->assertSame('Volkswagen', $resultado->getMarca());
    }

    public function testDeveLancarExcecaoQuandoVeiculoNaoEncontrado(): void
    {
        $repositoryMock = $this->createMock(VeiculoRepositoryInterface::class);

        $repositoryMock
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(99)
            ->willReturn(null);

        $useCase = new BuscarVeiculoPorIdUseCase($repositoryMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Veículo com ID 99 não encontrado.');

        $useCase->executar(99);
    }
}

