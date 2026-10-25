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
use Throwable;

#[OA\Tag(name: "Veículos", description: "Endpoints para gerenciamento de veículos vinculados a clientes")]
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
    public function listar(array $params = []): void
    {
        try {
            $clienteId = !empty($_GET['cliente_id']) ? (int) $_GET['cliente_id'] : null;
            $veiculos = $this->listarUseCase->executar($clienteId);

            $dados = array_map(fn($v) => $v->toArray(), $veiculos);
            JsonResponse::send($dados, 200);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao listar veículos: ' . $e->getMessage()], 500);
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
                    properties: [new OA\Property(property: "erro", type: "string", example: "Veículo não encontrado.")]
                )
            )
        ]
    )]
    public function exibir(array $params): void
    {
        try {
            $id = $this->lerId($params);
            $veiculo = $this->buscarUseCase->executar($id);

            if ($veiculo === null) {
                JsonResponse::send(['erro' => 'Veículo não encontrado.'], 404);
                return;
            }

            JsonResponse::send($veiculo->toArray(), 200);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao buscar veículo.'], 500);
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
    public function criar(array $params = []): void
    {
        try {
            $dados = $this->lerCorpo();
            $veiculo = $this->criarUseCase->executar($dados);
            JsonResponse::send($veiculo->toArray(), 201);
        } catch (ValidacaoException | InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao criar veículo: ' . $e->getMessage()], 500);
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
            $id = $this->lerId($params);
            $dados = $this->lerCorpo();
            $veiculo = $this->atualizarUseCase->executar($id, $dados);
            JsonResponse::send($veiculo->toArray(), 200);
        } catch (ValidacaoException | InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'não encontrado') ? 404 : 422;
            JsonResponse::send(['erro' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao atualizar veículo: ' . $e->getMessage()], 500);
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
            )
        ]
    )]
    public function remover(array $params): void
    {
        try {
            $id = $this->lerId($params);
            $this->deletarUseCase->executar($id);
            JsonResponse::send(['mensagem' => 'Veículo removido com sucesso.'], 200);
        } catch (ValidacaoException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao remover veículo: ' . $e->getMessage()], 500);
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
            $clienteId = $this->lerId($params);
            $veiculos = $this->listarUseCase->executar($clienteId);
            $dados = array_map(fn($v) => $v->toArray(), $veiculos);
            JsonResponse::send($dados, 200);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao listar veículos do cliente: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // Métodos Auxiliares
    // ------------------------------------------------------------------
    private function lerId(array $params): int
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new InvalidArgumentException('ID inválido.');
        }
        return $id;
    }

    private function lerCorpo(): array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return [];
        }

        $dados = json_decode($input, true);
        if (!is_array($dados)) {
            throw new InvalidArgumentException('JSON inválido no corpo da requisição.');
        }

        return $dados;
    }
}
