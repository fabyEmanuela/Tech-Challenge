<?php

declare(strict_types=1);

namespace Domain\OrdemServico;

use Domain\Exceptions\ValidacaoException;

final class ItemPecaOS
{
    public function __construct(
        private ?int $id,
        private ?int $pecaId,
        private string $descricao,
        private float $quantidade,
        private float $precoUnitario,
        private ?string $criadoEm = null,
    ) {
        $this->validar();
    }

    public static function criar(
        ?int $pecaId,
        string $descricao,
        float $quantidade,
        float $precoUnitario
    ): self {
        return new self(
            id: null,
            pecaId: $pecaId,
            descricao: trim($descricao),
            quantidade: round($quantidade, 2),
            precoUnitario: round($precoUnitario, 2),
            criadoEm: null,
        );
    }

    private function validar(): void
    {
        if (trim($this->descricao) === '') {
            throw new ValidacaoException('A descrição da peça/insumo é obrigatória.');
        }

        if ($this->quantidade <= 0) {
            throw new ValidacaoException('A quantidade da peça/insumo deve ser maior que zero.');
        }

        if ($this->precoUnitario < 0) {
            throw new ValidacaoException('O preço unitário da peça/insumo não pode ser negativo.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        $this->id = $id;
    }

    public function pecaId(): ?int
    {
        return $this->pecaId;
    }

    public function descricao(): string
    {
        return $this->descricao;
    }

    public function quantidade(): float
    {
        return $this->quantidade;
    }

    public function precoUnitario(): float
    {
        return $this->precoUnitario;
    }

    public function subtotal(): float
    {
        return round($this->quantidade * $this->precoUnitario, 2);
    }

    public function criadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'peca_id'        => $this->pecaId,
            'descricao'      => $this->descricao,
            'quantidade'     => $this->quantidade,
            'preco_unitario' => $this->precoUnitario,
            'subtotal'       => $this->subtotal(),
            'criado_em'      => $this->criadoEm,
        ];
    }
}
