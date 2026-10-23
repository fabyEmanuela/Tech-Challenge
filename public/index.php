<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Application\Usuario\AtualizarUsuarioUseCase;
use Application\Usuario\BuscarUsuarioPorIdUseCase;
use Application\Usuario\CriarUsuarioUseCase;
use Application\Usuario\DeletarUsuarioUseCase;
use Application\Usuario\ListarUsuariosUseCase;
use Http\Controllers\UsuarioController;
use Http\Router;
use Infrastructure\Database\Connection;
use Repositories\Usuario\UsuarioPdoRepository;

// -----------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------

$pdo = Connection::getInstance();

// Repositórios
$usuarioRepository = new UsuarioPdoRepository($pdo);

// Use Cases
$criarUsuario     = new CriarUsuarioUseCase($usuarioRepository);
$listarUsuarios   = new ListarUsuariosUseCase($usuarioRepository);
$buscarUsuario    = new BuscarUsuarioPorIdUseCase($usuarioRepository);
$atualizarUsuario = new AtualizarUsuarioUseCase($usuarioRepository);
$deletarUsuario   = new DeletarUsuarioUseCase($usuarioRepository);

// Controllers
$usuarioController = new UsuarioController(
    $criarUsuario,
    $listarUsuarios,
    $buscarUsuario,
    $atualizarUsuario,
    $deletarUsuario,
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

// -----------------------------------------------------------------------
// Dispatch
// -----------------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
