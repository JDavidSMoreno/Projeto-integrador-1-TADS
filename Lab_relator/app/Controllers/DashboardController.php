<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Models\DashboardModel;
use App\Models\LoginAttemptModel;
use App\Models\PasswordResetModel;
use App\Models\UsuarioModel;
use Throwable;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $user = SessionHelper::user() ?? [];
        $perfil = (string)($user['perfil'] ?? 'professor');
        $usuarioId = (int)($user['id'] ?? 0);
        $warning = null;

        $data = [
            'pageTitle' => 'Dashboard',
            'activeRoute' => 'dashboard',
            'user' => $user,
            'perfil' => $perfil,
            'usuariosPorPerfil' => ['gestor' => 0, 'professor' => 0, 'tecnico' => 0],
            'usuariosAtivos' => 0,
            'loginFailures24h' => 0,
            'passwordResetsAbertos' => 0,
            'ocorrencias' => [
                'total' => 0,
                'nao_atendida' => 0,
                'em_atendimento' => 0,
                'encerrada' => 0,
            ],
            'warning' => null,
        ];

        try {
            $usuarios = new UsuarioModel();
            $dashboard = new DashboardModel();

            $data['usuariosPorPerfil'] = $usuarios->countByPerfil();
            $data['usuariosAtivos'] = $usuarios->countActive();
            $data['ocorrencias'] = $dashboard->occurrenceStatsForRole($perfil, $usuarioId);

            try {
                $data['loginFailures24h'] = (new LoginAttemptModel())->countFailuresLastDay();
                $data['passwordResetsAbertos'] = (new PasswordResetModel())->countOpen();
            } catch (Throwable $exception) {
                error_log('[DashboardController] Security stats unavailable: ' . $exception->getMessage());
            }
        } catch (Throwable $exception) {
            error_log('[DashboardController] Dashboard data error: ' . $exception->getMessage());
            $warning = 'Nao foi possivel carregar todos os dados do banco. Verifique a configuracao e as tabelas da Fase 2.';
        }

        $data['warning'] = $warning;

        $this->render('dashboard/index', $data);
    }
}
