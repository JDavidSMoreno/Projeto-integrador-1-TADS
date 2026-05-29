<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Helpers\SessionHelper;

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (SessionHelper::isAuthenticated()) {
            return;
        }

        SessionHelper::flash('warning', 'Acesso negado. Faca login para continuar.');
        self::redirect('/auth/login');
    }

    public static function role(string ...$roles): void
    {
        self::handle();

        $perfil = SessionHelper::role();
        if ($perfil !== null && in_array($perfil, $roles, true)) {
            return;
        }

        SessionHelper::flash('danger', 'Voce nao tem permissao para acessar esta area.');
        self::abort(403);
    }

    private static function redirect(string $path): void
    {
        $basePath = defined('APP_BASE_PATH') ? (string)APP_BASE_PATH : '';
        $location = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        header('Location: ' . $location);
        exit;
    }

    private static function abort(int $status): void
    {
        http_response_code($status);

        $view = dirname(__DIR__, 3) . '/views/errors/' . $status . '.php';
        if (is_file($view)) {
            $title = $status === 403 ? 'Acesso negado' : 'Erro';
            $message = 'Voce nao tem permissao para acessar esta pagina.';
            include $view;
        } else {
            echo 'Erro ' . $status;
        }

        exit;
    }
}
