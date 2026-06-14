<?php
declare(strict_types=1);

// Arquivo: index.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EquipamentoController;
use App\Controllers\LaboratorioController;
use App\Controllers\MonitorController;
use App\Controllers\OcorrenciaController;
use App\Controllers\ProfessorController;
use App\Controllers\RelatorioController;
use App\Controllers\TecnicoController;
use App\Controllers\TipoProblemaController;
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

// ── INÍCIO CORREÇÃO QA ──
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
// ── FIM CORREÇÃO QA ──
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
// ── INÍCIO CORREÇÃO QA ──
$router->post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->middleware('csrf');
// ── FIM CORREÇÃO QA ──
$router->get('/auth/recuperar', [AuthController::class, 'recuperar']);
$router->post('/auth/recuperar', [AuthController::class, 'processRecuperar'])->middleware('csrf');
$router->get('/auth/resetar/{token}', [AuthController::class, 'resetar']);
$router->post('/auth/resetar/{token}', [AuthController::class, 'processResetar'])->middleware('csrf');

$router->get('/laboratorio', [LaboratorioController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/laboratorio/novo', [LaboratorioController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/laboratorio/{id}/editar', [LaboratorioController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/laboratorio/editar/{id}', [LaboratorioController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/laboratorio/salvar', [LaboratorioController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/laboratorio/{id}/atualizar', [LaboratorioController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/laboratorio/atualizar', [LaboratorioController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/laboratorio/{id}/toggle', [LaboratorioController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/laboratorio/status', [LaboratorioController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

$router->get('/equipamento/por-laboratorio', [EquipamentoController::class, 'porLaboratorio'])
    ->middleware('auth');
$router->get('/equipamento', [EquipamentoController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/equipamento/novo', [EquipamentoController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/equipamento/{id}/editar', [EquipamentoController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/equipamento/editar/{id}', [EquipamentoController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/equipamento/salvar', [EquipamentoController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/equipamento/{id}/atualizar', [EquipamentoController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/equipamento/atualizar', [EquipamentoController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/equipamento/{id}/toggle', [EquipamentoController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/equipamento/status', [EquipamentoController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

$router->get('/usuario/professor', [ProfessorController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/usuario/professor/novo', [ProfessorController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/usuario/professor/{id}/editar', [ProfessorController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/usuario/professor/editar/{id}', [ProfessorController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/usuario/professor/salvar', [ProfessorController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/usuario/professor/{id}/atualizar', [ProfessorController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/usuario/professor/{id}/toggle', [ProfessorController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

// Rotas alias para acesso direto ao cadastro de professores.
$router->get('/professor', [ProfessorController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/professor/novo', [ProfessorController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/professor/{id}/editar', [ProfessorController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/professor/salvar', [ProfessorController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/professor/{id}/atualizar', [ProfessorController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/professor/{id}/toggle', [ProfessorController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

$router->get('/usuario/tecnico', [TecnicoController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/usuario/tecnico/novo', [TecnicoController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/usuario/tecnico/{id}/editar', [TecnicoController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/usuario/tecnico/editar/{id}', [TecnicoController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/usuario/tecnico/salvar', [TecnicoController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/usuario/tecnico/{id}/atualizar', [TecnicoController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/usuario/tecnico/{id}/toggle', [TecnicoController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

// Rotas alias para acesso direto ao cadastro de tecnicos.
$router->get('/tecnico', [TecnicoController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/tecnico/novo', [TecnicoController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/tecnico/{id}/editar', [TecnicoController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/tecnico/salvar', [TecnicoController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/tecnico/{id}/atualizar', [TecnicoController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/tecnico/{id}/toggle', [TecnicoController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

$router->get('/tipo-problema', [TipoProblemaController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/tipo-problema/novo', [TipoProblemaController::class, 'novo'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/tipo-problema/{id}/editar', [TipoProblemaController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->post('/tipo-problema/salvar', [TipoProblemaController::class, 'salvar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/tipo-problema/{id}/atualizar', [TipoProblemaController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');
$router->post('/tipo-problema/{id}/toggle', [TipoProblemaController::class, 'toggle'])
    ->middleware('auth')
    ->middleware('role:gestor')
    ->middleware('csrf');

$router->get('/ocorrencia', [OcorrenciaController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:professor,tecnico,gestor');

$router->get('/ocorrencia/criar', [OcorrenciaController::class, 'criar'])
    ->middleware('auth')
    ->middleware('role:professor');
$router->post('/ocorrencia/registrar', [OcorrenciaController::class, 'registrar'])
    ->middleware('auth')
    ->middleware('role:professor')
    ->middleware('csrf');
$router->get('/ocorrencia/ver/{id}', [OcorrenciaController::class, 'ver'])
    ->middleware('auth')
    ->middleware('role:professor,tecnico,gestor');
$router->get('/ocorrencia/editar/{id}', [OcorrenciaController::class, 'editar'])
    ->middleware('auth')
    ->middleware('role:professor');
$router->post('/ocorrencia/atualizar/{id}', [OcorrenciaController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:professor')
    ->middleware('csrf');
$router->post('/ocorrencia/atualizar', [OcorrenciaController::class, 'atualizar'])
    ->middleware('auth')
    ->middleware('role:professor')
    ->middleware('csrf');
$router->post('/ocorrencia/cancelar/{id}', [OcorrenciaController::class, 'cancelar'])
    ->middleware('auth')
    ->middleware('role:professor')
    ->middleware('csrf');
$router->post('/ocorrencia/cancelar', [OcorrenciaController::class, 'cancelar'])
    ->middleware('auth')
    ->middleware('role:professor')
    ->middleware('csrf');

$router->get('/monitor', [MonitorController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:tecnico,gestor');

$router->get('/monitor/historico', [MonitorController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:tecnico,gestor');
$router->get('/monitor/historico/{id}', [MonitorController::class, 'historico'])
    ->middleware('auth')
    ->middleware('role:tecnico,gestor');
$router->post('/monitor/atualizar-status', [MonitorController::class, 'atualizarStatus'])
    ->middleware('auth')
    ->middleware('role:tecnico,gestor')
    ->middleware('csrf');

// ── INÍCIO CORREÇÃO QA ──
$router->get('/relatorio', [RelatorioController::class, 'index'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/relatorio/exportar-csv', [RelatorioController::class, 'exportarCsv'])
    ->middleware('auth')
    ->middleware('role:gestor');
$router->get('/relatorio/exportar-pdf', [RelatorioController::class, 'exportarPdf'])
    ->middleware('auth')
    ->middleware('role:gestor');
// ── FIM CORREÇÃO QA ──

$router->dispatch();
