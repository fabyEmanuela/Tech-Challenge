<?php

namespace Application\Servico;

use Domain\Servico\Servico;
use Domain\Shared\Validator;
use InvalidArgumentException;
use Repositories\Servico\ServicoRepositoryInterface;
use RuntimeException;

class AtualizarServicoUseCase
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    /**
     * @param array{
     *   nome?: string,
     *   descricao?: string|null,
     *   preco?: float|numeric,
     *   tempo_estimado_horas?: float|numeric,
     *   ativo?: bool
     * } $dados
     */
    public function executar(int $id, array $dados): Servico
    {
        $servico = $this->repository->buscarPorId($id);

        if ($servico === null) {
            throw new RuntimeException("Serviço com ID $id não encontrado.");
        }

        $dados = $this->validarESanitizar($dados);
        $this->aplicarAlteracoes($servico, $dados);

        return $this->repository->atualizar($servico);
    }

    /**
     * Valida e sanitiza somente os campos presentes no payload.
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados): array
    {
        // --- nome ---
        if (isset($dados['nome'])) {
            $dados['nome'] = Validator::nomeServico((string) $dados['nome']);
        }

        // --- descricao ---
        if (array_key_exists('descricao', $dados)) {
            if ($dados['descricao'] !== null && trim((string) $dados['descricao']) !== '') {
                $descricao = trim((string) $dados['descricao']);
                if (mb_strlen($descricao) > 255) {
                    throw new InvalidArgumentException('O campo "descricao" deve ter no máximo 255 caracteres.');
                }
                $dados['descricao'] = Validator::textoLivre($descricao, 'descricao');
            } else {
                $dados['descricao'] = null;
            }
        }

        // --- preco ---
        if (array_key_exists('preco', $dados)) {
            if (!is_numeric($dados['preco'])) {
                throw new InvalidArgumentException('O campo "preco" deve ser um valor numérico.');
            }
            $dados['preco'] = Validator::preco($dados['preco']);
        }

        // --- tempo_estimado_horas ---
        if (array_key_exists('tempo_estimado_horas', $dados)) {
            if (!is_numeric($dados['tempo_estimado_horas'])) {
                throw new InvalidArgumentException('O campo "tempo_estimado_horas" deve ser um valor numérico.');
            }
            $dados['tempo_estimado_horas'] = Validator::tempoEstimadoHoras($dados['tempo_estimado_horas']);
        }

        // --- ativo ---
        if (isset($dados['ativo'])) {
            $dados['ativo'] = (bool) $dados['ativo'];
        }

        return $dados;
    }

    private function aplicarAlteracoes(Servico $servico, array $dados): void
    {
        if (isset($dados['nome'])) {
            $servico->setNome($dados['nome']);
        }
        if (array_key_exists('descricao', $dados)) {
            $servico->setDescricao($dados['descricao']);
        }
        if (array_key_exists('preco', $dados)) {
            $servico->setPreco($dados['preco']);
        }
        if (array_key_exists('tempo_estimado_horas', $dados)) {
            $servico->setTempoEstimadoHoras($dados['tempo_estimado_horas']);
        }
        if (isset($dados['ativo'])) {
            $servico->setAtivo((bool) $dados['ativo']);
        }
    }
}
