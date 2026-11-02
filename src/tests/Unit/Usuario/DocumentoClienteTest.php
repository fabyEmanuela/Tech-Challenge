<?php
namespace Tests\Unit\Usuario;

use Domain\Shared\Validator;
use Domain\Usuario\{Usuario, TipoUsuario};
use Application\Usuario\{CriarUsuarioUseCase, AtualizarUsuarioUseCase};
use Repositories\Usuario\UsuarioRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use InvalidArgumentException;

class DocumentoClienteTest extends TestCase
{
    public static function cnpjsValidos(): array
    {
        return [['11222333000181', '11.222.333/0001-81'], ['11.222.333/0001-81', '11.222.333/0001-81'], ['12abc34501de35', '12.ABC.345/01DE-35'], ['00.000.000/E08G-12', '00.000.000/E08G-12']];
    }

    #[DataProvider('cnpjsValidos')]
    public function testNormalizaCnpj(string $entrada, string $esperado): void
    {
        self::assertSame($esperado, Validator::cnpj($entrada));
    }

    public static function cnpjsInvalidos(): array
    {
        return [[''], ['00000000000000'], ['11222333000182'], ['12ABC34501DE36'], ['1122233300018'], ['!11222333000181'], ['11/222/333/0001-81'], ['12ABC34501DEAB']];
    }

    #[DataProvider('cnpjsInvalidos')]
    public function testRejeitaCnpjInvalido(string $entrada): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::cnpj($entrada);
    }

    public function testCpfNaoDescartaLetras(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::cpf('a52998224725');
    }

    public static function documentosInvalidos(): array
    {
        return [[null, null], ['', ' '], ['52998224725', '11222333000181']];
    }

    #[DataProvider('documentosInvalidos')]
    public function testRejeitaCadastroSemDocumentoExclusivo(?string $cpf, ?string $cnpj): void
    {
        $repo = $this->createMock(UsuarioRepositoryInterface::class);
        $repo->expects($this->never())->method('salvar');
        $this->expectException(InvalidArgumentException::class);
        (new CriarUsuarioUseCase($repo))->executar(['nome'=>'Cliente Teste', 'email'=>'cliente@example.com', 'cpf'=>$cpf, 'cnpj'=>$cnpj]);
    }

    public function testCadastroNormalizaCnpj(): void
    {
        $repo = $this->createMock(UsuarioRepositoryInterface::class);
        $repo->expects($this->once())->method('cnpjJaCadastrado')->with('11.222.333/0001-81')->willReturn(false);
        $repo->method('salvar')->willReturnCallback(fn($u) => $u);
        $u = (new CriarUsuarioUseCase($repo))->executar(['nome'=>'Cliente Teste', 'email'=>'cliente@example.com', 'cnpj'=>'11222333000181']);
        self::assertNull($u->getCpf());
        self::assertSame('11.222.333/0001-81', $u->paraArray()['cnpj']);
    }

    public function testRejeitaCnpjDuplicado(): void
    {
        $repo = $this->createMock(UsuarioRepositoryInterface::class);
        $repo->method('cnpjJaCadastrado')->willReturn(true);
        $repo->expects($this->never())->method('salvar');
        $this->expectException(InvalidArgumentException::class);
        (new CriarUsuarioUseCase($repo))->executar(['nome'=>'Cliente Teste', 'email'=>'cliente@example.com', 'cnpj'=>'11222333000181']);
    }

    private function cliente(): Usuario
    {
        return new Usuario(1, 'Cliente Teste', 'cliente@example.com', null, '52998224725', TipoUsuario::CLIENTE, null, true, null, null);
    }

    public function testTrocaDocumentosNosDoisSentidosEPreservaEmAtualizacaoParcial(): void
    {
        $u = $this->cliente();
        $repo = $this->createMock(UsuarioRepositoryInterface::class);
        $repo->method('buscarPorId')->willReturn($u);
        $repo->expects($this->exactly(3))->method('atualizar')->willReturnCallback(fn($u) => $u);
        $case = new AtualizarUsuarioUseCase($repo);
        $case->executar(1, ['cpf'=>null, 'cnpj'=>'11222333000181']);
        self::assertNull($u->getCpf());
        $case->executar(1, ['nome'=>'Outro Cliente']);
        self::assertSame('11.222.333/0001-81', $u->getCnpj());
        $case->executar(1, ['cpf'=>'52998224725', 'cnpj'=>null]);
        self::assertNull($u->getCnpj());
        self::assertSame('529.982.247-25', $u->getCpf());
    }

    public static function alteracoesInvalidas(): array
    {
        return [[['cpf'=>null]], [['cnpj'=>'11222333000181']], [['cpf'=>' ', 'cnpj'=>null]]];
    }

    #[DataProvider('alteracoesInvalidas')]
    public function testAtualizacaoRejeitaEstadoFinalInvalido(array $dados): void
    {
        $repo = $this->createMock(UsuarioRepositoryInterface::class);
        $repo->method('buscarPorId')->willReturn($this->cliente());
        $repo->expects($this->never())->method('atualizar');
        $this->expectException(InvalidArgumentException::class);
        (new AtualizarUsuarioUseCase($repo))->executar(1, $dados);
    }

    public function testFuncionarioNaoAceitaCnpj(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::documentosUsuario(null, '11222333000181', 'mecanico');
    }
    public function testAtualizacaoRejeitaCnpjDeOutroCliente(): void
    {
        $repo = $this->createMock(UsuarioRepositoryInterface::class);
        $repo->method('buscarPorId')->willReturn($this->cliente());
        $repo->expects($this->once())->method('cnpjJaCadastrado')->with('11.222.333/0001-81', 1)->willReturn(true);
        $repo->expects($this->never())->method('atualizar');
        $this->expectException(InvalidArgumentException::class);
        (new AtualizarUsuarioUseCase($repo))->executar(1, ['cpf'=>null, 'cnpj'=>'11222333000181']);
    }

    public function testDominioImpedeRemoverDocumento(): void
    {
        $u = $this->cliente();
        $this->expectException(InvalidArgumentException::class);
        $u->setCpf(null);
    }

    public function testRejeitaDocumentoQueNaoSejaTexto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::documentosUsuario(null, ['11222333000181']);
    }}
