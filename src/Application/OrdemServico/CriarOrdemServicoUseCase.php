<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\ItemPecaOS;
use Domain\OrdemServico\ItemServicoOS;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use Repositories\OrdemServico\OrdemServicoRepositoryInterface;
use Domain\Shared\Validator;
use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use Repositories\Peca\PecaRepositoryInterface;
use Repositories\Servico\ServicoRepositoryInterface;
use Repositories\Usuario\UsuarioRepositoryInterface;
use Repositories\Veiculo\VeiculoRepositoryInterface;

class CriarOrdemServicoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository,
        private UsuarioRepositoryInterface      $usuarioRepository,
        private VeiculoRepositoryInterface      $veiculoRepository,
        private ServicoRepositoryInterface      $servicoRepository,
        private PecaRepositoryInterface         $pecaRepository,
        private ?NotificadorOrdemServicoInterface $notificador = null,
    ) {}

    /**
     * @param array{
     *     cliente_id?: int,
     *     cliente_cpf?: string,
     *     cpf?: string,
     *     cliente_nome?: string,
     *     cliente_email?: string,
     *     cliente_telefone?: string,
     *     veiculo_id?: int,
     *     veiculo_placa?: string,
     *     placa?: string,
     *     veiculo_marca?: string,
     *     marca?: string,
     *     veiculo_modelo?: string,
     *     modelo?: string,
     *     veiculo_ano?: int,
     *     ano?: int,
     *     veiculo_cor?: string,
     *     cor?: string,
     *     descricao_problema?: string,
     *     diagnostico?: string,
     *     status?: string,
     *     servicos?: array,
     *     pecas?: array,
     *     enviar_orcamento?: bool
     * } $dados
     */
    public function executar(array $dados): OrdemServico
    {
        // 1. Identificação do Cliente (por ID ou CPF)
        $cliente = $this->obterOuCadastrarCliente($dados);

        // 2. Cadastro / Identificação do Veículo
        $veiculo = $this->obterOuCadastrarVeiculo($dados, $cliente->getId());

        // 3. Gerar número de OS único
        $numeroOs = $this->ordemServicoRepository->gerarProximoNumeroOs();

        // 4. Instanciar a OS
        if (array_key_exists('status', $dados)) {
            throw new ValidacaoException('O status da OS é definido automaticamente pelo fluxo de negócio.');
        }

        $os = OrdemServico::criar(
            numeroOs: $numeroOs,
            clienteId: (int) $cliente->getId(),
            veiculoId: (int) $veiculo->getId(),
            descricaoProblema: $dados['descricao_problema'] ?? null,
            diagnostico: $dados['diagnostico'] ?? null,
        );

        // 5. Inclusão dos Serviços Solicitados
        if (!empty($dados['servicos']) && is_array($dados['servicos'])) {
            foreach ($dados['servicos'] as $itemS) {
                $os->adicionarServico($this->processarItemServico($itemS));
            }
        }

        // 6. Inclusão de Peças e Insumos Necessários
        if (!empty($dados['pecas']) && is_array($dados['pecas'])) {
            foreach ($dados['pecas'] as $itemP) {
                $os->adicionarPeca($this->processarItemPeca($itemP));
            }
        }

        // Se foram incluídos diagnóstico, serviços ou peças e status ainda é RECEBIDA, muda para EM_DIAGNOSTICO
        if (
            $os->status() === StatusOrdemServico::RECEBIDA &&
            (!empty($os->diagnostico()) || count($os->servicos()) > 0 || count($os->pecas()) > 0)
        ) {
            $os->atualizarDiagnostico($os->diagnostico());
        }

        // 7. Envio do orçamento ao cliente para aprovação (se solicitado)
        if (!empty($dados['enviar_orcamento']) && $dados['enviar_orcamento'] === true) {
            $os->enviarOrcamentoParaAprovacao();
        }

        // 8. Persistir no banco de dados
        $resultado = $this->ordemServicoRepository->salvar($os);
        $this->notificador?->notificar($resultado, null);
        return $resultado;
    }

    private function obterOuCadastrarCliente(array $dados): Usuario
    {
        // Se fornecido cliente_id direto
        if (!empty($dados['cliente_id'])) {
            $cliente = $this->usuarioRepository->buscarPorId((int) $dados['cliente_id']);
            if (!$cliente) {
                throw new ValidacaoException('Cliente informado não foi encontrado.');
            }
            return $cliente;
        }

        // Identificação por CPF
        $documento = $dados['cliente_cpf'] ?? $dados['cpf'] ?? null;
        if (!$documento) {
            throw new ValidacaoException('Identificação do cliente é obrigatória (informe cliente_id ou CPF).');
        }

        $cpfNormalizado = Validator::cpf($documento);
        $cliente        = $this->usuarioRepository->buscarPorCpf($cpfNormalizado);

        if ($cliente) {
            return $cliente;
        }

        // Se o cliente não existir, verifica se foram fornecidos dados para autocadastro
        $nome     = $dados['cliente_nome'] ?? $dados['nome'] ?? null;
        $email    = $dados['cliente_email'] ?? $dados['email'] ?? null;
        $telefone = $dados['cliente_telefone'] ?? $dados['telefone'] ?? null;

        if (!$nome || !$email) {
            throw new ValidacaoException(
                sprintf('Cliente com CPF "%s" não localizado. Para cadastrá-lo automaticamente, informe "cliente_nome" e "cliente_email".', $documento)
            );
        }

        $novoCliente = new Usuario(
            id: null,
            nome: Validator::nome($nome),
            email: Validator::email($email),
            telefone: $telefone ? Validator::telefone($telefone) : null,
            cpf: $cpfNormalizado,
            tipoUsuario: TipoUsuario::CLIENTE,
            senhaHash: null,
            ativo: true,
            criadoEm: null,
            atualizadoEm: null,
        );

        return $this->usuarioRepository->salvar($novoCliente);
    }

    private function obterOuCadastrarVeiculo(array $dados, int $clienteId): Veiculo
    {
        // Se informado veiculo_id direto
        if (!empty($dados['veiculo_id'])) {
            $veiculo = $this->veiculoRepository->buscarPorId((int) $dados['veiculo_id']);
            if (!$veiculo) {
                throw new ValidacaoException('Veículo informado não foi encontrado.');
            }
            $this->validarVeiculoDoCliente($veiculo, $clienteId);
            return $veiculo;
        }

        // Identificação pela Placa
        $placaStr = $dados['veiculo_placa'] ?? $dados['placa'] ?? null;
        if (!$placaStr) {
            throw new ValidacaoException('Dados do veículo são obrigatórios (informe veiculo_id ou placa).');
        }

        $placa   = new Placa($placaStr);
        $veiculo = $this->veiculoRepository->buscarPorPlaca($placa);

        if ($veiculo) {
            $this->validarVeiculoDoCliente($veiculo, $clienteId);
            return $veiculo;
        }

        // Autocadastro de veículo: placa, marca, modelo, ano
        $marca  = $dados['veiculo_marca'] ?? $dados['marca'] ?? null;
        $modelo = $dados['veiculo_modelo'] ?? $dados['modelo'] ?? null;
        $ano    = isset($dados['veiculo_ano']) ? (int) $dados['veiculo_ano'] : (isset($dados['ano']) ? (int) $dados['ano'] : null);
        $cor    = $dados['veiculo_cor'] ?? $dados['cor'] ?? null;

        if (!$marca || !$modelo || !$ano) {
            throw new ValidacaoException(
                sprintf('Veículo com placa "%s" não encontrado. Para cadastrá-lo, informe marca, modelo e ano.', $placaStr)
            );
        }

        $novoVeiculo = new Veiculo(
            id: null,
            clienteId: $clienteId,
            placa: $placa,
            marca: $marca,
            modelo: $modelo,
            ano: $ano,
            cor: $cor,
            criadoEm: null,
            atualizadoEm: null,
        );

        return $this->veiculoRepository->salvar($novoVeiculo);
    }

    private function validarVeiculoDoCliente(Veiculo $veiculo, int $clienteId): void
    {
        if ($veiculo->getClienteId() !== $clienteId) {
            throw new ValidacaoException('O veículo informado não pertence ao cliente da ordem de serviço.');
        }
    }

    private function processarItemServico(array $item): ItemServicoOS
    {
        $servicoId = !empty($item['servico_id']) ? (int) $item['servico_id'] : null;
        if ($servicoId === null) {
            throw new ValidacaoException('O ID de um serviço cadastrado é obrigatório para incluí-lo na OS.');
        }

        $servicoDb = $this->servicoRepository->buscarPorId($servicoId);
        if ($servicoDb === null) {
            throw new ValidacaoException(sprintf('Serviço com ID %d não foi encontrado.', $servicoId));
        }

        $quantidade = isset($item['quantidade']) ? max(1, (int) $item['quantidade']) : 1;

        return ItemServicoOS::criar(
            servicoId: $servicoId,
            nome: $servicoDb->getNome(),
            descricao: $servicoDb->getDescricao(),
            quantidade: $quantidade,
            precoUnitario: $servicoDb->getPreco(),
            tempoEstimadoHoras: $servicoDb->getTempoEstimadoHoras(),
        );
    }

    private function processarItemPeca(array $item): ItemPecaOS
    {
        $pecaId = !empty($item['peca_id']) ? (int) $item['peca_id'] : null;
        if ($pecaId === null) {
            throw new ValidacaoException('O ID de uma peça cadastrada é obrigatório para incluí-la na OS.');
        }

        $pecaDb = $this->pecaRepository->buscarPorId($pecaId);
        if ($pecaDb === null) {
            throw new ValidacaoException(sprintf('Peça com ID %d não foi encontrada.', $pecaId));
        }

        $quantidade = isset($item['quantidade']) ? (float) $item['quantidade'] : 1.0;

        return ItemPecaOS::criar(
            pecaId: $pecaId,
            descricao: $pecaDb->getNome(),
            quantidade: $quantidade,
            precoUnitario: $pecaDb->getValorUnitario(),
        );
    }
}
