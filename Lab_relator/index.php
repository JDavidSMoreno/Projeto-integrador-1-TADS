<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PageController;
use App\Core\Router;
use App\Helpers\Csrf;

$sessionPath = session_save_path();
if ($sessionPath === '' || !is_dir($sessionPath) || !is_writable($sessionPath)) {
    $fallbackSessionPath = __DIR__ . '/storage/sessions';
    if (!is_dir($fallbackSessionPath)) {
        mkdir($fallbackSessionPath, 0775, true);
    }

    session_save_path($fallbackSessionPath);
}

session_start();

require __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

define('APP_ROOT', __DIR__);
define('APP_BASE_PATH', $basePath);

if ($basePath !== '') {
    ob_start(static function (string $html) use ($basePath): string {
        return (string)preg_replace(
            '/\b(href|src|action)=("|\')\/(?!\/)/i',
            '$1=$2' . $basePath . '/',
            $html
        );
    });
}

Csrf::token();

$router = new Router($basePath);

$router->get('/', [DashboardController::class, 'index'])->middleware('auth');
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'processLogin'])->middleware('csrf');
$router->get('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/login', [AuthController::class, 'processLogin'])->middleware('csrf');
$router->get('/auth/logout', [AuthController::class, 'logout'])->middleware('auth');
$router->get('/auth/recuperar', [AuthController::class, 'recuperar']);
$router->post('/auth/recuperar', [AuthController::class, 'processRecuperar'])->middleware('csrf');
$router->get('/auth/resetar/{token}', [AuthController::class, 'resetar']);
$router->post('/auth/resetar/{token}', [AuthController::class, 'processResetar'])->middleware('csrf');

$router->get('/laboratorio', [PageController::class, 'laboratorio'])
    ->middleware('auth')
    ->middleware('role:gestor');

$router->get('/equipamento', [PageController::class, 'equipamento'])
    ->middleware('auth')
    ->middleware('role:gestor');

$router->get('/usuario/professor', [PageController::class, 'professores'])
    ->middleware('auth')
    ->middleware('role:gestor');

$router->get('/usuario/tecnico', [PageController::class, 'tecnicos'])
    ->middleware('auth')
    ->middleware('role:gestor');

$router->get('/tipo-problema', [PageController::class, 'tiposProblema'])
    ->middleware('auth')
    ->middleware('role:gestor');

$router->get('/ocorrencia', [PageController::class, 'ocorrencias'])
    ->middleware('auth')
    ->middleware('role:professor,gestor');

$router->get('/ocorrencia/criar', [PageController::class, 'criarOcorrencia'])
    ->middleware('auth')
    ->middleware('role:professor');

$router->get('/monitor', [PageController::class, 'monitor'])
    ->middleware('auth')
    ->middleware('role:tecnico,gestor');

$router->get('/monitor/historico', [PageController::class, 'monitor'])
    ->middleware('auth')
    ->middleware('role:tecnico,gestor');

$router->get('/relatorio', [PageController::class, 'relatorios'])
    ->middleware('auth')
    ->middleware('role:gestor');

$router->dispatch();
