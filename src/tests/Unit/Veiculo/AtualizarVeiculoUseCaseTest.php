<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculo;

use Application\Veiculo\AtualizarVeiculoUseCase;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Repositories\Usuario\UsuarioRepositoryInterface;
use Repositories\Veiculo\VeiculoRepositoryInterface;
use RuntimeException;

class AtualizarVeiculoUseCaseTest extends TestCase
{
    public function testDeveAtualizarParcialmenteVeiculoExistente(): void
    {
        $veiculoRepoMock = $this->createMock(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $veiculoExistente = new Veiculo(
            id: 5,
            clienteId: 1,
            placa: new Placa('ABC1D23'),
            marca: 'Volkswagen',
            modelo: 'Gol 1.0',
            ano: 2021,
            cor: 'Branco',
        );

        $veiculoRepoMock->method('buscarPorId')->with(5)->willReturn($veiculoExistente);
        $veiculoRepoMock
            ->expects($this->once())
            ->method('atualizar')
            ->willReturnCallback(fn(Veiculo $v) => $v);

        $useCase   = new AtualizarVeiculoUseCase($veiculoRepoMock, $usuarioRepoStub);
        $resultado = $useCase->executar(5, [
            'modelo' => 'Gol 1.6 MSI',
            'cor'    => 'Preto',
        ]);

        $this->assertSame('Gol 1.6 MSI', $resultado->getModelo());
        $this->assertSame('Preto', $resultado->getCor());
        $this->assertSame('ABC1D23', $resultado->getPlaca()->getValor());
    }

    public function testDeveLancarRuntimeExceptionQuandoVeiculoNaoExistir(): void
    {
        $veiculoRepoStub = $this->createStub(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);
        $veiculoRepoStub->method('buscarPorId')->willReturn(null);

        $useCase = new AtualizarVeiculoUseCase($veiculoRepoStub, $usuarioRepoStub);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Veículo com ID 404 não encontrado.');

        $useCase->executar(404, ['cor' => 'Azul']);
    }

    public function testDeveLancarExcecaoQuandoNovaPlacaJaPertencerAOutroVeiculo(): void
    {
        $veiculoRepoStub = $this->createStub(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $veiculoExistente = new Veiculo(5, 1, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2021, 'Branco');
        $veiculoRepoStub->method('buscarPorId')->willReturn($veiculoExistente);
        $veiculoRepoStub->method('placaJaCadastrada')->willReturn(true);

        $useCase = new AtualizarVeiculoUseCase($veiculoRepoStub, $usuarioRepoStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Já existe outro veículo cadastrado com a placa 'XYZ9K88'.");

        $useCase->executar(5, ['placa' => 'XYZ9K88']);
    }
}

