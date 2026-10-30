<?php

declare(strict_types=1);

namespace Http\Controllers;

use Application\Veiculo\AtualizarVeiculoUseCase;
use Application\Veiculo\BuscarVeiculoPorIdUseCase;
use Application\Veiculo\CriarVeiculoUseCase;
use Application\Veiculo\DeletarVeiculoUseCase;
use Application\Veiculo\ListarVeiculosUseCase;
use Domain\Exceptions\ValidacaoException;
use Infrastructure\Http\JsonResponse;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

class VeiculoController
{
    public function __construct(
        private CriarVeiculoUseCase       $criarUseCase,
        private ListarVeiculosUseCase     $listarUseCase,
        private BuscarVeiculoPorIdUseCase $buscarUseCase,
        private AtualizarVeiculoUseCase   $atualizarUseCase,
        private DeletarVeiculoUseCase     $deletarUseCase,
    ) {}

    // ------------------------------------------------------------------
    // GET /veiculos
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/veiculos",
        summary: "Lista todos os veículos ou filtra por cliente",
        tags: ["Veículos"],
        parameters: [
            new OA\Parameter(
                name: "cliente_id",
                description: "Filtrar veículos pelo ID do cliente",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de veículos retornada com sucesso",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "cliente_id", type: "integer", example: 1),
                            new OA\Property(property: "placa", type: "string", example: "ABC1D23"),
                            new OA\Property(property: "placa_formatada", type: "string", example: "ABC1D23"),
                            new OA\Property(property: "mercosul", type: "boolean", example: true),
                            new OA\Property(property: "marca", type: "string", example: "Volkswagen"),
                            new OA\Property(property: "modelo", type: "string", example: "Gol 1.0"),
                            new OA\Property(property: "ano", type: "integer", example: 2022),
                            new OA\Property(property: "cor", type: "string", nullable: true, example: "Prata"),
                            new OA\Property(property: "criado_em", type: "string", example: "2026-10-23 10:00:00"),
                            new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-23 10:00:00")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "erro", type: "string")]
                )
            )
        ]
    )]
    public function listar(array $params): void
    {
        $clienteId = !empty($_GET['cliente_id']) ? (int) $_GET['cliente_id'] : null;

        try {
            $veiculos = $this->listarUseCase->executar($clienteId);
            JsonResponse::send(array_map(fn($v) => $v->paraArray(), $veiculos));
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /veiculos/{id}
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/veiculos/{id}",
        summary: "Busca um veículo pelo ID",
        tags: ["Veículos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do veículo",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Veículo encontrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "cliente_id", type: "integer", example: 1),
                        new OA\Property(property: "placa", type: "string", example: "ABC1D23"),
                        new OA\Property(property: "placa_formatada", type: "string", example: "ABC1D23"),
                        new OA\Property(property: "mercosul", type: "boolean", example: true),
                        new OA\Property(property: "marca", type: "string", example: "Volkswagen"),
                        new OA\Property(property: "modelo", type: "string", example: "Gol 1.0"),
                        new OA\Property(property: "ano", type: "integer", example: 2022),
                        new OA\Property(property: "cor", type: "string", nullable: true, example: "Prata"),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-23 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-23 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Veículo não encontrado",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "erro", type: "string", example: "Veículo com ID 1 não encontrado.")]
                )
            )
        ]
    )]
    public function exibir(array $params): void
    {
        try {
            $veiculo = $this->buscarUseCase->executar((int) $params['id']);
            JsonResponse::send($veiculo->paraArray());
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /veiculos
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/veiculos",
        summary: "Cadastra um novo veículo vinculado a um cliente",
        tags: ["Veículos"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Dados para cadastro do veículo",
            content: new OA\JsonContent(
                required: ["cliente_id", "placa", "marca", "modelo", "ano"],
                properties: [
                    new OA\Property(property: "cliente_id", type: "integer", example: 1),
                    new OA\Property(property: "placa", type: "string", example: "ABC1D23"),
                    new OA\Property(property: "marca", type: "string", example: "Volkswagen"),
                    new OA\Property(property: "modelo", type: "string", example: "Gol 1.0"),
                    new OA\Property(property: "ano", type: "integer", example: 2022),
                    new OA\Property(property: "cor", type: "string", nullable: true, example: "Prata")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Veículo cadastrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "cliente_id", type: "integer", example: 1),
                        new OA\Property(property: "placa", type: "string", example: "ABC1D23"),
                        new OA\Property(property: "placa_formatada", type: "string", example: "ABC1D23"),
                        new OA\Property(property: "mercosul", type: "boolean", example: true),
                        new OA\Property(property: "marca", type: "string", example: "Volkswagen"),
                        new OA\Property(property: "modelo", type: "string", example: "Gol 1.0"),
                        new OA\Property(property: "ano", type: "integer", example: 2022),
                        new OA\Property(property: "cor", type: "string", nullable: true, example: "Prata"),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-23 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-23 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Erro de validação dos dados",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "erro", type: "string", example: "Placa inválida.")]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "erro", type: "string")]
                )
            )
        ]
    )]
    public function criar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $veiculo = $this->criarUseCase->executar($dados);
            JsonResponse::send($veiculo->paraArray(), 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /veiculos/{id}
    // ------------------------------------------------------------------
    #[OA\Put(
        path: "/veiculos/{id}",
        summary: "Atualiza os dados de um veículo",
        tags: ["Veículos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do veículo a ser atualizado",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Dados a serem atualizados",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cliente_id", type: "integer", example: 1),
                    new OA\Property(property: "placa", type: "string", example: "ABC1D23"),
                    new OA\Property(property: "marca", type: "string", example: "Volkswagen"),
                    new OA\Property(property: "modelo", type: "string", example: "Gol 1.6"),
                    new OA\Property(property: "ano", type: "integer", example: 2023),
                    new OA\Property(property: "cor", type: "string", nullable: true, example: "Prata")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Veículo atualizado com sucesso"
            ),
            new OA\Response(
                response: 404,
                description: "Veículo não encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "Erro de validação dos dados"
            )
        ]
    )]
    public function atualizar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $veiculo = $this->atualizarUseCase->executar((int) $params['id'], $dados);
            JsonResponse::send($veiculo->paraArray());
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /veiculos/{id}
    // ------------------------------------------------------------------
    #[OA\Delete(
        path: "/veiculos/{id}",
        summary: "Remove um veículo pelo ID",
        tags: ["Veículos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do veículo a ser removido",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Veículo removido com sucesso",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "mensagem", type: "string", example: "Veículo removido com sucesso.")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Veículo não encontrado",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "erro", type: "string", example: "Veículo com ID 1 não encontrado.")]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Conflito de integridade (ex.: ordens de serviço vinculadas)",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "erro", type: "string", example: "Não é possível remover este veículo porque existem ordens de serviço vinculadas a ele.")]
                )
            )
        ]
    )]
    public function remover(array $params): void
    {
        try {
            $this->deletarUseCase->executar((int) $params['id']);
            JsonResponse::send(['mensagem' => 'Veículo removido com sucesso.']);
        } catch (ValidacaoException | InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /clientes/{id}/veiculos
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/clientes/{id}/veiculos",
        summary: "Lista todos os veículos de um determinado cliente",
        tags: ["Veículos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do cliente",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de veículos do cliente",
                content: new OA\JsonContent(type: "array", items: new OA\Items())
            )
        ]
    )]
    public function listarPorCliente(array $params): void
    {
        try {
            $veiculos = $this->listarUseCase->executar((int) $params['id']);
            JsonResponse::send(array_map(fn($v) => $v->paraArray(), $veiculos));
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
