<?php

namespace Tests\Integration\Repositories;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use Repositories\Usuario\UsuarioPdoRepository;
use Repositories\Veiculo\VeiculoPdoRepository;
use Tests\Integration\IntegrationTestCase;

class VeiculoPdoRepositoryTest extends IntegrationTestCase
{
    private VeiculoPdoRepository $veiculoRepository;
    private UsuarioPdoRepository $usuarioRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->veiculoRepository = new VeiculoPdoRepository($this->pdo);
        $this->usuarioRepository = new UsuarioPdoRepository($this->pdo);
    }

    /**
     * Cria um cliente real no banco para satisfazer a chave estrangeira (FK cliente_id)
     */
    private function criarCliente(string $cpf = '751.070.960-18', string $email = 'cliente.teste@gmail.com'): Usuario
    {
        $cliente = new Usuario(
            id:           null,
            nome:         'Cliente Teste Veiculo',
            email:        $email,
            telefone:     '(11) 98888-1111',
            cpf:          $cpf,
            tipoUsuario:  TipoUsuario::CLIENTE,
            senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        return $this->usuarioRepository->salvar($cliente);
    }

    public function testDeveInserirEBuscarVeiculoNoBancoReal(): void
    {
        $cliente = $this->criarCliente();

        $veiculo = new Veiculo(
            id:           null,
            clienteId:    $cliente->getId(),
            placa:        new Placa('BRA2E19'),
            marca:        'Toyota',
            modelo:       'Corolla XEi 2.0',
            ano:          2023,
            cor:          'Prata',
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->veiculoRepository->salvar($veiculo);

        $this->assertNotNull($salvo->getId());
        $this->assertSame($cliente->getId(), $salvo->getClienteId());
        $this->assertSame('BRA2E19', $salvo->getPlaca()->getValor());
        $this->assertSame('Toyota', $salvo->getMarca());
        $this->assertSame('Corolla XEi 2.0', $salvo->getModelo());
        $this->assertSame(2023, $salvo->getAno());

        // Busca direto no MySQL real por ID
        $buscadoPorId = $this->veiculoRepository->buscarPorId($salvo->getId());
        $this->assertNotNull($buscadoPorId);
        $this->assertSame($salvo->getId(), $buscadoPorId->getId());

        // Busca direto no MySQL real por Placa
        $buscadoPorPlaca = $this->veiculoRepository->buscarPorPlaca(new Placa('BRA2E19'));
        $this->assertNotNull($buscadoPorPlaca);
        $this->assertSame($salvo->getId(), $buscadoPorPlaca->getId());
    }

    public function testDeveListarVeiculosPorClienteIdNoBancoReal(): void
    {
        $cliente = $this->criarCliente('937.577.850-96', 'cliente.multiplos@gmail.com');

        $veiculo1 = new Veiculo(
            id:           null,
            clienteId:    $cliente->getId(),
            placa:        new Placa('ABC1D23'),
            marca:        'Volkswagen',
            modelo:       'Gol 1.0',
            ano:          2022,
            cor:          'Branco',
            criadoEm:     null,
            atualizadoEm: null,
        );

        $veiculo2 = new Veiculo(
            id:           null,
            clienteId:    $cliente->getId(),
            placa:        new Placa('XYZ9K88'),
            marca:        'Fiat',
            modelo:       'Strada 1.3',
            ano:          2024,
            cor:          'Vermelho',
            criadoEm:     null,
            atualizadoEm: null,
        );

        $this->veiculoRepository->salvar($veiculo1);
        $this->veiculoRepository->salvar($veiculo2);

        $veiculosDoCliente = $this->veiculoRepository->listarPorClienteId($cliente->getId());

        $this->assertCount(2, $veiculosDoCliente);
    }

    public function testDeveVerificarSePlacaJaEstaCadastradaNoBancoReal(): void
    {
        $cliente = $this->criarCliente('111.444.777-35', 'cliente.placa@gmail.com');

        $placa = new Placa('KTM5G60');
        $veiculo = new Veiculo(
            id:           null,
            clienteId:    $cliente->getId(),
            placa:        $placa,
            marca:        'Honda',
            modelo:       'Civic Touring',
            ano:          2021,
            cor:          'Preto',
            criadoEm:     null,
            atualizadoEm: null,
        );

        $this->assertFalse($this->veiculoRepository->placaJaCadastrada($placa));

        $salvo = $this->veiculoRepository->salvar($veiculo);

        $this->assertTrue($this->veiculoRepository->placaJaCadastrada($placa));

        // Ao verificar excluindo o próprio ID do veículo, deve retornar false
        $this->assertFalse($this->veiculoRepository->placaJaCadastrada($placa, $salvo->getId()));
    }

    public function testDeveAtualizarVeiculoNoBancoReal(): void
    {
        $cliente = $this->criarCliente('222.555.888-46', 'cliente.atualizar@gmail.com');

        $veiculo = new Veiculo(
            id:           null,
            clienteId:    $cliente->getId(),
            placa:        new Placa('MER1C23'),
            marca:        'Chevrolet',
            modelo:       'Onix 1.0 Turbo',
            ano:          2022,
            cor:          'Prata',
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->veiculoRepository->salvar($veiculo);

        // Modifica os dados
        $salvo->setModelo('Onix Premier 1.0 Turbo');
        $salvo->setCor('Cinza');

        $atualizado = $this->veiculoRepository->atualizar($salvo);

        $this->assertSame('Onix Premier 1.0 Turbo', $atualizado->getModelo());
        $this->assertSame('Cinza', $atualizado->getCor());

        // Confirma buscando novamente no MySQL
        $buscado = $this->veiculoRepository->buscarPorId($salvo->getId());
        $this->assertSame('Onix Premier 1.0 Turbo', $buscado->getModelo());
        $this->assertSame('Cinza', $buscado->getCor());
    }

    public function testDeveDeletarVeiculoDoBancoReal(): void
    {
        $cliente = $this->criarCliente('529.982.247-25', 'cliente.deletar@gmail.com');

        $veiculo = new Veiculo(
            id:           null,
            clienteId:    $cliente->getId(),
            placa:        new Placa('DEL9A00'),
            marca:        'Ford',
            modelo:       'Ka 1.0',
            ano:          2019,
            cor:          'Azul',
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->veiculoRepository->salvar($veiculo);
        $id = $salvo->getId();

        $deletado = $this->veiculoRepository->deletar($id);

        $this->assertTrue($deletado);
        $this->assertNull($this->veiculoRepository->buscarPorId($id));
    }
}

