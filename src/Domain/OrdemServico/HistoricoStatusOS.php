<?php

declare(strict_types=1);

namespace Domain\OrdemServico;

final class HistoricoStatusOS
{
    public function __construct(
        private ?int $id,
        private ?string $statusDe,
        private string $statusPara,
        private ?string $observacao = null,
        private ?string $criadoEm = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function statusDe(): ?string
    {
        return $this->statusDe;
    }

    public function statusPara(): string
    {
        return $this->statusPara;
    }

    public function observacao(): ?string
    {
        return $this->observacao;
    }

    public function criadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'status_de'   => $this->statusDe,
            'status_para' => $this->statusPara,
            'observacao'  => $this->observacao,
            'criado_em'   => $this->criadoEm,
        ];
    }
}
