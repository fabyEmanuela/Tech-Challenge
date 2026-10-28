<?php

declare(strict_types=1);

namespace Http\Controllers;

use Application\Servico\AtualizarServicoUseCase;
use Application\Servico\BuscarServicoPorIdUseCase;
use Application\Servico\BuscarServicoPorNomeUseCase;
use Application\Servico\CriarServicoUseCase;
use Application\Servico\DeletarServicoUseCase;
use Application\Servico\ListarServicosUseCase;
use Infrastructure\Http\JsonResponse;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

class ServicoController
{
    public function __construct(
        private CriarServicoUseCase         $criarUseCase,
        private ListarServicosUseCase       $listarUseCase,
        private BuscarServicoPorIdUseCase   $buscarPorIdUseCase,
        private BuscarServicoPorNomeUseCase $buscarPorNomeUseCase,
        private AtualizarServicoUseCase     $atualizarUseCase,
        private DeletarServicoUseCase       $deletarUseCase,
    ) {}

    // ------------------------------------------------------------------
    // GET /servicos
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/servicos",
        summary: "Lista todos os serviços ou busca por termo no nome",
        tags: ["Serviços"],
        parameters: [
            new OA\Parameter(
                name: "nome",
                description: "Termo para busca parcial no nome do serviço",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "óleo")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de serviços retornada com sucesso",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nome", type: "string", example: "Troca de Óleo"),
                            new OA\Property(property: "descricao", type: "string", nullable: true, example: "Troca de óleo do motor e substituição do filtro."),
                            new OA\Property(property: "preco", type: "number", format: "float", example: 150.00),
                            new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 1.5),
                            new OA\Property(property: "ativo", type: "boolean", example: true),
                            new OA\Property(property: "criado_em", type: "string", example: "2026-10-25 10:00:00"),
                            new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-25 10:00:00")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Erro ao listar serviços.")
                    ]
                )
            )
        ]
    )]
    public function listar(array $params): void
    {
        $nome = $_GET['nome'] ?? null;

        try {
            $servicos = $this->listarUseCase->executar($nome);
            JsonResponse::send(array_map(fn($s) => $s->paraArray(), $servicos));
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /servicos/buscar?nome={termo}
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/servicos/buscar",
        summary: "Busca serviços por correspondência parcial no nome",
        tags: ["Serviços"],
        parameters: [
            new OA\Parameter(
                name: "nome",
                description: "Termo a ser buscado no nome dos serviços",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "string", example: "Troca")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Serviços encontrados com sucesso",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nome", type: "string", example: "Troca de Óleo"),
                            new OA\Property(property: "descricao", type: "string", nullable: true, example: "Troca de óleo e filtro"),
                            new OA\Property(property: "preco", type: "number", format: "float", example: 150.00),
                            new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 1.5),
                            new OA\Property(property: "ativo", type: "boolean", example: true),
                            new OA\Property(property: "criado_em", type: "string", example: "2026-10-25 10:00:00"),
                            new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-25 10:00:00")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 422,
                description: "Parâmetro de busca inválido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "O parâmetro 'nome' é obrigatório para a busca.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor"
            )
        ]
    )]
    public function buscarPorNome(array $params): void
    {
        $nome = $_GET['nome'] ?? null;

        if ($nome === null || trim($nome) === '') {
            JsonResponse::send(['erro' => 'O parâmetro "nome" é obrigatório para a busca.'], 422);
            return;
        }

        try {
            $servicos = $this->buscarPorNomeUseCase->executar(trim($nome));
            JsonResponse::send(array_map(fn($s) => $s->paraArray(), $servicos));
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /servicos/{id}
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/servicos/{id}",
        summary: "Busca um serviço pelo ID",
        tags: ["Serviços"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do serviço",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Serviço encontrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "Alinhamento e Balanceamento"),
                        new OA\Property(property: "descricao", type: "string", nullable: true, example: "Alinhamento a laser e balanceamento 3D"),
                        new OA\Property(property: "preco", type: "number", format: "float", example: 120.00),
                        new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 1.0),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-25 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-25 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Serviço não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Serviço com ID 1 não encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor"
            )
        ]
    )]
    public function exibir(array $params): void
    {
        try {
            $servico = $this->buscarPorIdUseCase->executar((int) $params['id']);
            JsonResponse::send($servico->paraArray());
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /servicos
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/servicos",
        summary: "Cadastra um novo serviço",
        tags: ["Serviços"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Dados para cadastro do serviço",
            content: new OA\JsonContent(
                required: ["nome", "preco", "tempo_estimado_horas"],
                properties: [
                    new OA\Property(property: "nome", type: "string", example: "Troca de Pastilhas de Freio"),
                    new OA\Property(property: "descricao", type: "string", nullable: true, example: "Substituição completa do jogo de pastilhas dianteiras."),
                    new OA\Property(property: "preco", type: "number", format: "float", example: 220.50),
                    new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 2.0),
                    new OA\Property(property: "ativo", type: "boolean", default: true, example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Serviço cadastrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "Troca de Pastilhas de Freio"),
                        new OA\Property(property: "descricao", type: "string", nullable: true, example: "Substituição completa do jogo de pastilhas dianteiras."),
                        new OA\Property(property: "preco", type: "number", format: "float", example: 220.50),
                        new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 2.0),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-25 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-25 10:00:00")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Dados inválidos / Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Preço inválido: o valor não pode ser negativo.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor"
            )
        ]
    )]
    public function criar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $servico = $this->criarUseCase->executar($dados);
            JsonResponse::send($servico->paraArray(), 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /servicos/{id}
    // ------------------------------------------------------------------
    #[OA\Put(
        path: "/servicos/{id}",
        summary: "Atualiza os dados de um serviço",
        tags: ["Serviços"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do serviço a ser atualizado",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Campos do serviço a serem alterados",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nome", type: "string", example: "Troca de Pastilhas e Fluido de Freio"),
                    new OA\Property(property: "descricao", type: "string", nullable: true, example: "Substituição de pastilhas e sangria do fluido."),
                    new OA\Property(property: "preco", type: "number", format: "float", example: 280.00),
                    new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 2.5),
                    new OA\Property(property: "ativo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Serviço atualizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nome", type: "string", example: "Troca de Pastilhas e Fluido de Freio"),
                        new OA\Property(property: "descricao", type: "string", nullable: true, example: "Substituição de pastilhas e sangria do fluido."),
                        new OA\Property(property: "preco", type: "number", format: "float", example: 280.00),
                        new OA\Property(property: "tempo_estimado_horas", type: "number", format: "float", example: 2.5),
                        new OA\Property(property: "ativo", type: "boolean", example: true),
                        new OA\Property(property: "criado_em", type: "string", example: "2026-10-25 10:00:00"),
                        new OA\Property(property: "atualizado_em", type: "string", nullable: true, example: "2026-10-25 10:30:00")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Serviço não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Serviço com ID 1 não encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Dados inválidos / Erro de validação",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Preço inválido: o valor não pode ser negativo.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor"
            )
        ]
    )]
    public function atualizar(array $params): void
    {
        try {
            $dados   = $this->lerCorpo();
            $servico = $this->atualizarUseCase->executar((int) $params['id'], $dados);
            JsonResponse::send($servico->paraArray());
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /servicos/{id}
    // ------------------------------------------------------------------
    #[OA\Delete(
        path: "/servicos/{id}",
        summary: "Remove um serviço pelo ID",
        tags: ["Serviços"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID do serviço a ser removido",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Serviço removido com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "mensagem", type: "string", example: "Serviço removido com sucesso.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Serviço não encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Serviço com ID 1 não encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Erro interno no servidor"
            )
        ]
    )]
    public function remover(array $params): void
    {
        try {
            $this->deletarUseCase->executar((int) $params['id']);
            JsonResponse::send(['mensagem' => 'Serviço removido com sucesso.']);
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
