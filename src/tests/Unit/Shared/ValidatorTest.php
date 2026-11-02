<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Domain\Shared\Validator;
use Domain\Veiculo\Placa;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    // ==================================================================
    // CPF
    // ==================================================================

    public function testDeveValidarCpfValidoComMascara(): void
    {
        $cpfValido = '111.444.777-35';
        $resultado = Validator::cpf($cpfValido);

        $this->assertSame('111.444.777-35', $resultado);
    }

    public function testDeveValidarCpfValidoSemMascaraEFormatar(): void
    {
        $cpfSemMascara = '11144477735';
        $resultado = Validator::cpf($cpfSemMascara);

        $this->assertSame('111.444.777-35', $resultado);
    }

    public function testDeveNormalizarCpfRemovendoPontosETracos(): void
    {
        $this->assertSame('11144477735', Validator::normalizarCpf('111.444.777-35'));
        $this->assertSame('12345678901', Validator::normalizarCpf('123-456.789/01'));
    }

    public function testDeveLancarExcecaoParaCpfComTamanhoDiferenteDeOnzeDigitos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF inválido: formato incorreto.');

        Validator::cpf('123.456.78-9');
    }

    public function testDeveLancarExcecaoParaCpfComDigitosRepetidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF inválido: sequência de dígitos repetidos não é permitida.');

        Validator::cpf('111.111.111-11');
    }

    public function testDeveLancarExcecaoParaCpfSequenciaConhecidaInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF inválido: sequência numérica não é um CPF válido.');

        Validator::cpf('12345678909');
    }

    public function testDeveLancarExcecaoParaCpfComDigitosVerificadoresIncorretos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF inválido: dígito verificador incorreto.');

        Validator::cpf('111.444.777-99');
    }

    // ==================================================================
    // Nome & Email & Telefone
    // ==================================================================

    public function testDeveValidarNomeValido(): void
    {
        $nome = Validator::nome('João da Silva Costa');
        $this->assertSame('João da Silva Costa', $nome);
    }

    public function testDeveLancarExcecaoParaNomeMuitoCurto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome inválido: deve ter pelo menos 3 caracteres.');

        Validator::nome('Ab');
    }

    public function testDeveLancarExcecaoParaNomeComCaracteresProibidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome inválido: são permitidas apenas letras, espaços e hífens.');

        Validator::nome('João <script>alert(1)</script>');
    }

    public function testDeveValidarEmailValido(): void
    {
        $email = Validator::email('contato@oficina.com.br');
        $this->assertSame('contato@oficina.com.br', $email);
    }

    public function testDeveLancarExcecaoParaEmailInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('E-mail inválido: formato incorreto.');

        Validator::email('email_sem_arroba.com');
    }

    public function testDeveLancarExcecaoParaEmailComCaracteresProibidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('E-mail inválido: contém caracteres não permitidos.');

        Validator::email('"teste;injection"@oficina.com');
    }

    public function testDeveValidarTelefoneValido(): void
    {
        $tel = Validator::telefone('(11) 98765-4321');
        $this->assertSame('(11) 98765-4321', $tel);
    }

    public function testDeveLancarExcecaoParaTelefoneComMenosDigitos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Telefone inválido: informe DDD + número (10 ou 11 dígitos).');

        Validator::telefone('12345');
    }

    // ==================================================================
    // Serviços, Peças e Estoque
    // ==================================================================

    public function testDeveValidarNomeServico(): void
    {
        $this->assertSame('Troca de Velas', Validator::nomeServico('Troca de Velas'));
    }

    public function testDeveLancarExcecaoParaNomeServicoInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome do serviço inválido: deve ter pelo menos 3 caracteres.');

        Validator::nomeServico('Oi');
    }

    public function testDeveValidarNomePeca(): void
    {
        $this->assertSame('Amortecedor Dianteiro', Validator::nomePeca('Amortecedor Dianteiro'));
    }

    public function testDeveLancarExcecaoParaNomePecaInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome da peça inválido: deve ter pelo menos 3 caracteres.');

        Validator::nomePeca('P1');
    }

    public function testDeveValidarValorUnitario(): void
    {
        $this->assertSame(49.90, Validator::valorUnitario('49.90'));
        $this->assertSame(100.0, Validator::valorUnitario(100));
    }

    public function testDeveLancarExcecaoParaValorUnitarioNaoNumericoOuNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $ValidatorUnitario = Validator::valorUnitario(-10.0);
    }

    public function testDeveValidarQuantidadeEstoque(): void
    {
        $this->assertSame(15, Validator::quantidadeEstoque('15'));
        $this->assertSame(0, Validator::quantidadeEstoque(0));
    }

    public function testDeveLancarExcecaoParaQuantidadeEstoqueInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::quantidadeEstoque(-5);
    }

    // ==================================================================
    // Veículos (Placa, Marca, Modelo, Ano, Cor)
    // ==================================================================

    public function testDeveValidarPlaca(): void
    {
        $placa = Validator::placa('ABC1D23');
        $this->assertInstanceOf(Placa::class, $placa);
        $this->assertSame('ABC1D23', $placa->getFormatada());
    }

    public function testDeveValidarMarcaEModeloECor(): void
    {
        $this->assertSame('Toyota', Validator::marcaVeiculo('Toyota'));
        $this->assertSame('Corolla XEi', Validator::modeloVeiculo('Corolla XEi'));
        $this->assertSame('Preto Perolizado', Validator::corVeiculo('Preto Perolizado'));
    }

    public function testDeveLancarExcecaoParaMarcaComMenosDeDoisCaracteres(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Marca inválida: deve ter pelo menos 2 caracteres.');

        Validator::marcaVeiculo('T');
    }

    public function testDeveValidarAnoVeiculo(): void
    {
        $this->assertSame(2022, Validator::anoVeiculo(2022));
    }

    public function testDeveLancarExcecaoParaAnoVeiculoInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ano inválido: deve estar entre 1900 e');

        Validator::anoVeiculo(1850);
    }

    // ==================================================================
    // Genéricos: ID, Preço, Horas, Texto Livre e Max255
    // ==================================================================

    public function testDeveValidarIdPositivo(): void
    {
        $this->assertSame(10, Validator::idPositivo(10));
        $this->assertSame(5, Validator::idPositivo('5'));
    }

    public function testDeveLancarExcecaoParaIdInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::idPositivo(0);
    }

    public function testDeveValidarPrecoETempoEstimadoHoras(): void
    {
        $this->assertSame(150.75, Validator::preco('150.75'));
        $this->assertSame(2.5, Validator::tempoEstimadoHoras(2.5));
    }

    public function testDeveLancarExcecaoParaPrecoNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::preco(-1.0);
    }

    public function testDeveLancarExcecaoParaTempoHorasNegativo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::tempoEstimadoHoras(-0.5);
    }

    public function testDeveValidarTextoLivreETextoMax255(): void
    {
        $this->assertSame('Observação simples e segura', Validator::textoLivre('Observação simples e segura'));
        $this->assertSame('Diagnóstico inicial ok', Validator::textoMax255('Diagnóstico inicial ok'));
        $this->assertSame('123456', Validator::apenasDigitos('12.34-56/abc'));
    }

    public function testDeveLancarExcecaoParaTextoMax255MuitoLongo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('deve ter no máximo 255 caracteres.');

        Validator::textoMax255(str_repeat('a', 256));
    }
}
