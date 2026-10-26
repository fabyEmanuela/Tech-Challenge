<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

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
use Http\Controllers\ServicoController;
use Http\Controllers\UsuarioController;
use Http\Router;
use Infrastructure\Database\Connection;
use Repositories\Servico\ServicoPdoRepository;
use Repositories\Usuario\UsuarioPdoRepository;

// -----------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------

$pdo = Connection::getInstance();

// Repositórios
$usuarioRepository = new UsuarioPdoRepository($pdo);
$servicoRepository = new ServicoPdoRepository($pdo);

// Use Cases - Usuários
$criarUsuario     = new CriarUsuarioUseCase($usuarioRepository);
$listarUsuarios   = new ListarUsuariosUseCase($usuarioRepository);
$buscarUsuario    = new BuscarUsuarioPorIdUseCase($usuarioRepository);
$atualizarUsuario = new AtualizarUsuarioUseCase($usuarioRepository);
$deletarUsuario   = new DeletarUsuarioUseCase($usuarioRepository);

// Use Cases - Serviços
$criarServico         = new CriarServicoUseCase($servicoRepository);
$listarServicos       = new ListarServicosUseCase($servicoRepository);
$buscarServicoPorId   = new BuscarServicoPorIdUseCase($servicoRepository);
$buscarServicoPorNome = new BuscarServicoPorNomeUseCase($servicoRepository);
$atualizarServico     = new AtualizarServicoUseCase($servicoRepository);
$deletarServico       = new DeletarServicoUseCase($servicoRepository);

// Controllers
$usuarioController = new UsuarioController(
    $criarUsuario,
    $listarUsuarios,
    $buscarUsuario,
    $atualizarUsuario,
    $deletarUsuario,
);

$servicoController = new ServicoController(
    $criarServico,
    $listarServicos,
    $buscarServicoPorId,
    $buscarServicoPorNome,
    $atualizarServico,
    $deletarServico,
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

// Serviços
$router->add('GET',    '/servicos',        [$servicoController, 'listar']);
$router->add('GET',    '/servicos/buscar', [$servicoController, 'buscarPorNome']);
$router->add('GET',    '/servicos/{id}',   [$servicoController, 'exibir']);
$router->add('POST',   '/servicos',        [$servicoController, 'criar']);
$router->add('PUT',    '/servicos/{id}',   [$servicoController, 'atualizar']);
$router->add('DELETE', '/servicos/{id}',   [$servicoController, 'remover']);

// -----------------------------------------------------------------------
// Dispatch
// -----------------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
