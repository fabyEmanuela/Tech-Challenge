<?php

namespace Tests\Unit\Peca;

use Application\Peca\AtualizarPecaUseCase;
use Domain\Peca\Peca;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Repositories\Peca\PecaRepositoryInterface;
use RuntimeException;

class AtualizarPecaUseCaseTest extends TestCase
{
    public function testDeveAtualizarPecaComSucesso(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);

        $pecaExistente = new Peca(1, 'Filtro Antigo', 'Desc', 30.00, 5, 2, true, null, null);
        $pecaAtualizada = new Peca(1, 'Filtro Novo', null, 50.00, 20, 4, false, null, null);

        $repositoryMock->expects($this->once())->method('buscarPorId')->with(1)->willReturn($pecaExistente);
        $repositoryMock->expects($this->once())->method('atualizar')->willReturn($pecaAtualizada);

        $useCase = new AtualizarPecaUseCase($repositoryMock);
        $resultado = $useCase->executar(1, [
            'nome'               => 'Filtro Novo',
            'descricao'          => '',
            'valor_unitario'     => 50.00,
            'quantidade_estoque' => 20,
            'estoque_minimo'     => 4,
            'ativo'              => false,
        ]);

        $this->assertSame('Filtro Novo', $resultado->getNome());
        $this->assertSame(50.00, $resultado->getValorUnitario());
        $this->assertSame(20, $resultado->getQuantidadeEstoque());
        $this->assertFalse($resultado->isAtivo());
    }

    public function testDeveLancarExcecaoQuandoPecaNaoEncontrada(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $repositoryMock->expects($this->once())->method('buscarPorId')->with(999)->willReturn(null);

        $useCase = new AtualizarPecaUseCase($repositoryMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Peça com ID 999 não encontrada.');

        $useCase->executar(999, ['nome' => 'Filtro Novo']);
    }

    public function testDeveLancarExcecaoQuandoEstoqueNegativoNaAtualizacao(): void
    {
        $repositoryMock = $this->createMock(PecaRepositoryInterface::class);
        $pecaExistente = new Peca(1, 'Filtro Antigo', null, 30.00, 5, 2, true, null, null);

        $repositoryMock->expects($this->once())->method('buscarPorId')->with(1)->willReturn($pecaExistente);

        $useCase = new AtualizarPecaUseCase($repositoryMock);

        $this->expectException(InvalidArgumentException::class);
        $useCase->executar(1, ['quantidade_estoque' => -3]);
    }
}

