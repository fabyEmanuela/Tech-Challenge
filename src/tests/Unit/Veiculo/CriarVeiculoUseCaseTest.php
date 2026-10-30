<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculo;

use Application\Veiculo\CriarVeiculoUseCase;
use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Repositories\Usuario\UsuarioRepositoryInterface;
use Repositories\Veiculo\VeiculoRepositoryInterface;

class CriarVeiculoUseCaseTest extends TestCase
{
    public function testDeveCriarVeiculoComDadosValidos(): void
    {
        $veiculoRepoMock = $this->createMock(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $cliente = new Usuario(
            id: 1,
            nome: 'João Silva',
            email: 'joao@email.com',
            telefone: '11999998888',
            cpf: '529.982.247-25',
            tipoUsuario: TipoUsuario::CLIENTE,
            senhaHash: null,
            ativo: true,
            criadoEm: null,
            atualizadoEm: null,
        );

        $usuarioRepoStub->method('buscarPorId')->willReturn($cliente);
        $veiculoRepoMock->method('placaJaCadastrada')->willReturn(false);

        $veiculoSalvo = new Veiculo(
            id: 10,
            clienteId: 1,
            placa: new Placa('ABC1D23'),
            marca: 'Volkswagen',
            modelo: 'Gol 1.0',
            ano: 2022,
            cor: 'Prata',
            criadoEm: '2026-10-29 20:00:00',
            atualizadoEm: '2026-10-29 20:00:00',
        );

        $veiculoRepoMock
            ->expects($this->once())
            ->method('salvar')
            ->willReturn($veiculoSalvo);

        $useCase   = new CriarVeiculoUseCase($veiculoRepoMock, $usuarioRepoStub);
        $resultado = $useCase->executar([
            'cliente_id' => 1,
            'placa'      => 'abc1d23',
            'marca'      => 'Volkswagen',
            'modelo'     => 'Gol 1.0',
            'ano'        => 2022,
            'cor'        => 'Prata',
        ]);

        $this->assertSame(10, $resultado->getId());
        $this->assertSame('ABC1D23', $resultado->getPlaca()->getValor());
        $this->assertSame('Volkswagen', $resultado->getMarca());
    }

    public function testDeveLancarExcecaoQuandoClienteNaoExistir(): void
    {
        $veiculoRepoStub = $this->createStub(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);
        $usuarioRepoStub->method('buscarPorId')->willReturn(null);

        $useCase = new CriarVeiculoUseCase($veiculoRepoStub, $usuarioRepoStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cliente com ID 99 não encontrado.');

        $useCase->executar([
            'cliente_id' => 99,
            'placa'      => 'ABC1D23',
            'marca'      => 'Volkswagen',
            'modelo'     => 'Gol 1.0',
            'ano'        => 2022,
        ]);
    }

    public function testDeveLancarExcecaoQuandoPlacaJaEstiverCadastrada(): void
    {
        $veiculoRepoStub = $this->createStub(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $cliente = new Usuario(1, 'João Silva', 'joao@email.com', null, '529.982.247-25', TipoUsuario::CLIENTE, null, true, null, null);
        $usuarioRepoStub->method('buscarPorId')->willReturn($cliente);
        $veiculoRepoStub->method('placaJaCadastrada')->willReturn(true);

        $useCase = new CriarVeiculoUseCase($veiculoRepoStub, $usuarioRepoStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Já existe um veículo cadastrado com a placa 'ABC-1234'.");

        $useCase->executar([
            'cliente_id' => 1,
            'placa'      => 'ABC-1234',
            'marca'      => 'Volkswagen',
            'modelo'     => 'Gol 1.0',
            'ano'        => 2022,
        ]);
    }

    public function testDeveLancarExcecaoQuandoMarcaContiverCaracteresProibidos(): void
    {
        $veiculoRepoStub = $this->createStub(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $cliente = new Usuario(1, 'João Silva', 'joao@email.com', null, '529.982.247-25', TipoUsuario::CLIENTE, null, true, null, null);
        $usuarioRepoStub->method('buscarPorId')->willReturn($cliente);
        $veiculoRepoStub->method('placaJaCadastrada')->willReturn(false);

        $useCase = new CriarVeiculoUseCase($veiculoRepoStub, $usuarioRepoStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Marca contém caracteres não permitidos');

        $useCase->executar([
            'cliente_id' => 1,
            'placa'      => 'ABC1D23',
            'marca'      => 'VW<script>',
            'modelo'     => 'Gol 1.0',
            'ano'        => 2022,
        ]);
    }

    public function testDeveLancarExcecaoQuandoAnoForInvalido(): void
    {
        $veiculoRepoStub = $this->createStub(VeiculoRepositoryInterface::class);
        $usuarioRepoStub = $this->createStub(UsuarioRepositoryInterface::class);

        $cliente = new Usuario(1, 'João Silva', 'joao@email.com', null, '529.982.247-25', TipoUsuario::CLIENTE, null, true, null, null);
        $usuarioRepoStub->method('buscarPorId')->willReturn($cliente);
        $veiculoRepoStub->method('placaJaCadastrada')->willReturn(false);

        $useCase = new CriarVeiculoUseCase($veiculoRepoStub, $usuarioRepoStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ano inválido: deve estar entre 1900');

        $useCase->executar([
            'cliente_id' => 1,
            'placa'      => 'ABC1D23',
            'marca'      => 'Volkswagen',
            'modelo'     => 'Gol 1.0',
            'ano'        => 1850,
        ]);
    }
}

