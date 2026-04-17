<?php
declare(strict_types=1);

/**
 * Front controller do sistema.
 *
 * Processo de autenticação documentado:
 * 1) Inicializa sessão e token CSRF.
 * 2) Normaliza rota atual (compatível com execução em subpasta no XAMPP).
 * 3) Se rota for privada e usuário não estiver autenticado, redireciona para login.
 * 4) Em /auth/login (POST), valida CSRF + credenciais e cria sessão do usuário.
 * 5) Em /auth/logout, finaliza sessão de forma segura e volta para login.
 *  gestor@unieinstein.edu.br / Gestor@123
 *  professor@unieinstein.edu.br / Professor@123
 *  tecnico@unieinstein.edu.br / Tecnico@123
 * 
 */
session_start();

/** Base path da aplicação (ex.: /Lab_relator quando rodando em subpasta). */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

/**
 * Compatibilidade para execução em subpasta:
 * converte href/src/action iniciados com "/" para "/{basePath}/...".
 */
if ($basePath !== '') {
    ob_start(static function (string $html) use ($basePath): string {
        return (string)preg_replace(
            '/\b(href|src|action)=("|\')\/(?!\/)/i',
            '$1=$2' . $basePath . '/',
            $html
        );
    });
}

/**
 * Redireciona para uma rota interna respeitando base path.
 */
function redirectTo(string $path, string $basePath): void
{
    $safePath = '/' . ltrim($path, '/');
    $location = ($basePath !== '' ? $basePath : '') . $safePath;
    header('Location: ' . $location);
    exit;
}

/**
 * Normaliza REQUEST_URI para rotas internas (sempre iniciando com '/').
 */
function normalizeRoute(string $requestUri, string $basePath): string
{
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

    if ($basePath !== '' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath));
    }

    if ($path === '' || $path === false) {
        $path = '/';
    }

    if ($path !== '/') {
        $path = '/' . trim($path, '/');
    }

    return $path;
}

/**
 * Garante existência do token CSRF em sessão.
 */
function ensureCsrfToken(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Valida se há um usuário autenticado em sessão.
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['id_usuario'], $_SESSION['tipo_usuario'])
        && (int)$_SESSION['id_usuario'] > 0;
}

ensureCsrfToken();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$uri = normalizeRoute($_SERVER['REQUEST_URI'] ?? '/', $basePath);

/** Rotas públicas (não exigem autenticação). */
$publicRoutes = [
    '/auth/login',
    '/auth/recuperar',
];

/**
 * Usuários temporários de desenvolvimento.
 *
 * Senhas para teste:
 * - gestor@unieinstein.edu.br   => Gestor@123
 * - professor@unieinstein.edu.br=> Professor@123
 * - tecnico@unieinstein.edu.br  => Tecnico@123
 *
 * Observação: manter esse bloco apenas enquanto o login não estiver integrado ao banco.
 */
$devUsers = [
    'gestor@unieinstein.edu.br' => [
        'id' => 1,
        'nome' => 'Gestor do Sistema',
        'email' => 'gestor@unieinstein.edu.br',
        'tipo' => 'gestor',
        'senha_hash' => '$2y$12$8cyIKgWBYJLRxd.8tm8GnueLy0nVucstBLectoCg3MCuQP32OIGqe',
    ],
    'professor@unieinstein.edu.br' => [
        'id' => 2,
        'nome' => 'Professor Demo',
        'email' => 'professor@unieinstein.edu.br',
        'tipo' => 'professor',
        'senha_hash' => '$2y$12$OTZbGC9mGPTF4wLihHt6.uzSKdkjHzd6N4dudBWjuqCc.WmRCcW9C',
    ],
    'tecnico@unieinstein.edu.br' => [
        'id' => 3,
        'nome' => 'Tecnico Demo',
        'email' => 'tecnico@unieinstein.edu.br',
        'tipo' => 'tecnico',
        'senha_hash' => '$2y$12$Rkt2qHkISoeMnXig4Hx8VurKTy1xMrlDXIoVLYHiXDsdKWbbJ1AaG',
    ],
];

/**
 * Middleware simples de autorização.
 * Se a rota não for pública e não houver sessão válida, envia para login.
 */
if (!in_array($uri, $publicRoutes, true) && !isAuthenticated()) {
    redirectTo('/auth/login', $basePath);
}

switch ($uri) {
    case '/':
    case '/dashboard':
        $pageTitle = 'Dashboard';
        $activeRoute = 'dashboard';
        include __DIR__ . '/views/layouts/header.php';
        echo '<h1 class="text-center mt-5">Dashboard (em breve)</h1>';
        include __DIR__ . '/views/layouts/footer.php';
        break;

    case '/auth/login':
        if (isAuthenticated()) {
            redirectTo('/dashboard', $basePath);
        }

        $error = null;

        if ($method === 'POST') {
            $csrfToken = (string)($_POST['csrf_token'] ?? '');
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $senha = (string)($_POST['senha'] ?? '');

            if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
                $error = 'Token de segurança inválido. Atualize a página e tente novamente.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Informe um e-mail válido.';
            } elseif (strlen($senha) < 8) {
                $error = 'Informe a senha com pelo menos 8 caracteres.';
            } else {
                $user = $devUsers[$email] ?? null;

                if (!$user || !password_verify($senha, (string)$user['senha_hash'])) {
                    $error = 'E-mail ou senha inválidos.';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['id_usuario'] = (int)$user['id'];
                    $_SESSION['nome_usuario'] = (string)$user['nome'];
                    $_SESSION['email_usuario'] = (string)$user['email'];
                    $_SESSION['tipo_usuario'] = (string)$user['tipo'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    redirectTo('/dashboard', $basePath);
                }
            }
        }

        include __DIR__ . '/views/auth/login.php';
        break;

    case '/auth/logout':
        /**
         * Logout seguro:
         * - limpa os dados da sessão atual
         * - invalida o cookie de sessão
         * - destrói a sessão
         */
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }

        session_destroy();
        redirectTo('/auth/login', $basePath);
        break;

    case '/auth/recuperar':
        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html>';
        echo '<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Recuperar senha</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">';
        echo '<main class="container py-5"><div class="card shadow-sm border-0"><div class="card-body p-4">';
        echo '<h1 class="h4 mb-3">Recuperação de senha</h1>';
        echo '<p class="mb-3">Esta funcionalidade está em construção no momento.</p>';
        echo '<a class="btn btn-primary" href="' . htmlspecialchars(($basePath !== '' ? $basePath : '') . '/auth/login', ENT_QUOTES, 'UTF-8') . '">Voltar para login</a>';
        echo '</div></div></main></body></html>';
        break;

    case '/laboratorio':
        include __DIR__ . '/views/laboratorio/index.php';
        break;

    case '/equipamento':
        include __DIR__ . '/views/equipamento/index.php';
        break;

    case '/usuario/professor':
        include __DIR__ . '/views/usuario/professores.php';
        break;

    case '/usuario/tecnico':
        include __DIR__ . '/views/usuario/tecnicos.php';
        break;

    case '/ocorrencia':
        include __DIR__ . '/views/ocorrencias/list.php';
        break;

    case '/ocorrencia/criar':
        include __DIR__ . '/views/ocorrencias/create.php';
        break;

    case '/monitor':
    case '/monitor/historico':
        include __DIR__ . '/views/monitor/index.php';
        break;

    case '/relatorio':
        include __DIR__ . '/views/relatorios/index.php';
        break;

    case '/tipo-problema':
        include __DIR__ . '/views/tipo-problema/index.php';
        break;

    default:
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Página não encontrada';
}