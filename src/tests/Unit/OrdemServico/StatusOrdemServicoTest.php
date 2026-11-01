<?php

declare(strict_types=1);

namespace Tests\Unit\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Domain\OrdemServico\StatusOrdemServico;
use PHPUnit\Framework\TestCase;

class StatusOrdemServicoTest extends TestCase
{
    public function testDevePossuirTodosOsStatusObrigatorios(): void
    {
        $this->assertSame('recebida', StatusOrdemServico::RECEBIDA->value);
        $this->assertSame('em_diagnostico', StatusOrdemServico::EM_DIAGNOSTICO->value);
        $this->assertSame('aguardando_aprovacao', StatusOrdemServico::AGUARDANDO_APROVACAO->value);
        $this->assertSame('em_execucao', StatusOrdemServico::EM_EXECUCAO->value);
        $this->assertSame('finalizada', StatusOrdemServico::FINALIZADA->value);
        $this->assertSame('entregue', StatusOrdemServico::ENTREGUE->value);
    }

    public function testDeveRetornarRotuloEDescricaoCorretos(): void
    {
        $this->assertSame('Recebida', StatusOrdemServico::RECEBIDA->rotulo());
        $this->assertSame('Em diagnóstico', StatusOrdemServico::EM_DIAGNOSTICO->rotulo());
        $this->assertSame('Aguardando aprovação', StatusOrdemServico::AGUARDANDO_APROVACAO->rotulo());
        $this->assertSame('Em execução', StatusOrdemServico::EM_EXECUCAO->rotulo());
        $this->assertSame('Finalizada', StatusOrdemServico::FINALIZADA->rotulo());
        $this->assertSame('Entregue', StatusOrdemServico::ENTREGUE->rotulo());

        $this->assertNotEmpty(StatusOrdemServico::RECEBIDA->descricao());
        $this->assertNotEmpty(StatusOrdemServico::ENTREGUE->descricao());
    }

    public function testDeveRetornarOrdemHierarquicaCorreta(): void
    {
        $this->assertSame(1, StatusOrdemServico::RECEBIDA->ordem());
        $this->assertSame(2, StatusOrdemServico::EM_DIAGNOSTICO->ordem());
        $this->assertSame(3, StatusOrdemServico::AGUARDANDO_APROVACAO->ordem());
        $this->assertSame(4, StatusOrdemServico::EM_EXECUCAO->ordem());
        $this->assertSame(5, StatusOrdemServico::FINALIZADA->ordem());
        $this->assertSame(6, StatusOrdemServico::ENTREGUE->ordem());
    }

    public function testDevePermitirAvancosValidosDaMaquinaDeStatus(): void
    {
        // De RECEBIDA para EM_DIAGNOSTICO ou direto para AGUARDANDO_APROVACAO
        $this->assertTrue(StatusOrdemServico::RECEBIDA->podeAvancarPara(StatusOrdemServico::EM_DIAGNOSTICO));
        $this->assertTrue(StatusOrdemServico::RECEBIDA->podeAvancarPara(StatusOrdemServico::AGUARDANDO_APROVACAO));

        // De EM_DIAGNOSTICO para AGUARDANDO_APROVACAO ou EM_EXECUCAO
        $this->assertTrue(StatusOrdemServico::EM_DIAGNOSTICO->podeAvancarPara(StatusOrdemServico::AGUARDANDO_APROVACAO));
        $this->assertTrue(StatusOrdemServico::EM_DIAGNOSTICO->podeAvancarPara(StatusOrdemServico::EM_EXECUCAO));

        // De AGUARDANDO_APROVACAO para EM_EXECUCAO ou voltar para EM_DIAGNOSTICO
        $this->assertTrue(StatusOrdemServico::AGUARDANDO_APROVACAO->podeAvancarPara(StatusOrdemServico::EM_EXECUCAO));
        $this->assertTrue(StatusOrdemServico::AGUARDANDO_APROVACAO->podeAvancarPara(StatusOrdemServico::EM_DIAGNOSTICO));

        // De EM_EXECUCAO para FINALIZADA
        $this->assertTrue(StatusOrdemServico::EM_EXECUCAO->podeAvancarPara(StatusOrdemServico::FINALIZADA));

        // De FINALIZADA para ENTREGUE
        $this->assertTrue(StatusOrdemServico::FINALIZADA->podeAvancarPara(StatusOrdemServico::ENTREGUE));

        // Mesmo status deve retornar true (idempotente)
        $this->assertTrue(StatusOrdemServico::RECEBIDA->podeAvancarPara(StatusOrdemServico::RECEBIDA));
    }

    public function testDeveBloquearAvancosInvalidosDaMaquinaDeStatus(): void
    {
        // Pular etapas não permitidas
        $this->assertFalse(StatusOrdemServico::RECEBIDA->podeAvancarPara(StatusOrdemServico::FINALIZADA));
        $this->assertFalse(StatusOrdemServico::RECEBIDA->podeAvancarPara(StatusOrdemServico::ENTREGUE));

        // Não pode avançar a partir de ENTREGUE (estado final)
        $this->assertFalse(StatusOrdemServico::ENTREGUE->podeAvancarPara(StatusOrdemServico::RECEBIDA));
        $this->assertFalse(StatusOrdemServico::ENTREGUE->podeAvancarPara(StatusOrdemServico::EM_EXECUCAO));
    }

    public function testDeveInstanciarAPartirDeStringAmigavel(): void
    {
        $this->assertSame(StatusOrdemServico::RECEBIDA, StatusOrdemServico::fromValor('recebida'));
        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO, StatusOrdemServico::fromValor('em_diagnostico'));
        $this->assertSame(StatusOrdemServico::EM_DIAGNOSTICO, StatusOrdemServico::fromValor('diagnostico'));
        $this->assertSame(StatusOrdemServico::AGUARDANDO_APROVACAO, StatusOrdemServico::fromValor('aguardando_aprovacao'));
        $this->assertSame(StatusOrdemServico::EM_EXECUCAO, StatusOrdemServico::fromValor('em_execucao'));
        $this->assertSame(StatusOrdemServico::FINALIZADA, StatusOrdemServico::fromValor('finalizada'));
        $this->assertSame(StatusOrdemServico::ENTREGUE, StatusOrdemServico::fromValor('entregue'));
    }

    public function testDeveLancarExcecaoAoFornecerStatusInvalido(): void
    {
        $this->expectException(ValidacaoException::class);
        $this->expectExceptionMessage('Status "status_inexistente" inválido.');

        StatusOrdemServico::fromValor('status_inexistente');
    }
}

