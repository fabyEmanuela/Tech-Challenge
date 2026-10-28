<?php

namespace Domain\Servico;

class Servico
{
    public function __construct(
        private ?int       $id,
        private string     $nome,
        private ?string    $descricao,
        private float      $preco,
        private float      $tempoEstimadoHoras,
        private bool       $ativo,
        private ?string    $criadoEm,
        private ?string    $atualizadoEm,
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

    public function getPreco(): float
    {
        return $this->preco;
    }

    public function getTempoEstimadoHoras(): float
    {
        return $this->tempoEstimadoHoras;
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

    // --- Setters ---

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function setDescricao(?string $descricao): void
    {
        $this->descricao = $descricao;
    }

    public function setPreco(float $preco): void
    {
        $this->preco = $preco;
    }

    public function setTempoEstimadoHoras(float $tempoEstimadoHoras): void
    {
        $this->tempoEstimadoHoras = $tempoEstimadoHoras;
    }

    public function setAtivo(bool $ativo): void
    {
        $this->ativo = $ativo;
    }

    /**
     * Serializa o serviço para array.
     */
    public function paraArray(): array
    {
        return [
            'id'                   => $this->id,
            'nome'                 => $this->nome,
            'descricao'            => $this->descricao,
            'preco'                => $this->preco,
            'tempo_estimado_horas' => $this->tempoEstimadoHoras,
            'ativo'                => $this->ativo,
            'criado_em'            => $this->criadoEm,
            'atualizado_em'        => $this->atualizadoEm,
        ];
    }
}
