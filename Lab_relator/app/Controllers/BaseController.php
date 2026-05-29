<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SessionHelper;

abstract class BaseController
{
    protected string $viewsPath;

    public function __construct()
    {
        $this->viewsPath = dirname(__DIR__, 2) . '/views';
    }

    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = [], bool $useLayout = true): void
    {
        $viewPath = $this->viewsPath . '/' . trim($view, '/') . '.php';

        if (!is_file($viewPath)) {
            $this->abort(404);
        }

        extract($data, EXTR_SKIP);

        if ($useLayout) {
            include $this->viewsPath . '/layouts/header.php';
        }

        include $viewPath;

        if ($useLayout) {
            include $this->viewsPath . '/layouts/footer.php';
        }
    }

    /** @param array<string, mixed> $payload */
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $path): void
    {
        $basePath = defined('APP_BASE_PATH') ? (string)APP_BASE_PATH : '';
        $location = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        header('Location: ' . $location);
        exit;
    }

    protected function requireLogin(): void
    {
        if (SessionHelper::isAuthenticated()) {
            return;
        }

        SessionHelper::flash('warning', 'Faca login para continuar.');
        $this->redirect('/auth/login');
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireLogin();

        $role = SessionHelper::role();
        if ($role !== null && in_array($role, $roles, true)) {
            return;
        }

        $this->abort(403);
    }

    protected function isAuthenticated(): bool
    {
        return SessionHelper::isAuthenticated();
    }

    protected function currentRole(): ?string
    {
        return SessionHelper::role();
    }

    protected function abort(int $status): void
    {
        http_response_code($status);

        $title = $status === 403 ? 'Acesso negado' : 'Pagina nao encontrada';
        $message = $status === 403
            ? 'Voce nao tem permissao para acessar esta pagina.'
            : 'A pagina solicitada nao foi encontrada.';

        $view = $this->viewsPath . '/errors/' . $status . '.php';
        if (is_file($view)) {
            include $view;
        } else {
            echo $title;
        }

        exit;
    }
}
