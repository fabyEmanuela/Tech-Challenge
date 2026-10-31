<?php

namespace Domain\Veiculo;

class Veiculo
{
    public function __construct(
        private ?int    $id,
        private int     $clienteId,
        private Placa   $placa,
        private string  $marca,
        private string  $modelo,
        private int     $ano,
        private ?string $cor,
        private ?string $criadoEm,
        private ?string $atualizadoEm,
    ) {}

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClienteId(): int
    {
        return $this->clienteId;
    }

    public function getPlaca(): Placa
    {
        return $this->placa;
    }

    public function getMarca(): string
    {
        return $this->marca;
    }

    public function getModelo(): string
    {
        return $this->modelo;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function getCor(): ?string
    {
        return $this->cor;
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

    public function setClienteId(int $clienteId): void
    {
        $this->clienteId = $clienteId;
    }

    public function setPlaca(Placa $placa): void
    {
        $this->placa = $placa;
    }

    public function setMarca(string $marca): void
    {
        $this->marca = $marca;
    }

    public function setModelo(string $modelo): void
    {
        $this->modelo = $modelo;
    }

    public function setAno(int $ano): void
    {
        $this->ano = $ano;
    }

    public function setCor(?string $cor): void
    {
        $this->cor = $cor;
    }

    /**
     * Serializa o veículo para array.
     */
    public function paraArray(): array
    {
        return [
            'id'              => $this->id,
            'cliente_id'      => $this->clienteId,
            'placa'           => $this->placa->getValor(),
            'placa_formatada' => $this->placa->getFormatada(),
            'mercosul'        => $this->placa->isMercosul(),
            'marca'           => $this->marca,
            'modelo'          => $this->modelo,
            'ano'             => $this->ano,
            'cor'             => $this->cor,
            'criado_em'       => $this->criadoEm,
            'atualizado_em'   => $this->atualizadoEm,
        ];
    }
}
