<?php

namespace Application\Servico;

use Domain\Servico\Servico;
use Domain\Shared\Validator;
use InvalidArgumentException;
use Repositories\Servico\ServicoRepositoryInterface;

class CriarServicoUseCase
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    /**
     * @param array{
     *   nome: string,
     *   descricao?: string|null,
     *   preco: float|numeric,
     *   tempo_estimado_horas: float|numeric,
     *   ativo?: bool
     * } $dados
     */
    public function executar(array $dados): Servico
    {
        $dados = $this->validarESanitizar($dados);

        $servico = new Servico(
            id:                 null,
            nome:               $dados['nome'],
            descricao:          $dados['descricao'] ?? null,
            preco:              $dados['preco'],
            tempoEstimadoHoras: $dados['tempo_estimado_horas'],
            ativo:              $dados['ativo'] ?? true,
            criadoEm:           null,
            atualizadoEm:       null,
        );

        return $this->repository->salvar($servico);
    }

    /**
     * Valida e sanitiza todos os campos de entrada.
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados): array
    {
        // --- nome (obrigatório) ---
        if (empty($dados['nome']) || trim((string) $dados['nome']) === '') {
            throw new InvalidArgumentException('O campo "nome" é obrigatório.');
        }
        $dados['nome'] = Validator::nomeServico((string) $dados['nome']);

        // --- descricao (opcional, máximo 255 caracteres se informada) ---
        if (isset($dados['descricao']) && $dados['descricao'] !== null && trim((string) $dados['descricao']) !== '') {
            $dados['descricao'] = Validator::textoMax255((string) $dados['descricao'], 'descricao');
        } else {
            $dados['descricao'] = null;
        }

        // --- preco (obrigatório, numérico e não negativo) ---
        if (!isset($dados['preco']) || !is_numeric($dados['preco'])) {
            throw new InvalidArgumentException('O campo "preco" é obrigatório e deve ser um número.');
        }
        $dados['preco'] = Validator::preco($dados['preco']);

        // --- tempo_estimado_horas (obrigatório, numérico e não negativo) ---
        if (!isset($dados['tempo_estimado_horas']) || !is_numeric($dados['tempo_estimado_horas'])) {
            throw new InvalidArgumentException('O campo "tempo_estimado_horas" é obrigatório e deve ser um número.');
        }
        $dados['tempo_estimado_horas'] = Validator::tempoEstimadoHoras($dados['tempo_estimado_horas']);

        // --- ativo (opcional, bool) ---
        if (isset($dados['ativo'])) {
            $dados['ativo'] = (bool) $dados['ativo'];
        }

        return $dados;
    }
}
