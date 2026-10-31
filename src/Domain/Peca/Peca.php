<?php

namespace Domain\Peca;

use InvalidArgumentException;

class Peca
{
    public function __construct(
        private ?int    $id,
        private string  $nome,
        private ?string $descricao,
        private float   $valorUnitario,
        private int     $quantidadeEstoque,
        private int     $estoqueMinimo,
        private bool    $ativo,
        private ?string $criadoEm,
        private ?string $atualizadoEm,
    ) {}

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function getValorUnitario(): float
    {
        return $this->valorUnitario;
    }

    public function getQuantidadeEstoque(): int
    {
        return $this->quantidadeEstoque;
    }

    public function getEstoqueMinimo(): int
    {
        return $this->estoqueMinimo;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function getCriadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function getAtualizadoEm(): ?string
    {
        return $this->atualizadoEm;
    }

    public function estaAbaixoDoEstoqueMinimo(): bool
    {
        return $this->quantidadeEstoque <= $this->estoqueMinimo;
    }

    // --- Setters ---

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function setDescricao(?string $descricao): void
    {
        $this->descricao = $descricao;
    }

    public function setValorUnitario(float $valorUnitario): void
    {
        $this->valorUnitario = $valorUnitario;
    }

    public function setQuantidadeEstoque(int $quantidadeEstoque): void
    {
        $this->quantidadeEstoque = $quantidadeEstoque;
    }

    public function setEstoqueMinimo(int $estoqueMinimo): void
    {
        $this->estoqueMinimo = $estoqueMinimo;
    }

    public function setAtivo(bool $ativo): void
    {
        $this->ativo = $ativo;
    }

    /**
     * Movimenta o saldo de estoque com validação de regras de negócio.
     */
    public function alterarEstoque(int $quantidade, bool $adicionar = true): void
    {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade de movimentação deve ser maior que zero.');
        }

        if ($adicionar) {
            $this->quantidadeEstoque += $quantidade;
        } else {
            if ($this->quantidadeEstoque < $quantidade) {
                throw new InvalidArgumentException(
                    "Estoque insuficiente para a peça '{$this->nome}'. Disponível: {$this->quantidadeEstoque}, Solicitado: {$quantidade}."
                );
            }
            $this->quantidadeEstoque -= $quantidade;
        }
    }

    /**
     * Serializa a peça para array.
     */
    public function paraArray(): array
    {
        return [
            'id'                 => $this->id,
            'nome'               => $this->nome,
            'descricao'          => $this->descricao,
            'valor_unitario'     => $this->valorUnitario,
            'quantidade_estoque' => $this->quantidadeEstoque,
            'estoque_minimo'     => $this->estoqueMinimo,
            'abaixo_minimo'      => $this->estaAbaixoDoEstoqueMinimo(),
            'ativo'              => $this->ativo,
            'criado_em'          => $this->criadoEm,
            'atualizado_em'      => $this->atualizadoEm,
        ];
    }
}
