<?php

namespace Application\Peca;

use Domain\Peca\Peca;
use Domain\Shared\Validator;
use InvalidArgumentException;
use Repositories\Peca\PecaRepositoryInterface;

class CriarPecaUseCase
{
    public function __construct(
        private PecaRepositoryInterface $repository
    ) {}

    /**
     * @param array{
     *   nome: string,
     *   descricao?: string|null,
     *   valor_unitario: float|int|string,
     *   quantidade_estoque?: int|string,
     *   estoque_minimo?: int|string,
     *   ativo?: bool
     * } $dados
     */
    public function executar(array $dados): Peca
    {
        $dados = $this->validarESanitizar($dados);

        $peca = new Peca(
            id:                null,
            nome:              $dados['nome'],
            descricao:         $dados['descricao'] ?? null,
            valorUnitario:     $dados['valor_unitario'],
            quantidadeEstoque: $dados['quantidade_estoque'] ?? 0,
            estoqueMinimo:     $dados['estoque_minimo'] ?? 0,
            ativo:             $dados['ativo'] ?? true,
            criadoEm:          null,
            atualizadoEm:      null,
        );

        return $this->repository->salvar($peca);
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
        $dados['nome'] = Validator::nomePeca((string) $dados['nome']);

        // --- descricao (opcional, máximo 255 caracteres se informada) ---
        if (isset($dados['descricao']) && $dados['descricao'] !== null && trim((string) $dados['descricao']) !== '') {
            $descricao = trim((string) $dados['descricao']);
            if (mb_strlen($descricao) > 255) {
                throw new InvalidArgumentException('O campo "descricao" deve ter no máximo 255 caracteres.');
            }
            $dados['descricao'] = Validator::textoLivre($descricao, 'descricao');
        } else {
            $dados['descricao'] = null;
        }

        // --- valor_unitario (obrigatório) ---
        if (!isset($dados['valor_unitario'])) {
            throw new InvalidArgumentException('O campo "valor_unitario" é obrigatório.');
        }
        $dados['valor_unitario'] = Validator::valorUnitario($dados['valor_unitario']);

        // --- quantidade_estoque (opcional, default 0) ---
        if (isset($dados['quantidade_estoque'])) {
            $dados['quantidade_estoque'] = Validator::quantidadeEstoque($dados['quantidade_estoque'], 'Quantidade em estoque');
        } else {
            $dados['quantidade_estoque'] = 0;
        }

        // --- estoque_minimo (opcional, default 0) ---
        if (isset($dados['estoque_minimo'])) {
            $dados['estoque_minimo'] = Validator::quantidadeEstoque($dados['estoque_minimo'], 'Estoque mínimo');
        } else {
            $dados['estoque_minimo'] = 0;
        }

        // --- ativo (opcional, default true) ---
        if (isset($dados['ativo'])) {
            $dados['ativo'] = (bool) $dados['ativo'];
        }

        return $dados;
    }
}
