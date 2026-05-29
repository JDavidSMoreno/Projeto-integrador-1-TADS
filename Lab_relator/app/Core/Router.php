<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

final class Router
{
    /** @var array<int, array{method: string, path: string, regex: string, handler: mixed, middleware: array<int, mixed>}> */
    private array $routes = [];

    private ?int $lastRouteIndex = null;

    public function __construct(private readonly string $basePath = '')
    {
    }

    public function get(string $path, mixed $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, mixed $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function middleware(string|callable $middleware): self
    {
        if ($this->lastRouteIndex === null) {
            return $this;
        }

        $this->routes[$this->lastRouteIndex]['middleware'][] = $middleware;

        return $this;
    }

    public function dispatch(?string $requestUri = null, ?string $requestMethod = null): void
    {
        $method = $this->resolveMethod($requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = $this->normalizePath($requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/'));

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            try {
                foreach ($route['middleware'] as $middleware) {
                    $this->runMiddleware($middleware, $params);
                }

                $this->callHandler($route['handler'], $params);
            } catch (Throwable $exception) {
                error_log('[Router] Dispatch error: ' . $exception->getMessage());
                $this->abort(500);
            }

            return;
        }

        $this->abort(404);
    }

    public function abort(int $status = 404): void
    {
        http_response_code($status);

        $title = match ($status) {
            403 => 'Acesso negado',
            404 => 'Pagina nao encontrada',
            default => 'Erro interno',
        };

        $message = match ($status) {
            403 => 'Voce nao tem permissao para acessar esta pagina.',
            404 => 'A rota solicitada nao foi encontrada.',
            default => 'Nao foi possivel concluir a requisicao.',
        };

        $view = dirname(__DIR__, 2) . '/views/errors/' . $status . '.php';
        if (!is_file($view)) {
            $view = dirname(__DIR__, 2) . '/views/errors/generic.php';
        }

        if (is_file($view)) {
            include $view;
        } else {
            echo $title;
        }

        exit;
    }

    private function add(string $method, string $path, mixed $handler): self
    {
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'regex' => $this->compilePath($path),
            'handler' => $handler,
            'middleware' => [],
        ];

        $this->lastRouteIndex = array_key_last($this->routes);

        return $this;
    }

    private function compilePath(string $path): string
    {
        $regex = preg_quote($path, '#');
        $regex = preg_replace(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#',
            '(?P<$1>[^/]+)',
            $regex
        );

        return '#^' . $regex . '$#';
    }

    private function normalizePath(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $basePath = rtrim($this->basePath, '/');

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    private function resolveMethod(string $method): string
    {
        $method = strtoupper($method);

        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string)$_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return $method;
    }

    /** @param array<string, string> $params */
    private function runMiddleware(string|callable $middleware, array $params): void
    {
        if (is_callable($middleware)) {
            $middleware($params);
            return;
        }

        if ($middleware === 'auth') {
            AuthMiddleware::handle();
            return;
        }

        if ($middleware === 'csrf') {
            CsrfMiddleware::handle();
            return;
        }

        if (str_starts_with($middleware, 'role:')) {
            $roles = preg_split('/[,|]/', substr($middleware, 5)) ?: [];
            $roles = array_values(array_filter(array_map('trim', $roles)));
            AuthMiddleware::role(...$roles);
        }
    }

    /** @param array<string, string> $params */
    private function callHandler(mixed $handler, array $params): void
    {
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            $controller = is_string($handler[0]) ? new $handler[0]() : $handler[0];
            $method = (string)$handler[1];

            $reflection = new ReflectionMethod($controller, $method);
            if ($reflection->getNumberOfParameters() > 0) {
                $controller->{$method}($params);
                return;
            }

            $controller->{$method}();
            return;
        }

        if (is_callable($handler)) {
            $reflection = new ReflectionFunction($handler);
            if ($reflection->getNumberOfParameters() > 0) {
                $handler($params);
                return;
            }

            $handler();
        }
    }
}
