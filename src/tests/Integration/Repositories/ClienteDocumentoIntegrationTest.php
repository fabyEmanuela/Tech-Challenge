<?php
namespace Tests\Integration\Repositories;

use Application\Usuario\{CriarUsuarioUseCase, AtualizarUsuarioUseCase};
use Repositories\Usuario\UsuarioPdoRepository;
use Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PDOException;

class ClienteDocumentoIntegrationTest extends IntegrationTestCase
{
    public function testCadastroBuscaFiltroETrocaDeDocumento(): void
    {
        $repo = new UsuarioPdoRepository($this->pdo);
        $u = (new CriarUsuarioUseCase($repo))->executar(['nome'=>'Empresa Teste', 'email'=>'empresa@example.com', 'cnpj'=>'11222333000181']);
        self::assertNull($repo->buscarPorId($u->getId())->getCpf());
        self::assertSame($u->getId(), $repo->buscarPorCnpj('11222333000181')->getId());
        self::assertCount(1, $repo->listarTodos(cnpj:'11222333000181'));
        self::assertFalse($repo->cnpjJaCadastrado('11222333000181', $u->getId()));
        self::assertTrue($repo->cnpjJaCadastrado('11222333000181'));
        $case = new AtualizarUsuarioUseCase($repo);
        $u = $case->executar($u->getId(), ['cnpj'=>null, 'cpf'=>'52998224725']);
        self::assertNull($u->getCnpj());
        self::assertSame('529.982.247-25', $u->getCpf());
        $u = $case->executar($u->getId(), ['cpf'=>null, 'cnpj'=>'12ABC34501DE35']);
        self::assertSame('12.ABC.345/01DE-35', $u->getCnpj());
        self::assertSame($u->getId(), $repo->buscarPorCnpj('12abc34501de35')->getId());
    }

    public static function documentosInvalidos(): array
    {
        return [[null,null], ['529.982.247-25','11.222.333/0001-81'], ['',null], [null,' ']];
    }

    #[DataProvider('documentosInvalidos')]
    public function testBancoRejeitaDocumentosInvalidos(?string $cpf, ?string $cnpj): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome,email,cpf,cnpj) VALUES (?,?,?,?)');
        $this->expectException(PDOException::class);
        $stmt->execute(['Empresa Teste','invalida@example.com',$cpf,$cnpj]);
    }

    public function testBancoRejeitaCnpjDuplicado(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome,email,cnpj) VALUES (?,?,?)');
        $stmt->execute(['Empresa Um','um@example.com','11.222.333/0001-81']);
        $this->expectException(PDOException::class);
        $stmt->execute(['Empresa Dois','dois@example.com','11.222.333/0001-81']);
    }
    public function testCriaEAcompanhaOsComAutocadastroPorCnpj(): void
    {
        $usuarios = new UsuarioPdoRepository($this->pdo);
        $ordens = new \Repositories\OrdemServico\OrdemServicoPdoRepository($this->pdo);
        $case = new \Application\OrdemServico\CriarOrdemServicoUseCase(
            $ordens, $usuarios,
            new \Repositories\Veiculo\VeiculoPdoRepository($this->pdo),
            new \Repositories\Servico\ServicoPdoRepository($this->pdo),
            new \Repositories\Peca\PecaPdoRepository($this->pdo)
        );
        $os = $case->executar([
            'cliente_cnpj'=>'11222333000181', 'cliente_nome'=>'Empresa Teste', 'cliente_email'=>'os.cnpj@example.com',
            'veiculo_placa'=>'CNP1J23', 'veiculo_marca'=>'Fiat', 'veiculo_modelo'=>'Uno', 'veiculo_ano'=>2020,
            'descricao_problema'=>'Revisao do veiculo',
        ]);
        self::assertCount(1, $ordens->listar(['cnpj'=>'11222333000181']));
        self::assertCount(0, $ordens->listar(['cnpj'=>'12ABC34501DE35']));
        self::assertSame('11.222.333/0001-81', $ordens->buscarPorId($os->id())->toArray()['cliente']['cnpj']);
        $acompanhamento = (new \Application\OrdemServico\AcompanharOrdemServicoUseCase($ordens))->executarPorFiltros(['cnpj'=>'11222333000181']);
        self::assertSame('11.222.333/0001-81', $acompanhamento[0]['cliente']['cnpj']);
        $segunda = $case->executar(['cliente_cnpj'=>'11222333000181', 'veiculo_id'=>$os->toArray()['veiculo_id']]);
        self::assertSame($os->toArray()['cliente_id'], $segunda->toArray()['cliente_id']);
    }

    public function testBancoRejeitaRemocaoDoUnicoDocumento(): void
    {
        $this->pdo->exec("INSERT INTO usuarios (nome,email,cpf) VALUES ('Cliente Teste','update@example.com','529.982.247-25')");
        $this->expectException(PDOException::class);
        $this->pdo->exec("UPDATE usuarios SET cpf = NULL WHERE email = 'update@example.com'");
    }}
