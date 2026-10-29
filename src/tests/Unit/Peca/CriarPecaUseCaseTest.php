<?php

namespace Tests\Unit\Peca;

use Application\Peca\CriarPecaUseCase;
use Domain\Peca\Peca;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Repositories\Peca\PecaRepositoryInterface;

class CriarPecaUseCaseTest extends TestCase
{
    public function testDeveCriarPecaComDadosValidos(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);

        $pecaSalva = new Peca(
            id: 1,
            nome: 'Filtro de Óleo',
            descricao: 'Filtro para motor 1.0',
            valorUnitario: 45.90,
            quantidadeEstoque: 10,
            estoqueMinimo: 2,
            ativo: true,
            criadoEm: '2026-10-28 10:00:00',
            atualizadoEm: '2026-10-28 10:00:00'
        );

        $repositoryMock
            ->expects($this->once())
            ->method('salvar')
            ->willReturn($pecaSalva);

        $useCase = new CriarPecaUseCase($repositoryMock);
        $resultado = $useCase->executar([
            'nome'               => 'Filtro de Óleo',
            'descricao'          => 'Filtro para motor 1.0',
            'valor_unitario'     => 45.90,
            'quantidade_estoque' => 10,
            'estoque_minimo'     => 2,
            'ativo'              => true,
        ]);

        $this->assertSame(1, $resultado->getId());
        $this->assertSame('Filtro de Óleo', $resultado->getNome());
        $this->assertSame(45.90, $resultado->getValorUnitario());
    }

    public function testDeveCriarPecaComValoresOpcionaisPadrao(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $pecaSalva = new Peca(1, 'Pastilha de Freio', null, 120.00, 0, 0, true, null, null);

        $repositoryMock->expects($this->once())->method('salvar')->willReturn($pecaSalva);

        $useCase = new CriarPecaUseCase($repositoryMock);
        $resultado = $useCase->executar([
            'nome'           => 'Pastilha de Freio',
            'valor_unitario' => 120.00,
        ]);

        $this->assertSame(0, $resultado->getQuantidadeEstoque());
        $this->assertNull($resultado->getDescricao());
    }

    public function testDeveLancarExcecaoQuandoNomeForVazio(): void
    {
        $repositoryStub = $this->createStub(PecaRepositoryInterface::class);
        $useCase = new CriarPecaUseCase($repositoryStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O campo "nome" é obrigatório.');

        $useCase->executar([
            'nome'           => '',
            'valor_unitario' => 45.90,
        ]);
    }

    public function testDeveLancarExcecaoQuandoValorUnitarioNaoForInformado(): void
    {
        $repositoryStub = $this->createStub(PecaRepositoryInterface::class);
        $useCase = new CriarPecaUseCase($repositoryStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O campo "valor_unitario" é obrigatório.');

        $useCase->executar([
            'nome' => 'Filtro de Óleo',
        ]);
    }

    public function testDeveLancarExcecaoQuandoValorUnitarioForNegativo(): void
    {
        $repositoryStub = $this->createStub(PecaRepositoryInterface::class);
        $useCase = new CriarPecaUseCase($repositoryStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário inválido: o valor não pode ser negativo.');

        $useCase->executar([
            'nome'           => 'Filtro de Óleo',
            'valor_unitario' => -10.00,
        ]);
    }

    public function testDeveLancarExcecaoQuandoDescricaoUltrapassar255Caracteres(): void
    {
        $repositoryStub = $this->createStub(PecaRepositoryInterface::class);
        $useCase = new CriarPecaUseCase($repositoryStub);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O campo "descricao" deve ter no máximo 255 caracteres.');

        $useCase->executar([
            'nome'           => 'Filtro de Óleo',
            'descricao'      => str_repeat('A', 256),
            'valor_unitario' => 45.90,
        ]);
    }
}
