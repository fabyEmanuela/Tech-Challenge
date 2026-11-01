<?php

declare(strict_types=1);

namespace Domain\OrdemServico;

use Domain\Exceptions\ValidacaoException;

enum StatusOrdemServico: string
{
    case RECEBIDA            = 'recebida';
    case EM_DIAGNOSTICO      = 'em_diagnostico';
    case AGUARDANDO_APROVACAO = 'aguardando_aprovacao';
    case EM_EXECUCAO         = 'em_execucao';
    case FINALIZADA          = 'finalizada';
    case ENTREGUE            = 'entregue';

    public function rotulo(): string
    {
        return match ($this) {
            self::RECEBIDA             => 'Recebida',
            self::EM_DIAGNOSTICO       => 'Em diagnóstico',
            self::AGUARDANDO_APROVACAO => 'Aguardando aprovação',
            self::EM_EXECUCAO          => 'Em execução',
            self::FINALIZADA           => 'Finalizada',
            self::ENTREGUE             => 'Entregue',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::RECEBIDA             => 'Ordem de serviço aberta e veículo recebido na oficina.',
            self::EM_DIAGNOSTICO       => 'Veículo em análise técnica pela equipe mecânica para identificação de serviços e peças.',
            self::AGUARDANDO_APROVACAO => 'Orçamento gerado e enviado ao cliente, aguardando aprovação para início dos reparos.',
            self::EM_EXECUCAO          => 'Orçamento aprovado. Serviços e troca de peças estão em execução pela equipe.',
            self::FINALIZADA           => 'Serviços concluídos e veículo pronto para retirada.',
            self::ENTREGUE             => 'Veículo entregue ao cliente e ordem de serviço concluída com sucesso.',
        };
    }

    public function ordem(): int
    {
        return match ($this) {
            self::RECEBIDA             => 1,
            self::EM_DIAGNOSTICO       => 2,
            self::AGUARDANDO_APROVACAO => 3,
            self::EM_EXECUCAO          => 4,
            self::FINALIZADA           => 5,
            self::ENTREGUE             => 6,
        };
    }

    /**
     * Valida se a transição para um novo status é permitida no ciclo de vida da OS.
     */
    public function podeAvancarPara(self $novoStatus): bool
    {
        if ($this === $novoStatus) {
            return true;
        }

        return match ($this) {
            self::RECEBIDA             => in_array($novoStatus, [self::EM_DIAGNOSTICO, self::AGUARDANDO_APROVACAO], true),
            self::EM_DIAGNOSTICO       => in_array($novoStatus, [self::AGUARDANDO_APROVACAO, self::EM_EXECUCAO], true),
            self::AGUARDANDO_APROVACAO => in_array($novoStatus, [self::EM_EXECUCAO, self::EM_DIAGNOSTICO], true),
            self::EM_EXECUCAO          => in_array($novoStatus, [self::FINALIZADA, self::EM_DIAGNOSTICO], true),
            self::FINALIZADA           => $novoStatus === self::ENTREGUE,
            self::ENTREGUE             => false, // Estado final
        };
    }

    public static function fromValor(string $valor): self
    {
        $limpo = mb_strtolower(trim($valor));
        $limpo = str_replace([' ', '-'], '_', $limpo);

        return match ($limpo) {
            'recebida'                                 => self::RECEBIDA,
            'em_diagnostico', 'diagnostico'            => self::EM_DIAGNOSTICO,
            'aguardando_aprovacao', 'aprovacao'        => self::AGUARDANDO_APROVACAO,
            'em_execucao', 'execucao'                  => self::EM_EXECUCAO,
            'finalizada', 'concluida'                  => self::FINALIZADA,
            'entregue'                                 => self::ENTREGUE,
            default => throw new ValidacaoException(
                sprintf('Status "%s" inválido. Status permitidos: recebida, em_diagnostico, aguardando_aprovacao, em_execucao, finalizada, entregue.', $valor)
            ),
        };
    }
}
