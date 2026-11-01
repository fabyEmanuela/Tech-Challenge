<?php

declare(strict_types=1);

namespace Domain\OrdemServico;

use Domain\Exceptions\ValidacaoException;

final class ItemServicoOS
{
    public function __construct(
        private ?int $id,
        private ?int $servicoId,
        private string $nome,
        private ?string $descricao,
        private int $quantidade,
        private float $precoUnitario,
        private float $tempoEstimadoHoras = 0.0,
        private ?string $criadoEm = null,
    ) {
        $this->validar();
    }

    public static function criar(
        ?int $servicoId,
        string $nome,
        ?string $descricao,
        int $quantidade,
        float $precoUnitario,
        float $tempoEstimadoHoras = 0.0
    ): self {
        return new self(
            id: null,
            servicoId: $servicoId,
            nome: trim($nome),
            descricao: $descricao !== null ? trim($descricao) : null,
            quantidade: $quantidade,
            precoUnitario: round($precoUnitario, 2),
            tempoEstimadoHoras: round($tempoEstimadoHoras, 2),
            criadoEm: null,
        );
    }

    private function validar(): void
    {
        if (trim($this->nome) === '') {
            throw new ValidacaoException('O nome do serviço é obrigatório.');
        }

        if ($this->quantidade <= 0) {
            throw new ValidacaoException('A quantidade de serviços deve ser no mínimo 1.');
        }

        if ($this->precoUnitario < 0) {
            throw new ValidacaoException('O preço unitário do serviço não pode ser negativo.');
        }

        if ($this->tempoEstimadoHoras < 0) {
            throw new ValidacaoException('O tempo estimado em horas não pode ser negativo.');
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

    public function servicoId(): ?int
    {
        return $this->servicoId;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function descricao(): ?string
    {
        return $this->descricao;
    }

    public function quantidade(): int
    {
        return $this->quantidade;
    }

    public function precoUnitario(): float
    {
        return $this->precoUnitario;
    }

    public function tempoEstimadoHoras(): float
    {
        return $this->tempoEstimadoHoras;
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
            'id'                   => $this->id,
            'servico_id'           => $this->servicoId,
            'nome'                 => $this->nome,
            'descricao'            => $this->descricao,
            'quantidade'           => $this->quantidade,
            'preco_unitario'       => $this->precoUnitario,
            'tempo_estimado_horas' => $this->tempoEstimadoHoras,
            'subtotal'             => $this->subtotal(),
            'criado_em'            => $this->criadoEm,
        ];
    }
}
