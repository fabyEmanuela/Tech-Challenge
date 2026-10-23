<?php

namespace Http\Controllers;

use Application\Usuario\AtualizarUsuarioUseCase;
use Application\Usuario\BuscarUsuarioPorIdUseCase;
use Application\Usuario\CriarUsuarioUseCase;
use Application\Usuario\DeletarUsuarioUseCase;
use Application\Usuario\ListarUsuariosUseCase;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class UsuarioController
{
    public function __construct(
        private CriarUsuarioUseCase       $criarUseCase,
        private ListarUsuariosUseCase     $listarUseCase,
        private BuscarUsuarioPorIdUseCase $buscarUseCase,
        private AtualizarUsuarioUseCase   $atualizarUseCase,
        private DeletarUsuarioUseCase     $deletarUseCase,
    ) {}

    // ------------------------------------------------------------------
    // GET /usuarios
    // ------------------------------------------------------------------
    public function listar(array $params): void
    {
        $tipo = $_GET['tipo'] ?? null;

        try {
            $usuarios = $this->listarUseCase->executar($tipo);
            $this->responderJson(array_map(fn($u) => $u->paraArray(), $usuarios));
        } catch (Throwable $e) {
            $this->responderJson(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /usuarios/{id}
    // ------------------------------------------------------------------
    public function exibir(array $params): void
    {
        try {
            $usuario = $this->buscarUseCase->executar((int) $params['id']);
            $this->responderJson($usuario->paraArray());
        } catch (RuntimeException $e) {
            $this->responderJson(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            $this->responderJson(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /usuarios
    // ------------------------------------------------------------------
    public function criar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $usuario = $this->criarUseCase->executar($dados);
            $this->responderJson($usuario->paraArray(), 201);
        } catch (InvalidArgumentException $e) {
            $this->responderJson(['erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            $this->responderJson(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /usuarios/{id}
    // ------------------------------------------------------------------
    public function atualizar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $usuario = $this->atualizarUseCase->executar((int) $params['id'], $dados);
            $this->responderJson($usuario->paraArray());
        } catch (InvalidArgumentException $e) {
            $this->responderJson(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            $this->responderJson(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            $this->responderJson(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /usuarios/{id}
    // ------------------------------------------------------------------
    public function remover(array $params): void
    {
        try {
            $this->deletarUseCase->executar((int) $params['id']);
            $this->responderJson(['mensagem' => 'Usuário removido com sucesso.']);
        } catch (RuntimeException $e) {
            $this->responderJson(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            $this->responderJson(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function lerCorpo(): array
    {
        $corpo = file_get_contents('php://input');
        $dados = json_decode($corpo, true);
        return is_array($dados) ? $dados : [];
    }

    private function responderJson(mixed $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
