<?php

declare(strict_types=1);

namespace Infrastructure\Email;

use Application\OrdemServico\NotificadorOrdemServicoInterface;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use PHPMailer\PHPMailer\PHPMailer;

class NotificadorOrdemServicoEmail implements NotificadorOrdemServicoInterface
{
    public function notificar(OrdemServico $os, ?StatusOrdemServico $anterior): void
    {
        if ($os->status() === $anterior && !$os->inicioPendentePorEstoque()) {
            return;
        }

        $mensagem = $os->inicioPendentePorEstoque()
            ? 'Seu orçamento foi aprovado. O início do serviço aguarda a chegada das peças necessárias. Avisaremos quando o trabalho começar.'
            : match ($os->status()) {
            StatusOrdemServico::EM_DIAGNOSTICO => 'Seu veículo está em diagnóstico pela nossa equipe.',
            StatusOrdemServico::AGUARDANDO_APROVACAO => 'Seu orçamento está disponível e aguarda sua aprovação.',
            StatusOrdemServico::EM_EXECUCAO => 'Orçamento aprovado! Estamos iniciando o trabalho no seu veículo.',
            StatusOrdemServico::FINALIZADA => 'O trabalho foi finalizado. Entre em contato com a oficina para combinar a retirada.',
            default => null,
        };
        if ($mensagem === null) {
            return;
        }

        // O envio ocorre depois da gravação. Uma falha SMTP não desfaz a alteração da OS.
        try {
            $cliente = $os->toArray()['cliente'] ?? [];
            $mail    = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host        = getenv('MAIL_HOST') ?: 'mailpit';
            $mail->Port        = (int) (getenv('MAIL_PORT') ?: 1025);
            $mail->SMTPAuth    = false;
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure  = '';
            $mail->Timeout     = 10;
            $mail->CharSet     = 'UTF-8';

            $mail->setFrom('nao-responda@oficina.com.br', 'Oficina');
            $mail->addAddress($cliente['email'] ?? '', $cliente['nome'] ?? 'Cliente');
            $mail->Subject = $os->inicioPendentePorEstoque()
                ? "OS {$os->numeroOs()} - Orçamento aprovado, aguardando peças"
                : "OS {$os->numeroOs()} - {$os->status()->rotulo()}";
            $mail->Body    = "Olá, " . ($cliente['nome'] ?? 'Cliente') . "!\n\n{$mensagem}\n\nOrdem de serviço: {$os->numeroOs()}";

            if ($os->status() === StatusOrdemServico::AGUARDANDO_APROVACAO) {
                foreach ($os->servicos() as $item) {
                    $mail->Body .= "\nServiço: {$item->nome()} - R$ " . number_format($item->subtotal(), 2, ',', '.');
                }

                foreach ($os->pecas() as $item) {
                    $mail->Body .= "\nPeça: {$item->descricao()} - R$ " . number_format($item->subtotal(), 2, ',', '.');
                }

                $mail->Body .= "\nTotal: R$ " . number_format($os->valorTotal(), 2, ',', '.');
                $mail->Body .= "\nTempo estimado de serviço: " . number_format($os->tempoEstimadoTotalHoras(), 2, ':', '.') . ' horas';
                $mail->Body .= "\nEntre em contato com a oficina para aprovar o orçamento.";
            }
            if ($os->inicioPendentePorEstoque()) {
                // Os detalhes de reposição são exclusivos da equipe da oficina.
                $avisoOficina = clone $mail;
                $avisoOficina->clearAllRecipients();
                $avisoOficina->addAddress(getenv('MAIL_OFICINA') ?: 'admin@oficina.com', 'Oficina');
                $avisoOficina->Subject = "OS {$os->numeroOs()} - Reposição de peças necessária";
                $avisoOficina->Body = "Orçamento aprovado. O início do serviço está pendente por falta de peças.\n\nOrdem de serviço: {$os->numeroOs()}\n\n"
                    . implode("\n", $os->avisosEstoque());
                try {
                    $avisoOficina->send();
                } catch (\Throwable $exception) {
                    error_log("Falha no email de estoque da OS {$os->numeroOs()}: {$exception->getMessage()}");
                }
            }
            $mail->send();
        } catch (\Throwable $exception) {
            error_log("Falha no email da OS {$os->numeroOs()}: {$exception->getMessage()}");
        }
    }
}
