<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// -----------------------------------------------------------------------
// CORS (Cross-Origin Resource Sharing)
// Pro Swagger (porta 8081) consuma a API (porta 8080)
// -----------------------------------------------------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

use Application\OrdemServico\AcompanharOrdemServicoUseCase;
use Application\OrdemServico\AlterarStatusOrdemServicoUseCase;
use Application\OrdemServico\AprovarOrcamentoUseCase;
use Application\OrdemServico\AtualizarOrdemServicoUseCase;
use Application\OrdemServico\BuscarOrdemServicoPorIdUseCase;
use Application\OrdemServico\CriarOrdemServicoUseCase;
use Application\OrdemServico\DeletarOrdemServicoUseCase;
use Application\OrdemServico\EnviarOrcamentoUseCase;
use Application\OrdemServico\ListarOrdensServicoUseCase;
use Application\Peca\AtualizarPecaUseCase;
use Application\Peca\BuscarPecaPorIdUseCase;
use Application\Peca\CriarPecaUseCase;
use Application\Peca\DeletarPecaUseCase;
use Application\Peca\ListarPecasUseCase;
use Application\Servico\AtualizarServicoUseCase;
use Application\Servico\BuscarServicoPorIdUseCase;
use Application\Servico\BuscarServicoPorNomeUseCase;
use Application\Servico\CriarServicoUseCase;
use Application\Servico\DeletarServicoUseCase;
use Application\Servico\ListarServicosUseCase;
use Application\Usuario\AtualizarUsuarioUseCase;
use Application\Usuario\BuscarUsuarioPorIdUseCase;
use Application\Usuario\CriarUsuarioUseCase;
use Application\Usuario\DeletarUsuarioUseCase;
use Application\Usuario\ListarUsuariosUseCase;
use Application\Veiculo\AtualizarVeiculoUseCase;
use Application\Veiculo\BuscarVeiculoPorIdUseCase;
use Application\Veiculo\CriarVeiculoUseCase;
use Application\Veiculo\DeletarVeiculoUseCase;
use Application\Veiculo\ListarVeiculosUseCase;
use Http\Controllers\OrdemServicoController;
use Http\Controllers\PecaController;
use Http\Controllers\ServicoController;
use Http\Controllers\UsuarioController;
use Http\Controllers\VeiculoController;
use Http\Router;
use Infrastructure\Database\Connection;
use Repositories\OrdemServico\OrdemServicoPdoRepository;
use Repositories\Peca\PecaPdoRepository;
use Repositories\Servico\ServicoPdoRepository;
use Repositories\Usuario\UsuarioPdoRepository;
use Repositories\Veiculo\VeiculoPdoRepository;

// -----------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------

$pdo = Connection::getInstance();

// Repositórios
$usuarioRepository      = new UsuarioPdoRepository($pdo);
$servicoRepository      = new ServicoPdoRepository($pdo);
$veiculoRepository      = new VeiculoPdoRepository($pdo);
$ordemServicoRepository = new OrdemServicoPdoRepository($pdo);
$pecaRepository    = new PecaPdoRepository($pdo);

// Use Cases - Usuários
$criarUsuario     = new CriarUsuarioUseCase($usuarioRepository);
$listarUsuarios   = new ListarUsuariosUseCase($usuarioRepository);
$buscarUsuario    = new BuscarUsuarioPorIdUseCase($usuarioRepository);
$atualizarUsuario = new AtualizarUsuarioUseCase($usuarioRepository);
$deletarUsuario   = new DeletarUsuarioUseCase($usuarioRepository);

// Use Cases - Veículos
$criarVeiculo     = new CriarVeiculoUseCase($veiculoRepository, $usuarioRepository);
$listarVeiculos   = new ListarVeiculosUseCase($veiculoRepository);
$buscarVeiculo    = new BuscarVeiculoPorIdUseCase($veiculoRepository);
$atualizarVeiculo = new AtualizarVeiculoUseCase($veiculoRepository, $usuarioRepository);
$deletarVeiculo   = new DeletarVeiculoUseCase($veiculoRepository);

// Use Cases - Serviços
$criarServico         = new CriarServicoUseCase($servicoRepository);
$listarServicos       = new ListarServicosUseCase($servicoRepository);
$buscarServicoPorId   = new BuscarServicoPorIdUseCase($servicoRepository);
$buscarServicoPorNome = new BuscarServicoPorNomeUseCase($servicoRepository);
$atualizarServico     = new AtualizarServicoUseCase($servicoRepository);
$deletarServico       = new DeletarServicoUseCase($servicoRepository);

// Use Cases - Ordens de Serviço
$notificadorEmail = new \Infrastructure\Email\NotificadorOrdemServicoEmail();
$criarOrdemServico         = new CriarOrdemServicoUseCase($ordemServicoRepository, $usuarioRepository, $veiculoRepository, $servicoRepository, $pecaRepository, notificador: $notificadorEmail);
$listarOrdensServico       = new ListarOrdensServicoUseCase($ordemServicoRepository);
$buscarOrdemServico        = new BuscarOrdemServicoPorIdUseCase($ordemServicoRepository);
$atualizarOrdemServico     = new AtualizarOrdemServicoUseCase($ordemServicoRepository, $servicoRepository, $pecaRepository, notificador: $notificadorEmail);
$deletarOrdemServico       = new DeletarOrdemServicoUseCase($ordemServicoRepository);
$enviarOrcamento           = new EnviarOrcamentoUseCase($ordemServicoRepository, notificador: $notificadorEmail);
$aprovarOrcamento          = new AprovarOrcamentoUseCase($ordemServicoRepository, notificador: $notificadorEmail);
$alterarStatusOrdemServico = new AlterarStatusOrdemServicoUseCase($ordemServicoRepository, notificador: $notificadorEmail);
$acompanharOrdemServico    = new AcompanharOrdemServicoUseCase($ordemServicoRepository);

