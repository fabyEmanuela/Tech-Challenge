<?php

declare(strict_types=1);

namespace Http\Controllers;

use Application\OrdemServico\AcompanharOrdemServicoUseCase;
use Application\OrdemServico\AlterarStatusOrdemServicoUseCase;
use Application\OrdemServico\AprovarOrcamentoUseCase;
use Application\OrdemServico\AtualizarOrdemServicoUseCase;
use Application\OrdemServico\BuscarOrdemServicoPorIdUseCase;
use Application\OrdemServico\CriarOrdemServicoUseCase;
use Application\OrdemServico\DeletarOrdemServicoUseCase;
use Application\OrdemServico\EnviarOrcamentoUseCase;
use Application\OrdemServico\ListarOrdensServicoUseCase;
use Domain\Exceptions\ValidacaoException;
use Infrastructure\Http\JsonResponse;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Throwable;

class OrdemServicoController
{
    public function __construct(
        private CriarOrdemServicoUseCase         $criarUseCase,
        private ListarOrdensServicoUseCase       $listarUseCase,
        private BuscarOrdemServicoPorIdUseCase   $buscarUseCase,
        private AtualizarOrdemServicoUseCase     $atualizarUseCase,
        private DeletarOrdemServicoUseCase       $deletarUseCase,
        private EnviarOrcamentoUseCase           $enviarOrcamentoUseCase,
        private AprovarOrcamentoUseCase          $aprovarOrcamentoUseCase,
        private AlterarStatusOrdemServicoUseCase $alterarStatusUseCase,
        private AcompanharOrdemServicoUseCase    $acompanharUseCase,
    ) {}

