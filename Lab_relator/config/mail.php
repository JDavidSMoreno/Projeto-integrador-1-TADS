<?php
declare(strict_types=1);

// Arquivo: config/mail.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

return [
    'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => (int)(getenv('MAIL_PORT') ?: 587),
    'username' => getenv('MAIL_USER') ?: 'seuemail@gmail.com',
    'password' => getenv('MAIL_PASS') ?: '',
    'from_email' => getenv('MAIL_FROM') ?: 'seuemail@gmail.com',
    'from_name' => getenv('MAIL_NAME') ?: 'Lab Relator',
    'encryption' => 'tls',
];
