<?php
declare(strict_types=1);

// Arquivo: app/Services/MailService.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Services;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

final class MailService
{
    public static function enviarResetSenha(string $email, string $nome, string $link): bool
    {
        // ── INÍCIO CORREÇÃO QA ──
        $h = self::h(...);
        $body = '<p>Ola, ' . $h($nome) . '.</p>'
            . '<p>Clique no link abaixo para redefinir sua senha.</p>'
            . '<p><a href="' . $h($link) . '">' . $h($link) . '</a></p>'
            . '<p>Link valido por 60 minutos. Se nao solicitou, ignore este e-mail.</p>';

        return self::send(
            $email,
            $nome,
            'Redefinição de senha — Lab Relator',
            $body
        );
        // ── FIM CORREÇÃO QA ──
    }

    /** @param array<string, mixed> $ocorrencia */
    public static function enviarNovaOcorrencia(string $emailGestor, array $ocorrencia): bool
    {
        // ── INÍCIO CORREÇÃO QA ──
        $h = self::h(...);
        $id = (int)($ocorrencia['id'] ?? 0);
        $professor = $h($ocorrencia['professor_nome'] ?? 'Professor nao informado');
        $laboratorio = $h($ocorrencia['laboratorio_nome'] ?? 'Laboratorio nao informado');
        $tipo = $h($ocorrencia['tipo_problema_desc'] ?? $ocorrencia['tipo_problema_nome'] ?? 'Tipo nao informado');
        $descricao = nl2br($h($ocorrencia['descricao'] ?? ''), false);

        $body = '<p>Nova ocorrencia #' . $id . ' registrada por ' . $professor . '.</p>'
            . '<p><strong>Laboratorio:</strong> ' . $laboratorio . ' | <strong>Tipo:</strong> ' . $tipo . '</p>'
            . '<p><strong>Descricao:</strong><br>' . $descricao . '</p>'
            . '<p>Acesse o sistema para acompanhar.</p>';

        return self::send(
            $emailGestor,
            'Gestor',
            'Nova ocorrência registrada — Lab Relator',
            $body
        );
        // ── FIM CORREÇÃO QA ──
    }

    /** @param array<string, mixed> $ocorrencia */
    public static function enviarOcorrenciaEncerrada(
        string $emailProfessor,
        string $nomeProfessor,
        array $ocorrencia
    ): bool {
        // ── INÍCIO CORREÇÃO QA ──
        $h = self::h(...);
        $id = (int)($ocorrencia['id'] ?? 0);
        $laboratorio = $h($ocorrencia['laboratorio_nome'] ?? 'Laboratorio nao informado');
        $tecnico = $h($ocorrencia['tecnico_nome'] ?? 'Responsavel nao informado');

        $body = '<p>Ola, ' . $h($nomeProfessor) . '.</p>'
            . '<p>A ocorrencia #' . $id . ' foi encerrada.</p>'
            . '<p><strong>Laboratorio:</strong> ' . $laboratorio . ' | <strong>Tecnico:</strong> ' . $tecnico . '</p>';

        return self::send(
            $emailProfessor,
            $nomeProfessor,
            'Sua ocorrência foi encerrada — Lab Relator',
            $body
        );
        // ── FIM CORREÇÃO QA ──
    }

    private static function send(string $email, string $nome, string $subject, string $body): bool
    {
        try {
            $mail = self::mailer();
            $mail->addAddress($email, $nome);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Throwable $e) {
            error_log('[MailService] ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @throws MailException
     * @return PHPMailer
     */
    private static function mailer(): PHPMailer
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = (string)$config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$config['username'];
        $mail->Password = (string)$config['password'];
        $mail->SMTPSecure = (string)$config['encryption'];
        $mail->Port = (int)$config['port'];
        $mail->setFrom((string)$config['from_email'], (string)$config['from_name']);

        return $mail;
    }

    private static function h(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