    // ------------------------------------------------------------------
    // POST /ordens-servico
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/ordens-servico",
        summary: "Cria uma nova Ordem de Serviço (OS)",
        description: "Permite abrir uma OS identificando o cliente por ID ou CPF, cadastrando o veículo e incluindo serviços e peças previamente cadastrados. O orçamento é calculado automaticamente e pode ser enviado de imediato.",
        tags: ["Ordens de Serviço"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["descricao_problema"],
                properties: [
                    new OA\Property(property: "cliente_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "cliente_cpf", type: "string", nullable: true, example: "529.982.247-25"),
                    new OA\Property(property: "cliente_nome", type: "string", nullable: true, example: "João da Silva"),
                    new OA\Property(property: "cliente_email", type: "string", nullable: true, example: "joao.silva@email.com"),
                    new OA\Property(property: "cliente_telefone", type: "string", nullable: true, example: "(11) 98765-4321"),
                    new OA\Property(property: "veiculo_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "veiculo_placa", type: "string", nullable: true, example: "ABC1D23"),
                    new OA\Property(property: "veiculo_marca", type: "string", nullable: true, example: "Volkswagen"),
                    new OA\Property(property: "veiculo_modelo", type: "string", nullable: true, example: "Gol 1.0"),
                    new OA\Property(property: "veiculo_ano", type: "integer", nullable: true, example: 2022),
                    new OA\Property(property: "veiculo_cor", type: "string", nullable: true, example: "Prata"),
                    new OA\Property(property: "descricao_problema", type: "string", example: "Barulho na suspensão dianteira e troca de óleo"),
                    new OA\Property(property: "diagnostico", type: "string", nullable: true, example: "Necessário troca de amortecedores dianteiros e troca de óleo motor 5w30"),
                    new OA\Property(property: "enviar_orcamento", type: "boolean", nullable: true, example: false),
                    new OA\Property(
                        property: "servicos",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "servico_id", type: "integer", example: 1, description: "ID obrigatório de um serviço cadastrado"),
                                new OA\Property(property: "quantidade", type: "integer", example: 1)
                            ]
                        )
                    ),
                    new OA\Property(
                        property: "pecas",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "peca_id", type: "integer", example: 1, description: "ID obrigatório de uma peça cadastrada"),
                                new OA\Property(property: "quantidade", type: "number", format: "float", example: 4.0)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ordem de Serviço criada com sucesso"),
            new OA\Response(response: 400, description: "Dados inválidos"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function criar(array $params = []): void
    {
        try {
            $corpo = file_get_contents('php://input');
            $dados = json_decode($corpo, true);

            if (!is_array($dados)) {
                JsonResponse::send(['erro' => 'Corpo da requisição inválido. Envie um JSON válido.'], 400);
                return;
            }

            $os = $this->criarUseCase->executar($dados);
            JsonResponse::send($os->toArray(), 201);
        } catch (ValidacaoException|InvalidArgumentException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao criar ordem de serviço: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /ordens-servico
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/ordens-servico",
        summary: "Lista todas as Ordens de Serviço ou filtra por parâmetros",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "status", in: "query", required: false, description: "Filtrar por status (recebida, em_diagnostico, aguardando_aprovacao, em_execucao, finalizada, entregue)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "cliente_id", in: "query", required: false, description: "Filtrar por ID do cliente", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "veiculo_id", in: "query", required: false, description: "Filtrar por ID do veículo", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "cpf", in: "query", required: false, description: "Filtrar por CPF do cliente", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "placa", in: "query", required: false, description: "Filtrar por Placa do veículo", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de Ordens de Serviço retornada com sucesso"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function listar(): void
    {
        try {
            $filtros = [
                'status'     => $_GET['status'] ?? null,
                'cliente_id' => !empty($_GET['cliente_id']) ? (int) $_GET['cliente_id'] : null,
                'veiculo_id' => !empty($_GET['veiculo_id']) ? (int) $_GET['veiculo_id'] : null,
                'cpf'        => $_GET['cpf'] ?? null,
                'placa'      => $_GET['placa'] ?? null,
            ];

            $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');
            $ordens = $this->listarUseCase->executar($filtros);

            $dados = array_map(fn($os) => $os->toArray(), $ordens);
            JsonResponse::send($dados, 200);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao listar ordens de serviço: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /ordens-servico/{id}
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/ordens-servico/{id}",
        summary: "Busca os detalhes completos de uma Ordem de Serviço pelo ID",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Detalhes da OS retornados com sucesso"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function exibir(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $os = $this->buscarUseCase->executar($id);
            JsonResponse::send($os->toArray(), 200);
        } catch (ValidacaoException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao buscar ordem de serviço: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /ordens-servico/{id}
    // ------------------------------------------------------------------
    #[OA\Put(
        path: "/ordens-servico/{id}",
        summary: "Atualiza uma Ordem de Serviço existente",
        description: "Permite atualizar diagnóstico, relato de problema, serviços e peças. Para peças, itens existentes exigem seu ID na OS e novas inclusões exigem um peca_id cadastrado. O orçamento é recalculado automaticamente.",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "descricao_problema", type: "string", nullable: true),
                    new OA\Property(property: "diagnostico", type: "string", nullable: true),
                    new OA\Property(property: "enviar_orcamento", type: "boolean", nullable: true),
                    new OA\Property(property: "servicos", type: "array", items: new OA\Items(type: "object")),
                    new OA\Property(
                        property: "pecas",
                        type: "array",
                        items: new OA\Items(properties: [
                            new OA\Property(property: "id", type: "integer", nullable: true, description: "ID do item já associado à OS, obrigatório para editá-lo"),
                            new OA\Property(property: "peca_id", type: "integer", example: 1, description: "ID obrigatório de uma peça cadastrada"),
                            new OA\Property(property: "quantidade", type: "number", format: "float", example: 1.0)
                        ])
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ordem de Serviço atualizada com sucesso"),
            new OA\Response(response: 400, description: "Dados inválidos"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function atualizar(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $corpo = file_get_contents('php://input');
            $dados = json_decode($corpo, true);

            if (!is_array($dados)) {
                JsonResponse::send(['erro' => 'Corpo da requisição inválido. Envie um JSON válido.'], 400);
                return;
            }

            $os = $this->atualizarUseCase->executar($id, $dados);
            JsonResponse::send($os->toArray(), 200);
        } catch (ValidacaoException|InvalidArgumentException $e) {
            $statusHttp = str_contains($e->getMessage(), 'não foi encontrada') ? 404 : 400;
            JsonResponse::send(['erro' => $e->getMessage()], $statusHttp);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao atualizar ordem de serviço: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE /ordens-servico/{id}
    // ------------------------------------------------------------------
    #[OA\Delete(
        path: "/ordens-servico/{id}",
        summary: "Remove uma Ordem de Serviço",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Ordem de Serviço removida com sucesso"),
            new OA\Response(response: 400, description: "Operação não permitida"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function remover(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $this->deletarUseCase->executar($id);
            JsonResponse::send(['mensagem' => 'Ordem de serviço removida com sucesso.'], 200);
        } catch (ValidacaoException $e) {
            $statusHttp = str_contains($e->getMessage(), 'não foi encontrada') ? 404 : 400;
            JsonResponse::send(['erro' => $e->getMessage()], $statusHttp);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao remover ordem de serviço: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /ordens-servico/{id}/enviar-orcamento
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/ordens-servico/{id}/enviar-orcamento",
        summary: "Envia o orçamento da OS ao cliente para aprovação",
        description: "Calcula automaticamente o orçamento com base nos serviços e peças e altera o status para 'Aguardando aprovação'.",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "observacao", type: "string", nullable: true, example: "Orçamento enviado por WhatsApp e E-mail.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Orçamento enviado com sucesso"),
            new OA\Response(response: 400, description: "Operação não permitida"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function enviarOrcamento(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $corpo = file_get_contents('php://input');
            $dados = json_decode($corpo, true) ?: [];
            $observacao = $dados['observacao'] ?? null;

            $os = $this->enviarOrcamentoUseCase->executar($id, $observacao);
            JsonResponse::send([
                'mensagem' => 'Orçamento gerado e enviado ao cliente para aprovação com sucesso.',
                'ordem_servico' => $os->toArray()
            ], 200);
        } catch (ValidacaoException $e) {
            $statusHttp = str_contains($e->getMessage(), 'não foi encontrada') ? 404 : 400;
            JsonResponse::send(['erro' => $e->getMessage()], $statusHttp);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao enviar orçamento: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /ordens-servico/{id}/aprovar-orcamento
    // ------------------------------------------------------------------
    #[OA\Post(
        path: "/ordens-servico/{id}/aprovar-orcamento",
        summary: "Registra a aprovação do orçamento pelo cliente",
        description: "Altera o status automaticamente de 'Aguardando aprovação' para 'Em execução'.",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "observacao", type: "string", nullable: true, example: "Aprovado via telefone pelo cliente.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Orçamento aprovado com sucesso"),
            new OA\Response(response: 400, description: "Operação não permitida"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function aprovarOrcamento(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $corpo = file_get_contents('php://input');
            $dados = json_decode($corpo, true) ?: [];
            $observacao = $dados['observacao'] ?? null;

            $os = $this->aprovarOrcamentoUseCase->executar($id, $observacao);
            JsonResponse::send([
                'mensagem' => 'Orçamento aprovado com sucesso. Status alterado para Em execução.',
                'ordem_servico' => $os->toArray()
            ], 200);
        } catch (ValidacaoException $e) {
            $statusHttp = str_contains($e->getMessage(), 'não foi encontrada') ? 404 : 400;
            JsonResponse::send(['erro' => $e->getMessage()], $statusHttp);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao aprovar orçamento: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // PATCH /ordens-servico/{id}/status
    // ------------------------------------------------------------------
    #[OA\Patch(
        path: "/ordens-servico/{id}/status",
        summary: "Atualiza o status da Ordem de Serviço",
        description: "Permite transicionar o status da OS com validação de fluxo (recebida, em_diagnostico, aguardando_aprovacao, em_execucao, finalizada, entregue).",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", example: "finalizada"),
                    new OA\Property(property: "observacao", type: "string", nullable: true, example: "Serviços de suspensão finalizados e testados.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status atualizado com sucesso"),
            new OA\Response(response: 400, description: "Transição de status inválida"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function atualizarStatus(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $corpo = file_get_contents('php://input');
            $dados = json_decode($corpo, true);

            if (!isset($dados['status'])) {
                JsonResponse::send(['erro' => 'O campo "status" é obrigatório.'], 400);
                return;
            }

            $observacao = $dados['observacao'] ?? null;
            $os = $this->alterarStatusUseCase->executar($id, (string) $dados['status'], $observacao);

            JsonResponse::send([
                'mensagem' => sprintf('Status da OS atualizado para "%s" com sucesso.', $os->status()->rotulo()),
                'ordem_servico' => $os->toArray()
            ], 200);
        } catch (ValidacaoException $e) {
            $statusHttp = str_contains($e->getMessage(), 'não foi encontrada') ? 404 : 400;
            JsonResponse::send(['erro' => $e->getMessage()], $statusHttp);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao atualizar status: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /ordens-servico/{id}/acompanhamento
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/ordens-servico/{id}/acompanhamento",
        summary: "Acompanhamento do progresso da OS pelo cliente",
        description: "Retorna a linha do tempo completa, percentual de conclusão, status atual, orçamento e itens para que o cliente acompanhe pela API.",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID da Ordem de Serviço", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Acompanhamento retornado com sucesso"),
            new OA\Response(response: 404, description: "OS não encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function acompanhar(array $params): void
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $dados = $this->acompanharUseCase->executarPorId($id);
            JsonResponse::send($dados, 200);
        } catch (ValidacaoException $e) {
            JsonResponse::send(['erro' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao consultar acompanhamento: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /acompanhamento-os
    // ------------------------------------------------------------------
    #[OA\Get(
        path: "/acompanhamento-os",
        summary: "Consulta pública/cliente de acompanhamento de OS",
        description: "Permite ao cliente consultar o progresso de sua OS informando CPF, Placa do veículo ou Número da OS.",
        tags: ["Ordens de Serviço"],
        parameters: [
            new OA\Parameter(name: "cpf", in: "query", required: false, description: "CPF do cliente", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "placa", in: "query", required: false, description: "Placa do veículo", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "numero_os", in: "query", required: false, description: "Número da OS (ex: OS-20261028-0001)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "id", in: "query", required: false, description: "ID da OS", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Acompanhamento retornado com sucesso"),
            new OA\Response(response: 400, description: "Parâmetros insuficientes"),
            new OA\Response(response: 404, description: "Nenhuma OS encontrada"),
            new OA\Response(response: 500, description: "Erro interno no servidor")
        ]
    )]
    public function consultarAcompanhamento(): void
    {
        try {
            $filtros = [
                'cpf'       => $_GET['cpf'] ?? null,
                'placa'     => $_GET['placa'] ?? null,
                'numero_os' => $_GET['numero_os'] ?? null,
                'id'        => !empty($_GET['id']) ? (int) $_GET['id'] : null,
            ];

            $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');
            $dados = $this->acompanharUseCase->executarPorFiltros($filtros);

            JsonResponse::send($dados, 200);
        } catch (ValidacaoException $e) {
            $statusHttp = str_contains($e->getMessage(), 'não localizada') || str_contains($e->getMessage(), 'Nenhuma ordem') ? 404 : 400;
            JsonResponse::send(['erro' => $e->getMessage()], $statusHttp);
        } catch (Throwable $e) {
            JsonResponse::send(['erro' => 'Erro ao consultar acompanhamento: ' . $e->getMessage()], 500);
        }
    }
}
