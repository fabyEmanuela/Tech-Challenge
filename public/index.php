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
use Http\Controllers\AuthController;
use Http\Controllers\OrdemServicoController;
use Http\Controllers\PecaController;
use Http\Controllers\ServicoController;
use Http\Controllers\UsuarioController;
use Http\Controllers\VeiculoController;
use Http\Middleware\AuthMiddleware;
use Http\Router;
use Infrastructure\Auth\JwtService;
use Infrastructure\Database\Connection;
use Infrastructure\Http\Controllers\HealthController;
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

// Autenticação & JWT
$jwtSecret      = getenv('JWT_SECRET') ?: 'sua-chave-secreta-padrao';
$jwtService     = new JwtService($jwtSecret);
$authMiddleware = new AuthMiddleware($jwtService);

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
$criarOrdemServico         = new CriarOrdemServicoUseCase($ordemServicoRepository, $usuarioRepository, $veiculoRepository, $servicoRepository, $pecaRepository);
$listarOrdensServico       = new ListarOrdensServicoUseCase($ordemServicoRepository);
$buscarOrdemServico        = new BuscarOrdemServicoPorIdUseCase($ordemServicoRepository);
$atualizarOrdemServico     = new AtualizarOrdemServicoUseCase($ordemServicoRepository, $servicoRepository, $pecaRepository);
$deletarOrdemServico       = new DeletarOrdemServicoUseCase($ordemServicoRepository);
$enviarOrcamento           = new EnviarOrcamentoUseCase($ordemServicoRepository);
$aprovarOrcamento          = new AprovarOrcamentoUseCase($ordemServicoRepository);
$alterarStatusOrdemServico = new AlterarStatusOrdemServicoUseCase($ordemServicoRepository);
$acompanharOrdemServico    = new AcompanharOrdemServicoUseCase($ordemServicoRepository);

// Use Cases - Peças
$criarPeca     = new CriarPecaUseCase($pecaRepository);
$listarPecas   = new ListarPecasUseCase($pecaRepository);
$buscarPeca    = new BuscarPecaPorIdUseCase($pecaRepository);
$atualizarPeca = new AtualizarPecaUseCase($pecaRepository);
$deletarPeca   = new DeletarPecaUseCase($pecaRepository);

// Controllers
$healthController = new HealthController();

$authController = new AuthController(
    $usuarioRepository,
    $jwtService,
);

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

// =======================================================================
// 1. Rotas Públicas (Sem token)
// =======================================================================
$router->add('GET',  '/',      [$healthController, 'check']);
$router->add('POST', '/login', [$authController, 'login']);

// =======================================================================
// 2. Middlewares de Controle de Acesso por Perfil 
// =======================================================================
$apenasAtendente    = $authMiddleware->comRoles(['atendente']);
$equipeInterna      = $authMiddleware->comRoles(['atendente', 'mecanico']);

// =======================================================================
// 3. Rotas de Usuários (Apenas Atendente pode gerenciar usuários/clientes)
// =======================================================================
$router->add('GET',    '/usuarios',       [$usuarioController, 'listar'],    [$apenasAtendente]);
$router->add('GET',    '/usuarios/{id}',  [$usuarioController, 'exibir'],    [$apenasAtendente]);
$router->add('POST',   '/usuarios',       [$usuarioController, 'criar'],     [$apenasAtendente]);
$router->add('PUT',    '/usuarios/{id}',  [$usuarioController, 'atualizar'], [$apenasAtendente]);
$router->add('DELETE', '/usuarios/{id}',  [$usuarioController, 'remover'],   [$apenasAtendente]);

// filtra por tipo_usuario=cliente
$router->add('GET', '/clientes', function (array $params) use ($usuarioController): void {
    $_GET['tipo'] = 'cliente';
    $usuarioController->listar($params);
}, [$equipeInterna]);

// =======================================================================
// 4. Rotas de Veículos
// =======================================================================
$router->add('GET',    '/veiculos',               [$veiculoController, 'listar'],           [$equipeInterna]);
$router->add('GET',    '/veiculos/{id}',          [$veiculoController, 'exibir'],           [$equipeInterna]);
$router->add('POST',   '/veiculos',               [$veiculoController, 'criar'],            [$apenasAtendente]);
$router->add('PUT',    '/veiculos/{id}',          [$veiculoController, 'atualizar'],        [$apenasAtendente]);
$router->add('DELETE', '/veiculos/{id}',          [$veiculoController, 'remover'],          [$apenasAtendente]);
$router->add('GET',    '/clientes/{id}/veiculos', [$veiculoController, 'listarPorCliente'], [$equipeInterna]);

// =======================================================================
// 5. Rotas de Serviços
// =======================================================================
$router->add('GET',    '/servicos',        [$servicoController, 'listar'],        [$equipeInterna]);
$router->add('GET',    '/servicos/buscar', [$servicoController, 'buscarPorNome'], [$equipeInterna]);
$router->add('GET',    '/servicos/{id}',   [$servicoController, 'exibir'],        [$equipeInterna]);
$router->add('POST',   '/servicos',        [$servicoController, 'criar'],         [$apenasAtendente]);
$router->add('PUT',    '/servicos/{id}',   [$servicoController, 'atualizar'],     [$apenasAtendente]);
$router->add('DELETE', '/servicos/{id}',   [$servicoController, 'remover'],       [$apenasAtendente]);

// =======================================================================
// 6. Rotas de Peças (Atendente e Mecânico)
// =======================================================================
$router->add('GET',    '/pecas',       [$pecaController, 'listar'],    [$equipeInterna]);
$router->add('GET',    '/pecas/{id}',  [$pecaController, 'exibir'],    [$equipeInterna]);
$router->add('POST',   '/pecas',       [$pecaController, 'criar'],     [$equipeInterna]);
$router->add('PUT',    '/pecas/{id}',  [$pecaController, 'atualizar'], [$equipeInterna]);
$router->add('DELETE', '/pecas/{id}',  [$pecaController, 'remover'],   [$apenasAtendente]);
// =======================================================================
// 7. Rotas de Ordens de Servico (OS)
// =======================================================================
// Rotas Administrativas e Operacionais (Protegidas por JWT)
$router->add('GET',    '/ordens-servico',                       [$ordemServicoController, 'listar'],           [$equipeInterna]);
$router->add('GET',    '/ordens-servico/{id}',                  [$ordemServicoController, 'exibir'],           [$equipeInterna]);
$router->add('POST',   '/ordens-servico',                       [$ordemServicoController, 'criar'],            [$equipeInterna]);
$router->add('PUT',    '/ordens-servico/{id}',                  [$ordemServicoController, 'atualizar'],        [$equipeInterna]);
$router->add('DELETE', '/ordens-servico/{id}',                  [$ordemServicoController, 'remover'],          [$apenasAtendente]);
$router->add('POST',   '/ordens-servico/{id}/enviar-orcamento', [$ordemServicoController, 'enviarOrcamento'], [$equipeInterna]);
$router->add('PATCH',  '/ordens-servico/{id}/status',           [$ordemServicoController, 'atualizarStatus'],   [$equipeInterna]);

// Rotas do Cliente (Acesso publico para consulta e aprovacao de orcamento)
$router->add('POST',   '/ordens-servico/{id}/aprovar-orcamento',[$ordemServicoController, 'aprovarOrcamento']);
$router->add('GET',    '/ordens-servico/{id}/acompanhamento',   [$ordemServicoController, 'acompanhar']);
$router->add('GET',    '/acompanhamento-os',                    [$ordemServicoController, 'consultarAcompanhamento']);


// -----------------------------------------------------------------------
// Dispatch
// -----------------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
