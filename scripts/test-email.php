<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../vendor/autoload.php';

// Execute este teste no container PHP. O Mailpit captura a mensagem localmente.
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = getenv('MAIL_HOST') ?: 'mailpit';
    $mail->Port = (int) (getenv('MAIL_PORT') ?: 1025);
    $mail->SMTPAuth = false;
    $mail->SMTPAutoTLS = false;
    $mail->SMTPSecure = '';
    $mail->Timeout = 10;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('oficina@example.test', 'Oficina');
    $mail->addAddress('cliente@example.test', 'Cliente de teste');
    $mail->Subject = 'Teste de envio - Oficina';
    $mail->Body = 'Olá! Este é um email de teste enviado com PHPMailer e capturado pelo Mailpit.';
    $mail->send();

    echo "Email enviado! Confira em http://localhost:8025\n";
} catch (\Throwable $exception) {
    fwrite(STDERR, "Falha ao enviar email: {$exception->getMessage()}\n");
    exit(1);
}
