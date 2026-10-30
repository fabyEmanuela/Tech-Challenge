<?php

namespace Tests\Unit\Veiculo;

use Domain\Shared\Validator;
use Domain\Veiculo\Placa;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PlacaTest extends TestCase
{
    public function testDeveValidarPlacaAntigaComTraco(): void
    {
        $placa = Validator::placa('abc-1234');

        $this->assertInstanceOf(Placa::class, $placa);
        $this->assertSame('ABC1234', $placa->getValor());
        $this->assertSame('ABC-1234', $placa->getFormatada());
        $this->assertTrue($placa->isAntiga());
        $this->assertFalse($placa->isMercosul());
    }

    public function testDeveValidarPlacaMercosul(): void
    {
        $placa = Validator::placa('abc1d23');

        $this->assertSame('ABC1D23', $placa->getValor());
        $this->assertSame('ABC1D23', $placa->getFormatada());
        $this->assertTrue($placa->isMercosul());
        $this->assertFalse($placa->isAntiga());
        $this->assertSame('ABC1D23', (string) $placa);
    }

    public function testDeveCompararIgualdadeEntrePlacas(): void
    {
        $placa1 = new Placa('abc-1234');
        $placa2 = new Placa('ABC1234');
        $placa3 = new Placa('ABC1D23');

        $this->assertTrue($placa1->equals($placa2));
        $this->assertFalse($placa1->equals($placa3));
    }

    public function testDeveLancarExcecaoParaPlacaComTamanhoInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida: deve conter exatamente 7 caracteres alfanuméricos.');

        Validator::placa('ABC12');
    }

    public function testDeveLancarExcecaoParaPlacaComFormatoInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida: deve seguir o padrão tradicional');

        Validator::placa('1234ABC');
    }

    public function testDeveLancarExcecaoParaPlacaComCaracteresEspeciais(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida: contém caracteres especiais não permitidos.');

        Validator::placa('ABC@1234');
    }
}
