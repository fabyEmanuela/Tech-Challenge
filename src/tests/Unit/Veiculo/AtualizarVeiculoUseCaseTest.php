<?php

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
    public function testDeveAtualizarVeiculoComSucesso(): void
    {
        $veiculoRepoMock = $this->createMock(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $veiculoExistente  = new Veiculo(5, 1, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2021, 'Branco', null, null);
        $veiculoAtualizado = new Veiculo(5, 1, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.6 MSI', 2021, 'Preto', null, null);

        $veiculoRepoMock->expects($this->once())->method('buscarPorId')->with(5)->willReturn($veiculoExistente);
        $veiculoRepoMock->expects($this->once())->method('atualizar')->willReturn($veiculoAtualizado);

        $useCase   = new AtualizarVeiculoUseCase($veiculoRepoMock, $usuarioRepoStub);
        $resultado = $useCase->executar(5, [
            'modelo' => 'Gol 1.6 MSI',
            'cor'    => 'Preto',
        ]);

        $this->assertSame('Gol 1.6 MSI', $resultado->getModelo());
        $this->assertSame('Preto', $resultado->getCor());
        $this->assertSame('ABC1D23', $resultado->getPlaca()->getValor());
    }

    public function testDeveLancarExcecaoQuandoVeiculoNaoEncontrado(): void
    {
        $veiculoRepoMock = $this->createMock(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $veiculoRepoMock->expects($this->once())->method('buscarPorId')->with(999)->willReturn(null);

        $useCase = new AtualizarVeiculoUseCase($veiculoRepoMock, $usuarioRepoStub);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Veículo com ID 999 não encontrado.');

        $useCase->executar(999, ['cor' => 'Azul']);
    }

    public function testDeveLancarExcecaoQuandoNovaPlacaJaPertencerAOutroVeiculo(): void
    {
        $veiculoRepoMock = $this->createMock(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $veiculoExistente = new Veiculo(5, 1, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2021, 'Branco', null, null);

        $veiculoRepoMock->expects($this->once())->method('buscarPorId')->with(5)->willReturn($veiculoExistente);
        $veiculoRepoMock->expects($this->once())->method('placaJaCadastrada')->willReturn(true);

        $useCase = new AtualizarVeiculoUseCase($veiculoRepoMock, $usuarioRepoStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Já existe outro veículo cadastrado com a placa 'XYZ9K88'.");

        $useCase->executar(5, ['placa' => 'XYZ9K88']);
    }
}
