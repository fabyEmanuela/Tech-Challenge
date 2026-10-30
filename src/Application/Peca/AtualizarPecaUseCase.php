<?php

namespace Application\Peca;

use Domain\Peca\Peca;
use Domain\Shared\Validator;
use InvalidArgumentException;
use Repositories\Peca\PecaRepositoryInterface;
use RuntimeException;

class AtualizarPecaUseCase
{
    public function __construct(
        private PecaRepositoryInterface $repository
    ) {}

    /**
     * @param array{
     *   nome?: string,
     *   descricao?: string|null,
     *   valor_unitario?: float|int|string,
     *   quantidade_estoque?: int|string,
     *   estoque_minimo?: int|string,
     *   ativo?: bool
     * } $dados
     */
    public function executar(int $id, array $dados): Peca
    {
        $peca = $this->repository->buscarPorId($id);

        if ($peca === null) {
            throw new RuntimeException("Peça com ID $id não encontrada.");
        }

        $dados = $this->validarESanitizar($dados);
        $this->aplicarAlteracoes($peca, $dados);

        return $this->repository->atualizar($peca);
    }

    /**
     * Valida e sanitiza somente os campos presentes no payload (PATCH-like).
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados): array
    {
        // --- nome ---
        if (isset($dados['nome'])) {
            $dados['nome'] = Validator::nomePeca((string) $dados['nome']);
        }

        // --- descricao ---
        if (array_key_exists('descricao', $dados)) {
            if ($dados['descricao'] !== null && trim((string) $dados['descricao']) !== '') {
                $dados['descricao'] = Validator::textoMax255((string) $dados['descricao'], 'descricao');
            } else {
                $dados['descricao'] = null;
            }
        }

        // --- valor_unitario ---
        if (array_key_exists('valor_unitario', $dados)) {
            $dados['valor_unitario'] = Validator::valorUnitario($dados['valor_unitario']);
        }

        // --- quantidade_estoque ---
        if (array_key_exists('quantidade_estoque', $dados)) {
            $dados['quantidade_estoque'] = Validator::quantidadeEstoque($dados['quantidade_estoque'], 'Quantidade em estoque');
        }

        // --- estoque_minimo ---
        if (array_key_exists('estoque_minimo', $dados)) {
            $dados['estoque_minimo'] = Validator::quantidadeEstoque($dados['estoque_minimo'], 'Estoque mínimo');
        }

        // --- ativo ---
        if (isset($dados['ativo'])) {
            $dados['ativo'] = (bool) $dados['ativo'];
        }

        return $dados;
    }

    private function aplicarAlteracoes(Peca $peca, array $dados): void
    {
        if (isset($dados['nome'])) {
            $peca->setNome($dados['nome']);
        }
        if (array_key_exists('descricao', $dados)) {
            $peca->setDescricao($dados['descricao']);
        }
        if (array_key_exists('valor_unitario', $dados)) {
            $peca->setValorUnitario($dados['valor_unitario']);
        }
        if (array_key_exists('quantidade_estoque', $dados)) {
            $peca->setQuantidadeEstoque($dados['quantidade_estoque']);
        }
        if (array_key_exists('estoque_minimo', $dados)) {
            $peca->setEstoqueMinimo($dados['estoque_minimo']);
        }
        if (isset($dados['ativo'])) {
            $peca->setAtivo((bool) $dados['ativo']);
        }
    }
}
