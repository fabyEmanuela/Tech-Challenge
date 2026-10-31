<?php

namespace Tests\Unit\Veiculo;

use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;
use PHPUnit\Framework\TestCase;

class VeiculoTest extends TestCase
{
    public function testDeveInstanciarVeiculoEConverterParaArray(): void
    {
        $placa = new Placa('ABC1D23');

        $veiculo = new Veiculo(
            id:           1,
            clienteId:    10,
            placa:        $placa,
            marca:        'Volkswagen',
            modelo:       'Gol 1.0',
            ano:          2022,
            cor:          'Prata',
            criadoEm:     '2026-10-29 10:00:00',
            atualizadoEm: '2026-10-29 10:00:00',
        );

        $this->assertSame(1, $veiculo->getId());
        $this->assertSame(10, $veiculo->getClienteId());
        $this->assertSame('ABC1D23', $veiculo->getPlaca()->getValor());
        $this->assertSame('Volkswagen', $veiculo->getMarca());
        $this->assertSame('Gol 1.0', $veiculo->getModelo());
        $this->assertSame(2022, $veiculo->getAno());
        $this->assertSame('Prata', $veiculo->getCor());
        $this->assertSame('2026-10-29 10:00:00', $veiculo->getCriadoEm());
        $this->assertSame('2026-10-29 10:00:00', $veiculo->getAtualizadoEm());

        $array = $veiculo->paraArray();

        $this->assertSame(1, $array['id']);
        $this->assertSame(10, $array['cliente_id']);
        $this->assertSame('ABC1D23', $array['placa']);
        $this->assertSame('ABC1D23', $array['placa_formatada']);
        $this->assertTrue($array['mercosul']);
        $this->assertSame('Volkswagen', $array['marca']);
        $this->assertSame('Gol 1.0', $array['modelo']);
        $this->assertSame(2022, $array['ano']);
        $this->assertSame('Prata', $array['cor']);
    }

    public function testDeveAlterarPropriedadesComSetters(): void
    {
        $veiculo = new Veiculo(1, 10, new Placa('ABC1D23'), 'Volkswagen', 'Gol 1.0', 2020, 'Branco', null, null);
        $novaPlaca = new Placa('XYZ-9876');

        $veiculo->setClienteId(25);
        $veiculo->setPlaca($novaPlaca);
        $veiculo->setMarca('Chevrolet');
        $veiculo->setModelo('Onix 1.0 Turbo');
        $veiculo->setAno(2023);
        $veiculo->setCor(null);

        $this->assertSame(25, $veiculo->getClienteId());
        $this->assertSame('XYZ9876', $veiculo->getPlaca()->getValor());
        $this->assertSame('XYZ-9876', $veiculo->getPlaca()->getFormatada());
        $this->assertFalse($veiculo->getPlaca()->isMercosul());
        $this->assertSame('Chevrolet', $veiculo->getMarca());
        $this->assertSame('Onix 1.0 Turbo', $veiculo->getModelo());
        $this->assertSame(2023, $veiculo->getAno());
        $this->assertNull($veiculo->getCor());
    }
}
