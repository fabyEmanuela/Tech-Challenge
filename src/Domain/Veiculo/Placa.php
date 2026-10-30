<?php

declare(strict_types=1);

namespace Domain\Veiculo;

use InvalidArgumentException;

/**
 * Value Object: Placa de Veículo.
 *
 * Suporta e valida os formatos brasileiros:
 *  - Antigo / Tradicional: 3 letras + 4 números (ex.: ABC-1234 ou ABC1234)
 *  - Mercosul: 3 letras + 1 número + 1 letra + 2 números (ex.: ABC1D23)
 *
 * Garante que a placa seja imutável, válida e normalizada em letras maiúsculas.
 */
final class Placa
{
    private const REGEX_ANTIGA   = '/^[A-Z]{3}[0-9]{4}$/';
    private const REGEX_MERCOSUL = '/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/';

    private string $valor;

    public function __construct(string $valor)
    {
        $placaLimpa = $this->sanitizar($valor);
        $this->validar($placaLimpa);
        $this->valor = $placaLimpa;
    }

    /**
     * Remove espaços e hífens e converte para maiúsculo.
     * Rejeita caracteres especiais inválidos antes da normalização.
     */
    private function sanitizar(string $valor): string
    {
        $valor = strtoupper(trim($valor));

        // Permite apenas letras, números, hífen opcional e espaços internos
        if (!preg_match('/^[A-Z0-9\-\s]+$/', $valor)) {
            throw new InvalidArgumentException(
                'Placa inválida: contém caracteres especiais não permitidos.'
            );
        }

        return preg_replace('/[\-\s]/', '', $valor) ?? '';
    }

    /**
     * Valida o tamanho e os padrões Antigo ou Mercosul.
     *
     * @throws InvalidArgumentException
     */
    private function validar(string $placa): void
    {
        if (strlen($placa) !== 7) {
            throw new InvalidArgumentException(
                'Placa inválida: deve conter exatamente 7 caracteres alfanuméricos.'
            );
        }

        $ehAntiga   = (bool) preg_match(self::REGEX_ANTIGA, $placa);
        $ehMercosul = (bool) preg_match(self::REGEX_MERCOSUL, $placa);

        if (!$ehAntiga && !$ehMercosul) {
            throw new InvalidArgumentException(
                'Placa inválida: deve seguir o padrão tradicional (ex.: ABC-1234) ou Mercosul (ex.: ABC1D23).'
            );
        }
    }

    /**
     * Retorna a placa normalizada sem traço (7 caracteres).
     */
    public function getValor(): string
    {
        return $this->valor;
    }

    /**
     * Retorna a placa formatada para exibição (ABC-1234 para padrão antigo ou ABC1D23 para Mercosul).
     */
    public function getFormatada(): string
    {
        if ($this->isAntiga()) {
            return substr($this->valor, 0, 3) . '-' . substr($this->valor, 3);
        }

        return $this->valor;
    }

    public function isMercosul(): bool
    {
        return (bool) preg_match(self::REGEX_MERCOSUL, $this->valor);
    }

    public function isAntiga(): bool
    {
        return (bool) preg_match(self::REGEX_ANTIGA, $this->valor);
    }

    public function equals(Placa $outra): bool
    {
        return $this->valor === $outra->getValor();
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}

