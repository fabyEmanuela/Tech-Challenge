<?php

namespace Domain\Shared;

use Domain\Veiculo\Placa;
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

    private const ANO_MINIMO_VEICULO = 1900;

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
     * Valida o nome de uma pessoa:
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

    /**
     * Valida o nome de um serviço:
     *  - obrigatório
     *  - mínimo 3 caracteres
     *  - máximo 100 caracteres
     *  - permite letras, números, espaços e hífens
     *
     * @throws InvalidArgumentException
     */
    public static function nomeServico(string $nome): string
    {
        $nome = trim($nome);

        if (mb_strlen($nome) < 3) {
            throw new InvalidArgumentException(
                'Nome do serviço inválido: deve ter pelo menos 3 caracteres.'
            );
        }

        if (mb_strlen($nome) > 100) {
            throw new InvalidArgumentException(
                'Nome do serviço inválido: máximo de 100 caracteres.'
            );
        }

        return self::textoLivre($nome, 'Nome do serviço');
    }

    // ------------------------------------------------------------------
    // Peças e Insumos
    // ------------------------------------------------------------------

    /**
     * Valida o nome de uma peça:
     *  - obrigatório
     *  - mínimo 3 caracteres
     *  - máximo 100 caracteres
     *  - sem caracteres especiais proibidos
     *
     * @throws InvalidArgumentException
     */
    public static function nomePeca(string $nome): string
    {
        $nome = trim($nome);

        if (mb_strlen($nome) < 3) {
            throw new InvalidArgumentException(
                'Nome da peça inválido: deve ter pelo menos 3 caracteres.'
            );
        }

        if (mb_strlen($nome) > 100) {
            throw new InvalidArgumentException(
                'Nome da peça inválido: máximo de 100 caracteres.'
            );
        }

        return self::textoLivre($nome, 'Nome da peça');
    }

    /**
     * Valida o valor unitário de uma peça:
     *  - numérico e não negativo (>= 0)
     *
     * @throws InvalidArgumentException
     */
    public static function valorUnitario(mixed $valor): float
    {
        if (!is_numeric($valor)) {
            throw new InvalidArgumentException(
                'Valor unitário inválido: deve ser um valor numérico.'
            );
        }

        $valorFloat = (float) $valor;

        if ($valorFloat < 0) {
            throw new InvalidArgumentException(
                'Valor unitário inválido: o valor não pode ser negativo.'
            );
        }

        return round($valorFloat, 2);
    }

    /**
     * Valida uma quantidade inteira de estoque (saldo ou estoque mínimo):
     *  - numérico inteiro e não negativo (>= 0)
     *
     * @throws InvalidArgumentException
     */
    public static function quantidadeEstoque(mixed $quantidade, string $nomeCampo = 'Quantidade'): int
    {
        if (!is_numeric($quantidade) || (int) $quantidade < 0) {
            throw new InvalidArgumentException(
                "$nomeCampo inválida: deve ser um número inteiro não negativo."
            );
        }

        return (int) $quantidade;
    }

    // ------------------------------------------------------------------
    // Veículos
    // ------------------------------------------------------------------

    /**
     * Valida e retorna o Value Object Placa (padrão tradicional ABC-1234 ou Mercosul ABC1D23).
     *
     * @throws InvalidArgumentException
     */
    public static function placa(string $placa): Placa
    {
        return new Placa($placa);
    }

    /**
     * Valida a marca de um veículo:
     *  - obrigatório
     *  - mínimo 2 caracteres
     *  - máximo 50 caracteres (compatível com VARCHAR(50) no banco)
     *  - sem caracteres especiais proibidos
     *
     * @throws InvalidArgumentException
     */
    public static function marcaVeiculo(string $marca): string
    {
        $marca = trim($marca);

        if (mb_strlen($marca) < 2) {
            throw new InvalidArgumentException(
                'Marca inválida: deve ter pelo menos 2 caracteres.'
            );
        }

        if (mb_strlen($marca) > 50) {
            throw new InvalidArgumentException(
                'Marca inválida: máximo de 50 caracteres.'
            );
        }

        return self::textoLivre($marca, 'Marca');
    }

    /**
     * Valida o modelo de um veículo:
     *  - obrigatório
     *  - mínimo 1 caractere (ex.: Ka, Gol 1.0, Corolla 2.0)
     *  - máximo 50 caracteres (compatível com VARCHAR(50) no banco)
     *  - sem caracteres especiais proibidos
     *
     * @throws InvalidArgumentException
     */
    public static function modeloVeiculo(string $modelo): string
    {
        $modelo = trim($modelo);

        if (mb_strlen($modelo) < 1) {
            throw new InvalidArgumentException(
                'Modelo inválido: deve ter pelo menos 1 caractere.'
            );
        }

        if (mb_strlen($modelo) > 50) {
            throw new InvalidArgumentException(
                'Modelo inválido: máximo de 50 caracteres.'
            );
        }

        return self::textoLivre($modelo, 'Modelo');
    }

    /**
     * Valida o ano do veículo:
     *  - inteiro válido entre 1900 e o próximo ano-modelo (ano atual + 1)
     *
     * @throws InvalidArgumentException
     */
    public static function anoVeiculo(mixed $ano): int
    {
        if (is_bool($ano) || filter_var($ano, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException(
                'Ano inválido: deve ser um número inteiro.'
            );
        }

        $anoInt    = (int) $ano;
        $anoMaximo = (int) date('Y') + 1;

        if ($anoInt < self::ANO_MINIMO_VEICULO || $anoInt > $anoMaximo) {
            throw new InvalidArgumentException(
                sprintf('Ano inválido: deve estar entre %d e %d.', self::ANO_MINIMO_VEICULO, $anoMaximo)
            );
        }

        return $anoInt;
    }

    /**
     * Valida a cor de um veículo:
     *  - mínimo 2 caracteres
     *  - máximo 30 caracteres (compatível com VARCHAR(30) no banco)
     *  - sem caracteres especiais proibidos
     *
     * @throws InvalidArgumentException
     */
    public static function corVeiculo(string $cor): string
    {
        $cor = trim($cor);

        if (mb_strlen($cor) < 2) {
            throw new InvalidArgumentException(
                'Cor inválida: deve ter pelo menos 2 caracteres.'
            );
        }

        if (mb_strlen($cor) > 30) {
            throw new InvalidArgumentException(
                'Cor inválida: máximo de 30 caracteres.'
            );
        }

        return self::textoLivre($cor, 'Cor');
    }

    /**
     * Valida um identificador inteiro positivo (> 0).
     *
     * @throws InvalidArgumentException
     */
    public static function idPositivo(mixed $id, string $nomeCampo = 'ID'): int
    {
        if (is_bool($id) || filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
            throw new InvalidArgumentException(
                "$nomeCampo inválido: deve ser um número inteiro positivo."
            );
        }

        return (int) $id;
    }

    // ------------------------------------------------------------------
    // Preço
    // ------------------------------------------------------------------

    /**
     * Valida o preço:
     *  - obrigatório / numérico
     *  - não pode ser negativo (< 0)
     *
     * @throws InvalidArgumentException
     */
    public static function preco(mixed $preco): float
    {
        if (!is_numeric($preco)) {
            throw new InvalidArgumentException(
                'Preço inválido: deve ser um valor numérico.'
            );
        }

        $precoFloat = (float) $preco;

        if ($precoFloat < 0) {
            throw new InvalidArgumentException(
                'Preço inválido: o valor não pode ser negativo.'
            );
        }

        return round($precoFloat, 2);
    }

    // ------------------------------------------------------------------
    // Tempo Estimado em Horas
    // ------------------------------------------------------------------

    /**
     * Valida o tempo estimado em horas de um serviço:
     *  - numérico
     *  - não pode ser negativo (< 0)
     *
     * @throws InvalidArgumentException
     */
    public static function tempoEstimadoHoras(mixed $tempo): float
    {
        if (!is_numeric($tempo)) {
            throw new InvalidArgumentException(
                'Tempo estimado inválido: deve ser um valor numérico.'
            );
        }

        $tempoFloat = (float) $tempo;

        if ($tempoFloat < 0) {
            throw new InvalidArgumentException(
                'Tempo estimado inválido: o valor não pode ser negativo.'
            );
        }

        return round($tempoFloat, 2);
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

    /**
     * Valida campos de texto livre com limite padrão de 255 caracteres (ex.: descrições e observações).
     * Remove espaços nas extremidades, valida o tamanho máximo e bloqueia caracteres proibidos.
     *
     * @throws InvalidArgumentException
     */
    public static function textoMax255(string $valor, string $nomeCampo = 'Campo'): string
    {
        $valor = trim($valor);

        if (mb_strlen($valor) > 255) {
            throw new InvalidArgumentException(
                sprintf('O campo "%s" deve ter no máximo 255 caracteres.', $nomeCampo)
            );
        }

        return self::textoLivre($valor, $nomeCampo);
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