// Use Cases - Peças
$criarPeca     = new CriarPecaUseCase($pecaRepository);
$listarPecas   = new ListarPecasUseCase($pecaRepository);
$buscarPeca    = new BuscarPecaPorIdUseCase($pecaRepository);
$atualizarPeca = new AtualizarPecaUseCase($pecaRepository);
$deletarPeca   = new DeletarPecaUseCase($pecaRepository);

// Controllers
$usuarioController = new UsuarioController(
    $criarUsuario,
    $listarUsuarios,
    $buscarUsuario,
    $atualizarUsuario,
    $deletarUsuario,
);

$veiculoController = new VeiculoController(
    $criarVeiculo,
    $listarVeiculos,
    $buscarVeiculo,
    $atualizarVeiculo,
    $deletarVeiculo,
);

$servicoController = new ServicoController(
    $criarServico,
    $listarServicos,
    $buscarServicoPorId,
    $buscarServicoPorNome,
    $atualizarServico,
    $deletarServico,
);

$ordemServicoController = new OrdemServicoController(
    $criarOrdemServico,
    $listarOrdensServico,
    $buscarOrdemServico,
    $atualizarOrdemServico,
    $deletarOrdemServico,
    $enviarOrcamento,
    $aprovarOrcamento,
    $alterarStatusOrdemServico,
    $acompanharOrdemServico,
);

$pecaController = new PecaController(
    $criarPeca,
    $listarPecas,
    $buscarPeca,
    $atualizarPeca,
    $deletarPeca,
);

// -----------------------------------------------------------------------
// Roteamento
// -----------------------------------------------------------------------

$router = new Router();

// Usuários
$router->add('GET',    '/usuarios',       [$usuarioController, 'listar']);
$router->add('GET',    '/usuarios/{id}',  [$usuarioController, 'exibir']);
$router->add('POST',   '/usuarios',       [$usuarioController, 'criar']);
$router->add('PUT',    '/usuarios/{id}',  [$usuarioController, 'atualizar']);
$router->add('DELETE', '/usuarios/{id}',  [$usuarioController, 'remover']);

// Alias para clientes (filtra por tipo_usuario=cliente)
$router->add('GET', '/clientes', function (array $params) use ($usuarioController): void {
    $_GET['tipo'] = 'cliente';
    $usuarioController->listar($params);
});

// Veículos
$router->add('GET',    '/veiculos',               [$veiculoController, 'listar']);
$router->add('GET',    '/veiculos/{id}',          [$veiculoController, 'exibir']);
$router->add('POST',   '/veiculos',               [$veiculoController, 'criar']);
$router->add('PUT',    '/veiculos/{id}',          [$veiculoController, 'atualizar']);
$router->add('DELETE', '/veiculos/{id}',          [$veiculoController, 'remover']);
$router->add('GET',    '/clientes/{id}/veiculos', [$veiculoController, 'listarPorCliente']);

// Serviços
$router->add('GET',    '/servicos',        [$servicoController, 'listar']);
$router->add('GET',    '/servicos/buscar', [$servicoController, 'buscarPorNome']);
$router->add('GET',    '/servicos/{id}',   [$servicoController, 'exibir']);
$router->add('POST',   '/servicos',        [$servicoController, 'criar']);
$router->add('PUT',    '/servicos/{id}',   [$servicoController, 'atualizar']);
$router->add('DELETE', '/servicos/{id}',   [$servicoController, 'remover']);

// Ordens de Serviço (OS)
$router->add('GET',    '/ordens-servico',                       [$ordemServicoController, 'listar']);
$router->add('GET',    '/ordens-servico/{id}',                  [$ordemServicoController, 'exibir']);
$router->add('POST',   '/ordens-servico',                       [$ordemServicoController, 'criar']);
$router->add('PUT',    '/ordens-servico/{id}',                  [$ordemServicoController, 'atualizar']);
$router->add('DELETE', '/ordens-servico/{id}',                  [$ordemServicoController, 'remover']);
$router->add('POST',   '/ordens-servico/{id}/enviar-orcamento', [$ordemServicoController, 'enviarOrcamento']);
$router->add('POST',   '/ordens-servico/{id}/aprovar-orcamento',[$ordemServicoController, 'aprovarOrcamento']);
$router->add('PATCH',  '/ordens-servico/{id}/status',           [$ordemServicoController, 'atualizarStatus']);
$router->add('GET',    '/ordens-servico/{id}/acompanhamento',   [$ordemServicoController, 'acompanhar']);
$router->add('GET',    '/acompanhamento-os',                    [$ordemServicoController, 'consultarAcompanhamento']);

// Peças
$router->add('GET',    '/pecas',       [$pecaController, 'listar']);
$router->add('GET',    '/pecas/{id}',  [$pecaController, 'exibir']);
$router->add('POST',   '/pecas',       [$pecaController, 'criar']);
$router->add('PUT',    '/pecas/{id}',  [$pecaController, 'atualizar']);
$router->add('DELETE', '/pecas/{id}',  [$pecaController, 'remover']);

// -----------------------------------------------------------------------
// Dispatch
// -----------------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
