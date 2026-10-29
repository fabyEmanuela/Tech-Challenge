<?php

declare(strict_types=1);

namespace Http\Controllers;

use Application\Peca\AtualizarPecaUseCase;
use Application\Peca\BuscarPecaPorIdUseCase;
use Application\Peca\CriarPecaUseCase;
use Application\Peca\DeletarPecaUseCase;
use Application\Peca\ListarPecasUseCase;
use Infrastructure\Http\JsonResponse;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

class PecaController
{
    public function __construct(
        private CriarPecaUseCase       $criarUseCase,
        private ListarPecasUseCase     $listarUseCase,
        private BuscarPecaPorIdUseCase $buscarUseCase,
        private AtualizarPecaUseCase   $atualizarUseCase,
        private DeletarPecaUseCase     $deletarUseCase,
    ) {}

    // ------------------------------------------------------------------
    // GET /pecas
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/pecas",
        summary: "Lista todas as peças ou filtra por status de ativação",
        tags: ["Peças"],
        parameters: [
            new OA\Parameter(
                name: "ativo",
                description: "Filtrar por peças ativas (true/1) ou inativas (false/0)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de peças retornada com sucesso",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nome", type: "string", example: "Filtro de Óleo"),
                            new OA\Property(property: "descricao", type: "string", nullable: true, example: "Filtro de óleo para motores 1.0 e 1.6"),
                            new OA\Property(property: "valor_unitario", type: "number", format: "float", example: 45.90),
                            new OA\Property(property: "quantidade_estoque", type: "integer", example: 25),
                            new OA\Property(property: "estoque_minimo", type: "integer", example: 5),
                            new OA\Property(property: "abaixo_minimo", type: "boolean", example: false),
                            new OA\Property(property: "ativo", type: "boolean", example: true),
                            new OA\Property(property: "criado_em", type: "string", example: "2026-10-28 10:00:00"),
                            new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-28 10:00:00")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao listar peças.")
                    ]
                )
            )
        ]
    )]
    public function listar(array $params): void
    {
        $ativo = $_GET['ativo'] ?? null;

        try {
            $pecas = $this->listarUseCase->executar($ativo !== null ? (string) $ativo : null);
            JsonResponse::send(array_map(fn($p) => $p->paraArray(), $pecas));
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /pecas/{id}
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/pecas/{id}",
        summary: "Busca uma peça pelo ID",
        tags: ["Peças"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID da peça",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Peça encontrada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "Filtro de Óleo"),
                        new OA\Property(property: "descricao", type: "string", nullable: true, example: "Filtro de óleo para motores 1.0 e 1.6"),
                        new OA\Property(property: "valor_unitario", type: "number", format: "float", example: 45.90),
                        new OA\Property(property: "quantidade_estoque", type: "integer", example: 25),
                        new OA\Property(property: "estoque_minimo", type: "integer", example: 5),
                        new OA\Property(property: "abaixo_minimo", type: "boolean", example: false),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-28 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-28 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Peça não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Peça com ID 1 não encontrada.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao buscar peça.")
                    ]
                )
            )
        ]
    )]
    public function exibir(array $params): void
    {
        try {
            $peca = $this->buscarUseCase->executar((int) $params['id']);
            JsonResponse::send($peca->paraArray());
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /pecas
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/pecas",
        summary: "Cadastra uma nova peça ou insumo",
        tags: ["Peças"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Dados para cadastro da peça",
            content: new OA\JsonContent(
                required: ["nome", "valor_unitario"],
                properties: [
                    new OA\Property(property: "nome", type: "string", example: "Filtro de Óleo"),
                    new OA\Property(property: "descricao", type: "string", nullable: true, example: "Filtro de óleo para motores 1.0 e 1.6"),
                    new OA\Property(property: "valor_unitario", type: "number", format: "float", example: 45.90),
                    new OA\Property(property: "quantidade_estoque", type: "integer", default: 0, example: 25),
                    new OA\Property(property: "estoque_minimo", type: "integer", default: 0, example: 5),
                    new OA\Property(property: "ativo", type: "boolean", default: true, example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Peça cadastrada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "mensagem", type: "string", example: "Peça cadastrada com sucesso.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Dados inválidos / Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "O campo \"nome\" é obrigatório.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao criar peça.")
                    ]
                )
            )
        ]
    )]
    public function criar(array $params): void
    {
        try {
            $dados = $this->lerCorpo();
            $this->criarUseCase->executar($dados);
            JsonResponse::send(['mensagem' => 'Peça cadastrada com sucesso.'], 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /pecas/{id}
    // ------------------------------------------------------------------
    #[OA\Put(
        path: "/pecas/{id}",
        summary: "Atualiza os dados e o saldo de estoque de uma peça",
        tags: ["Peças"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID da peça a ser atualizada",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Campos da peça a serem alterados",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nome", type: "string", example: "Filtro de Óleo Sintético"),
                    new OA\Property(property: "descricao", type: "string", nullable: true, example: "Filtro de óleo para motores 1.0 e 1.6"),
                    new OA\Property(property: "valor_unitario", type: "number", format: "float", example: 49.90),
                    new OA\Property(property: "quantidade_estoque", type: "integer", example: 20),
                    new OA\Property(property: "estoque_minimo", type: "integer", example: 5),
                    new OA\Property(property: "ativo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Peça atualizada com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "Filtro de Óleo Sintético"),
                        new OA\Property(property: "descricao", type: "string", nullable: true, example: "Filtro de óleo para motores 1.0 e 1.6"),
                        new OA\Property(property: "valor_unitario", type: "number", format: "float", example: 49.90),
                        new OA\Property(property: "quantidade_estoque", type: "integer", example: 20),
                        new OA\Property(property: "estoque_minimo", type: "integer", example: 5),
                        new OA\Property(property: "abaixo_minimo", type: "boolean", example: false),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-28 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-28 10:30:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Peça não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Peça com ID 1 não encontrada.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Dados inválidos / Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Nome da peça inválido: deve ter pelo menos 3 caracteres.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao atualizar peça.")
                    ]
                )
            )
        ]
    )]
    public function atualizar(array $params): void
    {
        try {
            $dados = $this->lerCorpo();
            $peca  = $this->atualizarUseCase->executar((int) $params['id'], $dados);
            JsonResponse::send($peca->paraArray());
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /pecas/{id}
    // ------------------------------------------------------------------
    #[OA\Delete(
        path: "/pecas/{id}",
        summary: "Remove uma peça pelo ID",
        tags: ["Peças"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID da peça a ser removida",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Peça removida com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "mensagem", type: "string", example: "Peça removida com sucesso.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Peça não encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Peça com ID 1 não encontrada.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao deletar peça.")
                    ]
                )
            )
        ]
    )]
    public function remover(array $params): void
    {
        try {
            $this->deletarUseCase->executar((int) $params['id']);
            JsonResponse::send(['mensagem' => 'Peça removida com sucesso.']);
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
