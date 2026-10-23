<?php

namespace Domain\Shared;

use InvalidArgumentException;

/**
 * Classe utilitária de validações de domínio.
 *
 * Todas as regras de negócio relativas ao formato dos dados ficam aqui,
 * garantindo que a lógica de validação seja reutilizável em qualquer
 * Use Case sem duplicação de código.
 */
class Validator
{
    // ------------------------------------------------------------------
    // Caracteres especiais proibidos em campos de texto livre
    // ------------------------------------------------------------------

    /**
     * Caracteres que representam risco de injeção, XSS ou dados malformados.
     * PDO prepared statements já protegem o SQL, mas barramos na entrada
     * para manter os dados limpos na camada de domínio.
     */
    private const CHARS_PROIBIDOS = '/[<>\'"`;()\[\]{}\\\\?\/|!@#\$%\^&\*=\+]/u';

    // ------------------------------------------------------------------
    // CPF
    // ------------------------------------------------------------------

    /**
     * Valida um CPF completo:
     *  - apenas dígitos (com ou sem máscara 000.000.000-00)
     *  - exatamente 11 dígitos
     *  - não pode ser sequência de dígitos iguais (111.111.111-11 etc.)
     *  - não pode ser sequência crescente conhecida (123.456.789-09)
     *  - dígitos verificadores corretos (algoritmo oficial da Receita Federal)
     *
     * @throws InvalidArgumentException
     */
    public static function cpf(string $cpf): string
    {
        // Remove máscara
        $apenas_digitos = preg_replace('/\D/', '', $cpf);

        // Deve ter exatamente 11 dígitos
        if (strlen($apenas_digitos) !== 11) {
            throw new InvalidArgumentException(
                'CPF inválido: deve conter exatamente 11 dígitos numéricos.'
            );
        }

        // Não pode conter letras (double-check após strip de não-dígitos)
        if (!ctype_digit($apenas_digitos)) {
            throw new InvalidArgumentException(
                'CPF inválido: não deve conter letras.'
            );
        }

        // Não pode ser todos os dígitos iguais
        if (preg_match('/^(\d)\1{10}$/', $apenas_digitos)) {
            throw new InvalidArgumentException(
                'CPF inválido: sequência de dígitos repetidos não é permitida.'
            );
        }

        // Não pode ser a sequência crescente 12345678909
        $sequenciasInvalidas = [
            '12345678909',
            '01234567890',
        ];
        if (in_array($apenas_digitos, $sequenciasInvalidas, true)) {
            throw new InvalidArgumentException(
                'CPF inválido: sequência numérica não é um CPF válido.'
            );
        }

        // Validação dos dígitos verificadores (algoritmo oficial)
        self::validarDigitosCpf($apenas_digitos);

        // Retorna no formato normalizado 000.000.000-00
        return substr($apenas_digitos, 0, 3) . '.'
             . substr($apenas_digitos, 3, 3) . '.'
             . substr($apenas_digitos, 6, 3) . '-'
             . substr($apenas_digitos, 9, 2);
    }

    /**
     * Calcula e confere os dois dígitos verificadores do CPF.
     *
     * @throws InvalidArgumentException
     */
    private static function validarDigitosCpf(string $cpf): void
    {
        // Primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int) $cpf[$i] * (10 - $i);
        }
        $resto     = $soma % 11;
        $digito1   = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[9] !== $digito1) {
            throw new InvalidArgumentException('CPF inválido: dígito verificador incorreto.');
        }

        // Segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $cpf[$i] * (11 - $i);
        }
        $resto   = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[10] !== $digito2) {
            throw new InvalidArgumentException('CPF inválido: dígito verificador incorreto.');
        }
    }

    // ------------------------------------------------------------------
    // E-mail
    // ------------------------------------------------------------------

    /**
     * Valida o formato do e-mail usando o filtro nativo do PHP,
     * além de verificar comprimento máximo e ausência de caracteres suspeitos.
     *
     * @throws InvalidArgumentException
     */
    public static function email(string $email): string
    {
        $email = trim($email);

        if (strlen($email) > 150) {
            throw new InvalidArgumentException(
                'E-mail inválido: máximo de 150 caracteres.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'E-mail inválido: formato incorreto.'
            );
        }

        // Verifica caracteres proibidos (ex.: < > ; `)
        if (preg_match('/[<>\'";`\\\\]/u', $email)) {
            throw new InvalidArgumentException(
                'E-mail inválido: contém caracteres não permitidos.'
            );
        }

        return strtolower($email);
    }

    // ------------------------------------------------------------------
    // Nome
    // ------------------------------------------------------------------

    /**
     * Valida o nome:
     *  - obrigatório
     *  - mínimo 3 caracteres (letras)
     *  - máximo 100 caracteres
     *  - permite letras (incluindo acentuadas), espaços e hífens
     *  - não permite números nem caracteres especiais
     *
     * @throws InvalidArgumentException
     */
    public static function nome(string $nome): string
    {
        $nome = trim($nome);

        if (strlen($nome) < 3) {
            throw new InvalidArgumentException(
                'Nome inválido: deve ter pelo menos 3 caracteres.'
            );
        }

        if (strlen($nome) > 100) {
            throw new InvalidArgumentException(
                'Nome inválido: máximo de 100 caracteres.'
            );
        }

        // Permite somente letras (Unicode), espaços e hífens
        if (!preg_match('/^[\p{L}\s\-]+$/u', $nome)) {
            throw new InvalidArgumentException(
                'Nome inválido: são permitidas apenas letras, espaços e hífens. '
                . 'Números e caracteres especiais não são aceitos.'
            );
        }

        return $nome;
    }

    // ------------------------------------------------------------------
    // Telefone
    // ------------------------------------------------------------------

    /**
     * Valida telefone brasileiro (com ou sem máscara).
     * Aceita 10 ou 11 dígitos (com DDD).
     * Formato aceito: (99) 9999-9999 ou (99) 99999-9999
     *
     * @throws InvalidArgumentException
     */
    public static function telefone(string $telefone): string
    {
        $apenas_digitos = preg_replace('/\D/', '', $telefone);
        $tam = strlen($apenas_digitos);

        if ($tam < 10 || $tam > 11) {
            throw new InvalidArgumentException(
                'Telefone inválido: informe DDD + número (10 ou 11 dígitos).'
            );
        }

        return $telefone;
    }

    // ------------------------------------------------------------------
    // Texto livre genérico (campos sem regra específica)
    // ------------------------------------------------------------------

    /**
     * Rejeita caracteres que representam risco em campos de texto livre:
     * < > ' " ` ; ( ) [ ] { } \ ? / | ! @ # $ % ^ & * = +
     *
     * @throws InvalidArgumentException
     */
    public static function textoLivre(string $valor, string $nomeCampo = 'Campo'): string
    {
        if (preg_match(self::CHARS_PROIBIDOS, $valor)) {
            throw new InvalidArgumentException(
                "$nomeCampo contém caracteres não permitidos "
                . "(evite: < > ' \" ; ` ? / \\ | ( ) [ ] { } @ # \$ % ^ & * = +)."
            );
        }

        return $valor;
    }

    // ------------------------------------------------------------------
    // Helpers públicos
    // ------------------------------------------------------------------

    /**
     * Retorna apenas os dígitos de uma string (útil para normalizar CPF/telefone).
     */
    public static function apenasDigitos(string $valor): string
    {
        return preg_replace('/\D/', '', $valor);
    }
}
