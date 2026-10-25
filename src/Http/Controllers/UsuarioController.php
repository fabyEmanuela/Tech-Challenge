<?php

declare(strict_types=1);

namespace Http\Controllers;

use Application\Usuario\AtualizarUsuarioUseCase;
use Application\Usuario\BuscarUsuarioPorIdUseCase;
use Application\Usuario\CriarUsuarioUseCase;
use Application\Usuario\DeletarUsuarioUseCase;
use Application\Usuario\ListarUsuariosUseCase;
use Infrastructure\Http\JsonResponse;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
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
    // GET /usuarios e GET /clientes
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/usuarios",
        summary: "Lista todos os usuários ou filtra por tipo",
        tags: ["Usuários"],
        parameters: [
            new OA\Parameter(
                name: "tipo",
                description: "Tipo de usuário para filtragem",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["cliente", "mecanico", "atendente"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de usuários retornada com sucesso",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nome", type: "string", example: "João Silva"),
                            new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                            new OA\Property(property: "telefone", type: "string", nullable: true, example: "11987654321"),
                            new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                            new OA\Property(property: "tipo_usuario", type: "string", enum: ["cliente", "mecanico", "atendente"], example: "cliente"),
                            new OA\Property(property: "ativo", type: "boolean", example: true),
                            new OA\Property(property: "criado_em", type: "string", example: "2026-10-22 10:00:00"),
                            new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-22 10:00:00")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao listar usuários.")
                    ]
                )
            )
        ]
    )]
    #[OA\Get(
        path: "/clientes",
        summary: "Lista todos os clientes",
        description: "Alias para /usuarios?tipo=cliente",
        tags: ["Usuários"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de clientes retornada com sucesso",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nome", type: "string", example: "Maria Santos"),
                            new OA\Property(property: "email", type: "string", example: "maria@email.com"),
                            new OA\Property(property: "telefone", type: "string", nullable: true, example: "11987654321"),
                            new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                            new OA\Property(property: "tipo_usuario", type: "string", example: "cliente"),
                            new OA\Property(property: "ativo", type: "boolean", example: true),
                            new OA\Property(property: "criado_em", type: "string", example: "2026-10-22 10:00:00"),
                            new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-22 10:00:00")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor"
            )
        ]
    )]
    public function listar(array $params): void
    {
        $tipo = $_GET['tipo'] ?? null;

        try {
            $usuarios = $this->listarUseCase->executar($tipo);
            JsonResponse::send(array_map(fn($u) => $u->paraArray(), $usuarios));
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /usuarios/{id}
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/usuarios/{id}",
        summary: "Busca um usuário pelo ID",
        tags: ["Usuários"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do usuário",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Usuário encontrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "João Silva"),
                        new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                        new OA\Property(property: "telefone", type: "string", nullable: true, example: "11987654321"),
                        new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                        new OA\Property(property: "tipo_usuario", type: "string", example: "cliente"),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-22 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-22 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Usuário não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Usuário com ID 1 não encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao buscar usuário.")
                    ]
                )
            )
        ]
    )]
    public function exibir(array $params): void
    {
        try {
            $usuario = $this->buscarUseCase->executar((int) $params['id']);
            JsonResponse::send($usuario->paraArray());
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /usuarios
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/usuarios",
        summary: "Cadastra um novo usuário",
        tags: ["Usuários"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Dados para cadastro do usuário",
            content: new OA\JsonContent(
                required: ["nome", "email"],
                properties: [
                    new OA\Property(property: "nome", type: "string", example: "João Silva"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao@email.com"),
                    new OA\Property(property: "telefone", type: "string", nullable: true, example: "11987654321"),
                    new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                    new OA\Property(property: "tipo_usuario", type: "string", enum: ["cliente", "mecanico", "atendente"], default: "cliente", example: "cliente"),
                    new OA\Property(property: "senha", type: "string", format: "password", nullable: true, example: "senha123"),
                    new OA\Property(property: "ativo", type: "boolean", default: true, example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuário cadastrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "João Silva"),
                        new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                        new OA\Property(property: "telefone", type: "string", nullable: true, example: "11987654321"),
                        new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                        new OA\Property(property: "tipo_usuario", type: "string", example: "cliente"),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-22 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-22 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Dados inválidos / Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "O campo 'nome' é obrigatório.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao criar usuário.")
                    ]
                )
            )
        ]
    )]
    public function criar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $usuario = $this->criarUseCase->executar($dados);
            JsonResponse::send($usuario->paraArray(), 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /usuarios/{id}
    // ------------------------------------------------------------------
    #[OA\Put(
        path: "/usuarios/{id}",
        summary: "Atualiza os dados de um usuário",
        tags: ["Usuários"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do usuário a ser atualizado",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Campos do usuário a serem alterados",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nome", type: "string", example: "João da Silva"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao.silva@email.com"),
                    new OA\Property(property: "telefone", type: "string", nullable: true, example: "11999998888"),
                    new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                    new OA\Property(property: "tipo_usuario", type: "string", enum: ["cliente", "mecanico", "atendente"], example: "cliente"),
                    new OA\Property(property: "senha", type: "string", format: "password", nullable: true, example: "nova_senha123"),
                    new OA\Property(property: "ativo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Usuário atualizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "João da Silva"),
                        new OA\Property(property: "email", type: "string", example: "joao.silva@email.com"),
                        new OA\Property(property: "telefone", type: "string", nullable: true, example: "11999998888"),
                        new OA\Property(property: "cpf", type: "string", nullable: true, example: "123.456.789-00"),
                        new OA\Property(property: "tipo_usuario", type: "string", example: "cliente"),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-22 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-22 10:30:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Usuário não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Usuário com ID 1 não encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Dados inválidos / Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Já existe outro usuário com este e-mail.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao atualizar usuário.")
                    ]
                )
            )
        ]
    )]
    public function atualizar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $usuario = $this->atualizarUseCase->executar((int) $params['id'], $dados);
            JsonResponse::send($usuario->paraArray());
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /usuarios/{id}
    // ------------------------------------------------------------------
    #[OA\Delete(
        path: "/usuarios/{id}",
        summary: "Remove um usuário pelo ID",
        tags: ["Usuários"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do usuário a ser removido",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Usuário removido com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "mensagem", type: "string", example: "Usuário removido com sucesso.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Usuário não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Usuário com ID 1 não encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao deletar usuário.")
                    ]
                )
            )
        ]
    )]
    public function remover(array $params): void
    {
        try {
            $this->deletarUseCase->executar((int) $params['id']);
            JsonResponse::send(['mensagem' => 'Usuário removido com sucesso.']);
        } catch (\Domain\Exceptions\ValidacaoException | InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
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
}
