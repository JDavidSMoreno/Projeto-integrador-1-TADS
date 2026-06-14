<?php
declare(strict_types=1);

// Arquivo: app/Controllers/MonitorController.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Controllers;

use App\Models\LaboratorioModel;
use App\Models\OcorrenciaModel;
use App\Services\MailService;
use Throwable;

final class MonitorController extends BaseController
{
    private OcorrenciaModel $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new OcorrenciaModel();
    }

    public function index(): void
    {
        $filtros = [
            'id_laboratorio' => trim((string)($_GET['id_laboratorio'] ?? '')),
        ];
        $ocorrencias = [];
        $laboratorios = [];
        $stats = $this->emptyStats();

        try {
            $laboratorios = (new LaboratorioModel())->listActive();
            $ocorrencias = $this->model->monitorList($filtros, $this->usuarioId(), $this->usuarioPerfil());
            $stats = $this->statsFromRows($ocorrencias);
        } catch (Throwable $exception) {
            error_log('[MonitorController] Index error: ' . $exception->getMessage());
            $_SESSION['flash_error'] = 'Nao foi possivel carregar o monitor de chamados.';
            $_SESSION['flash_type'] = 'danger';
            $_SESSION['flash_message'] = 'Nao foi possivel carregar o monitor de chamados.';
        }

        $this->render('monitor/index', [
            'ocorrencias' => $ocorrencias,
            'laboratorios' => $laboratorios,
            'stats' => $stats,
            'filtros' => $filtros,
            'totalAbertos' => $stats['nao_atendida'],
        ], false);
    }

    /** @param array<string, string> $params */
    public function historico(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->abort(404);
        }

        try {
            $ocorrencia = $this->model->findById($id);
            if ($ocorrencia === false) {
                $this->abort(404);
            }

            if (!$this->canView($ocorrencia)) {
                $this->abort(403);
            }

            $historico = $this->model->getHistorico($id);
            $this->renderHistorico($ocorrencia, $historico);
        } catch (Throwable $exception) {
            error_log('[MonitorController] History error: ' . $exception->getMessage());
            $this->abort(500);
        }
    }

    public function atualizarStatus(): void
    {
        $idOcorrencia = (int)($_POST['id_ocorrencia'] ?? $_POST['id'] ?? 0);
        $novoStatus = trim((string)($_POST['novo_status'] ?? ''));
        $observacao = trim((string)($_POST['observacao'] ?? ''));

        if ($idOcorrencia <= 0 || $novoStatus === '') {
            $this->json([
                'success' => false,
                'message' => 'Dados obrigatorios nao informados.',
            ], 422);
        }

        try {
            $ocorrencia = $this->model->findById($idOcorrencia);
            if ($ocorrencia === false) {
                $this->json([
                    'success' => false,
                    'message' => 'Ocorrencia nao encontrada.',
                ], 404);
            }

            if (!$this->canView($ocorrencia)) {
                $this->json([
                    'success' => false,
                    'message' => 'Voce nao tem permissao para alterar esta ocorrencia.',
                ], 403);
            }

            $updated = $this->model->changeStatus(
                $idOcorrencia,
                $novoStatus,
                $this->usuarioId(),
                $observacao
            );

            // ── INÍCIO CORREÇÃO QA ──
            if ($updated && $novoStatus === 'Encerrada') {
                $this->notificarProfessorEncerramento($idOcorrencia);
            }
            // ── FIM CORREÇÃO QA ──

            $this->json([
                'success' => $updated,
                'message' => $updated ? 'Status atualizado com sucesso.' : 'Nenhuma alteracao realizada.',
            ]);
        } catch (Throwable $exception) {
            error_log('[MonitorController] Status update error: ' . $exception->getMessage());
            $this->json([
                'success' => false,
                'message' => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : 'Nao foi possivel atualizar o status.',
            ], 422);
        }
    }

    /** @return array{nao_atendida: int, em_edicao: int, em_atendimento: int, encerrada: int, total: int} */
    private function emptyStats(): array
    {
        return [
            'nao_atendida' => 0,
            'em_edicao' => 0,
            'em_atendimento' => 0,
            'encerrada' => 0,
            'total' => 0,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{nao_atendida: int, em_edicao: int, em_atendimento: int, encerrada: int, total: int}
     */
    private function statsFromRows(array $rows): array
    {
        $stats = $this->emptyStats();

        foreach ($rows as $row) {
            $stats['total']++;

            $status = (string)($row['status'] ?? '');
            if ($status === 'Nao Atendida') {
                $stats['nao_atendida']++;
            } elseif ($status === 'Em Edicao') {
                $stats['em_edicao']++;
            } elseif ($status === 'Em Atendimento') {
                $stats['em_atendimento']++;
            } elseif ($status === 'Encerrada') {
                $stats['encerrada']++;
            }
        }

        return $stats;
    }

    /** @param array<string, mixed> $ocorrencia */
    private function canView(array $ocorrencia): bool
    {
        $perfil = $this->usuarioPerfil();
        $usuarioId = $this->usuarioId();

        if ($perfil === 'gestor') {
            return true;
        }

        if ($perfil === 'tecnico') {
            return (string)($ocorrencia['status'] ?? '') === 'Nao Atendida'
                || (int)($ocorrencia['id_tecnico'] ?? 0) === $usuarioId;
        }

        return false;
    }

    private function usuarioId(): int
    {
        return (int)(
            $_SESSION['usuario']['id']
            ?? $_SESSION['usuario_id']
            ?? $_SESSION['id_usuario']
            ?? 0
        );
    }

    private function usuarioPerfil(): string
    {
        return (string)(
            $_SESSION['usuario']['perfil']
            ?? $_SESSION['usuario_tipo']
            ?? $_SESSION['tipo_usuario']
            ?? ''
        );
    }

    private function notificarProfessorEncerramento(int $idOcorrencia): void
    {
        // ── INÍCIO CORREÇÃO QA ──
        try {
            $ocorrencia = $this->model->findById($idOcorrencia);
            if ($ocorrencia === false || empty($ocorrencia['professor_email'])) {
                return;
            }

            MailService::enviarOcorrenciaEncerrada(
                (string)$ocorrencia['professor_email'],
                (string)($ocorrencia['professor_nome'] ?? 'Professor'),
                $ocorrencia
            );
        } catch (Throwable $exception) {
            error_log('[MonitorController] Mail encerramento error: ' . $exception->getMessage());
        }
        // ── FIM CORREÇÃO QA ──
    }

    /**
     * @param array<string, mixed> $ocorrencia
     * @param array<int, array<string, mixed>> $historico
     */
    private function renderHistorico(array $ocorrencia, array $historico): void
    {
        $pageTitle = 'Historico do Chamado';
        $activeRoute = 'historico';
        $h = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        include $this->viewsPath . '/layouts/header.php';
        ?>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <h1 class="h4 mb-1">Historico #OC-<?= $h(str_pad((string)(int)$ocorrencia['id'], 3, '0', STR_PAD_LEFT)) ?></h1>
            <p class="text-muted mb-0">
              <?= $h($ocorrencia['laboratorio_nome'] ?? '-') ?>
              - <?= $h($ocorrencia['status'] ?? '-') ?>
            </p>
          </div>
          <a href="/monitor" class="btn btn-outline-secondary" style="border-radius:8px">Voltar</a>
        </div>

        <div class="sr-card card mb-3">
          <div class="sr-card-header">
            <h2 class="sr-card-title h6">Resumo</h2>
          </div>
          <div class="p-3">
            <dl class="row mb-0">
              <dt class="col-sm-3">Tipo</dt>
              <dd class="col-sm-9"><?= $h($ocorrencia['tipo_problema_desc'] ?? $ocorrencia['tipo_problema_nome'] ?? '-') ?></dd>
              <dt class="col-sm-3">Professor</dt>
              <dd class="col-sm-9"><?= $h($ocorrencia['professor_nome'] ?? '-') ?></dd>
              <dt class="col-sm-3">Tecnico</dt>
              <dd class="col-sm-9"><?= $h($ocorrencia['tecnico_nome'] ?? '-') ?></dd>
              <dt class="col-sm-3">Descricao</dt>
              <dd class="col-sm-9"><?= nl2br($h($ocorrencia['descricao'] ?? '')) ?></dd>
            </dl>
          </div>
        </div>

        <div class="sr-card card">
          <div class="sr-card-header">
            <h2 class="sr-card-title h6">Movimentacoes</h2>
          </div>
          <div class="table-responsive">
            <table class="sr-table table mb-0">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Usuario</th>
                  <th>De</th>
                  <th>Para</th>
                  <th>Observacao</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($historico === []): ?>
                  <tr><td colspan="5" class="text-muted">Nenhum historico registrado.</td></tr>
                <?php else: ?>
                  <?php foreach ($historico as $item): ?>
                    <tr>
                      <td><?= $h($item['criado_em'] ?? '') ?></td>
                      <td><?= $h($item['usuario_nome'] ?? '-') ?></td>
                      <td><?= $h($item['status_anterior'] ?? '-') ?></td>
                      <td><?= $h($item['status_novo'] ?? '-') ?></td>
                      <td><?= $h($item['observacao'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php
        include $this->viewsPath . '/layouts/footer.php';
    }
}
