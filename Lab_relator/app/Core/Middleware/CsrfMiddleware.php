<?php
declare(strict_types=1);

// Arquivo: app/Core/Middleware/CsrfMiddleware.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Core\Middleware;

use App\Helpers\Csrf;
use App\Helpers\SessionHelper;

final class CsrfMiddleware
{
    public static function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        // ── INÍCIO CORREÇÃO QA ──
        $token = $_POST['_csrf_token']
            ?? $_POST['csrf_token']
            ?? $_POST['_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        // ── FIM CORREÇÃO QA ──

        if (Csrf::validate(is_string($token) ? $token : null)) {
            return;
        }

        SessionHelper::flash('danger', 'Token de seguranca invalido. Atualize a pagina e tente novamente.');
        self::redirectBack();
    }

    private static function redirectBack(): void
    {
        $fallback = '/auth/login';
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $basePath = defined('APP_BASE_PATH') ? (string)APP_BASE_PATH : '';

        if ($referer !== '') {
            header('Location: ' . $referer);
            exit;
        }

        header('Location: ' . rtrim($basePath, '/') . $fallback);
        exit;
    }
}
