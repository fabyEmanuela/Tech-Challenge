<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Exceptions\ValidacaoException;
use Domain\ValueObjects\Placa;

final class Veiculo
{
    public const ANO_MINIMO = 1900;

    private function __construct(
        private ?int $id,
        private int $clienteId,
        private Placa $placa,
        private string $marca,
        private string $modelo,
        private int $ano,
        private ?string $cor = null,
        private ?string $criadoEm = null,
        private ?string $atualizadoEm = null
    ) {
        $this->validar();
    }

    /** Novo veículo . */
    public static function criar(
        int $clienteId,
        Placa $placa,
        string $marca,
        string $modelo,
        int $ano,
        ?string $cor = null
    ): self {
        return new self(
            id:           null,
            clienteId:    $clienteId,
            placa:        $placa,
            marca:        trim($marca),
            modelo:       trim($modelo),
            ano:          $ano,
            cor:          $cor !== null ? trim($cor) : null,
            criadoEm:     null,
            atualizadoEm: null
        );
    }

    /** Recria a entidade a partir do banco. */
    public static function reconstituir(
        int $id,
        int $clienteId,
        Placa $placa,
        string $marca,
        string $modelo,
        int $ano,
        ?string $cor = null,
        ?string $criadoEm = null,
        ?string $atualizadoEm = null
    ): self {
        return new self(
            id:           $id,
            clienteId:    $clienteId,
            placa:        $placa,
            marca:        $marca,
            modelo:       $modelo,
            ano:          $ano,
            cor:          $cor,
            criadoEm:     $criadoEm,
            atualizadoEm: $atualizadoEm
        );
    }

    public function atualizar(
        int $clienteId,
        Placa $placa,
        string $marca,
        string $modelo,
        int $ano,
        ?string $cor = null
    ): void {
        $this->clienteId = $clienteId;
        $this->placa     = $placa;
        $this->marca     = trim($marca);
        $this->modelo    = trim($modelo);
        $this->ano       = $ano;
        $this->cor       = $cor !== null ? trim($cor) : $this->cor;
        $this->validar();
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new ValidacaoException('O veículo já possui identificador.');
        }
        $this->id = $id;
    }

    private function validar(): void
    {
        if ($this->clienteId <= 0) {
            throw new ValidacaoException('O veículo deve estar vinculado a um cliente válido.');
        }
        if ($this->marca === '' || mb_strlen($this->marca) > 60) {
            throw new ValidacaoException('Marca é obrigatória e deve ter no máximo 60 caracteres.');
        }
        if ($this->modelo === '' || mb_strlen($this->modelo) > 80) {
            throw new ValidacaoException('Modelo é obrigatório e deve ter no máximo 80 caracteres.');
        }

        $anoMaximo = (int) date('Y') + 1; // permite ano-modelo seguinte
        if ($this->ano < self::ANO_MINIMO || $this->ano > $anoMaximo) {
            throw new ValidacaoException(
                sprintf('Ano deve estar entre %d e %d.', self::ANO_MINIMO, $anoMaximo)
            );
        }

        if ($this->cor !== null && mb_strlen($this->cor) > 30) {
            throw new ValidacaoException('Cor deve ter no máximo 30 caracteres.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function placa(): Placa
    {
        return $this->placa;
    }

    public function marca(): string
    {
        return $this->marca;
    }

    public function modelo(): string
    {
        return $this->modelo;
    }

    public function ano(): int
    {
        return $this->ano;
    }

    public function cor(): ?string
    {
        return $this->cor;
    }

    public function criadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function atualizadoEm(): ?string
    {
        return $this->atualizadoEm;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'cliente_id'      => $this->clienteId,
            'placa'           => $this->placa->valor(),
            'placa_formatada' => $this->placa->formatada(),
            'mercosul'        => $this->placa->ehMercosul(),
            'marca'           => $this->marca,
            'modelo'          => $this->modelo,
            'ano'             => $this->ano,
            'cor'             => $this->cor,
            'criado_em'       => $this->criadoEm,
            'atualizado_em'   => $this->atualizadoEm,
        ];
    }
}
