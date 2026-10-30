<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculo;

use Application\Veiculo\ListarVeiculosUseCase;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use PHPUnit\Framework\TestCase;
use Repositories\Veiculo\VeiculoRepositoryInterface;

class ListarVeiculosUseCaseTest extends TestCase
{
    public function testDeveListarTodosOsVeiculosSemFiltro(): void
    {
        $repositoryMock = $this->createMock(VeiculoRepositoryInterface::class);
        $veiculos = [
            new Veiculo(1, 10, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2022, 'Prata'),
            new Veiculo(2, 15, new Placa('XYZ-9876'), 'Chevrolet', 'Onix 1.0', 2020, 'Branco'),
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('listarTodos')
            ->willReturn($veiculos);

        $useCase   = new ListarVeiculosUseCase($repositoryMock);
        $resultado = $useCase->executar();

        $this->assertCount(2, $resultado);
    }

    public function testDeveListarVeiculosFiltrandoPorClienteId(): void
    {
        $repositoryMock = $this->createMock(VeiculoRepositoryInterface::class);
        $veiculos = [
            new Veiculo(1, 10, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2022, 'Prata'),
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('listarPorClienteId')
            ->with(10)
            ->willReturn($veiculos);

        $useCase   = new ListarVeiculosUseCase($repositoryMock);
        $resultado = $useCase->executar(10);

        $this->assertCount(1, $resultado);
        $this->assertSame(10, $resultado[0]->getClienteId());
    }
}

