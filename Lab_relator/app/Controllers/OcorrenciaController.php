<?php
declare(strict_types=1);

// Arquivo: app/Controllers/OcorrenciaController.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Helpers\Validator;
use App\Models\EquipamentoModel;
use App\Models\LaboratorioModel;
use App\Models\OcorrenciaModel;
use App\Models\TipoProblemaModel;
use App\Models\UsuarioModel;
use App\Services\MailService;
use InvalidArgumentException;
use Throwable;

final class OcorrenciaController extends BaseController
{
    private OcorrenciaModel $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = new OcorrenciaModel();
    }

    public function index(): void
    {
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 10;
        $perfil = $this->usuarioPerfil();
        $usuarioId = $this->usuarioId();
        $filtros = $this->filtros();
        $laboratorios = [];
        $dados = ['dados' => [], 'total' => 0, 'paginas' => 1];
        $stats = ['nao_atendida' => 0, 'em_edicao' => 0, 'em_atendimento' => 0, 'encerrada' => 0, 'total' => 0];

        try {
            $laboratorios = (new LaboratorioModel())->listActive();
            $dados = $this->model->paginate($pagina, $porPagina, $filtros, $usuarioId, $perfil);
            $stats = $this->model->stats($usuarioId, $perfil);
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Index error: ' . $exception->getMessage());
            $this->flashError('Nao foi possivel carregar as ocorrencias.');
        }

        $this->render('ocorrencias/list', [
            'ocorrencias' => $dados['dados'],
            'stats' => $stats,
            'laboratorios' => $laboratorios,
            'filtros' => $filtros,
            'pagination' => [
                'pagina' => $pagina,
                'total' => $dados['total'],
                'porPagina' => $porPagina,
            ],
        ], false);
    }

    public function criar(): void
    {
        $this->ensureProfessor();
        $this->renderForm();
    }

    public function registrar(): void
    {
        $this->ensureProfessor();

        $input = $this->input();
        $errors = Validator::validar($input, $this->rules(), $this->labels());

        if ($errors === []) {
            $errors = $this->validateEquipamentoLaboratorio($input);
        }

        if ($errors !== []) {
            $this->flashError('Corrija os campos destacados antes de registrar a ocorrencia.');
            $this->renderForm($input, $errors);
            return;
        }

        try {
            $id = $this->model->create($this->payload($input));
            // ── INÍCIO CORREÇÃO QA ──
            $this->notificarGestoresNovaOcorrencia($id);
            // ── FIM CORREÇÃO QA ──
            $this->flashSuccess('Ocorrencia #' . str_pad((string)$id, 3, '0', STR_PAD_LEFT) . ' registrada com sucesso.');
            $this->redirect('/ocorrencia');
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Store error: ' . $exception->getMessage());
            $this->flashError('Nao foi possivel registrar a ocorrencia.');
            $this->renderForm($input, ['geral' => 'Nao foi possivel registrar a ocorrencia.']);
        }
    }

    /** @param array<string, string> $params */
    public function ver(array $params): void
    {
        $ocorrencia = $this->findVisibleOrAbort((int)($params['id'] ?? 0));
        $historico = [];

        try {
            $historico = $this->model->getHistorico((int)$ocorrencia['id']);
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] History error: ' . $exception->getMessage());
            $this->flashError('Nao foi possivel carregar o historico da ocorrencia.');
        }

        $showView = $this->viewsPath . '/ocorrencias/show.php';
        if (!is_file($showView)) {
            $this->renderInlineShow($ocorrencia, $historico);
            return;
        }

        $this->render('ocorrencias/show', [
            'ocorrencia' => $ocorrencia,
            'historico' => $historico,
            'podeEditar' => $this->podeEditar($ocorrencia),
        ], false);
    }

    /** @param array<string, string> $params */
    public function editar(array $params): void
    {
        $this->ensureProfessor();

        $ocorrencia = $this->findVisibleOrAbort((int)($params['id'] ?? 0));
        if (!$this->podeEditar($ocorrencia)) {
            $this->flashError('Esta ocorrencia nao pode mais ser editada.');
            $this->redirect('/ocorrencia');
        }

        $this->renderForm($ocorrencia, [], $ocorrencia, true);
    }

    /** @param array<string, string> $params */
    public function atualizar(array $params = []): void
    {
        $this->ensureProfessor();

        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $ocorrencia = $this->findVisibleOrAbort($id);

        if (!$this->podeEditar($ocorrencia)) {
            $this->flashError('Esta ocorrencia nao pode mais ser editada.');
            $this->redirect('/ocorrencia');
        }

        $input = ['id' => $id] + $this->input();
        $errors = Validator::validar($input, $this->rules(), $this->labels());

        if ($errors === []) {
            $errors = $this->validateEquipamentoLaboratorio($input);
        }

        if ($errors !== []) {
            $this->flashError('Corrija os campos destacados antes de salvar.');
            $this->renderForm(array_merge($ocorrencia, $input), $errors, $ocorrencia, true);
            return;
        }

        try {
            $payload = $this->payload($input);
            $payload['id_usuario'] = $this->usuarioId();

            $this->model->update($id, $payload);
            $this->flashSuccess('Ocorrencia atualizada com sucesso.');
            $this->redirect('/ocorrencia');
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Update error: ' . $exception->getMessage());
            $this->flashError('Nao foi possivel atualizar a ocorrencia.');
            $this->renderForm(array_merge($ocorrencia, $input), ['geral' => 'Nao foi possivel atualizar.'], $ocorrencia, true);
        }
    }

    /** @param array<string, string> $params */
    public function cancelar(array $params = []): void
    {
        $this->ensureProfessor();

        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $ocorrencia = $this->findVisibleOrAbort($id);

        if ((string)$ocorrencia['status'] !== 'Em Edicao') {
            $this->flashError('Apenas ocorrencias em edicao podem voltar para Nao Atendida.');
            $this->redirect('/ocorrencia');
        }

        try {
            $this->model->changeStatus(
                $id,
                'Nao Atendida',
                $this->usuarioId(),
                'Edicao cancelada pelo professor.'
            );
            $this->flashSuccess('Edicao cancelada. A ocorrencia voltou para Nao Atendida.');
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Cancel error: ' . $exception->getMessage());
            $this->flashError('Nao foi possivel cancelar a edicao da ocorrencia.');
        }

        $this->redirect('/ocorrencia');
    }

    /**
     * Variaveis esperadas por views/ocorrencias/create.php:
     * laboratorios, tiposProblema, ocorrencia, podeEditar, errors.
     *
     * @param array<string, mixed>|null $input
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $existing
     */
    private function renderForm(
        ?array $input = null,
        array $errors = [],
        ?array $existing = null,
        bool $editing = false
    ): void {
        $laboratorios = [];
        $equipamentos = [];
        $tiposProblema = [];
        $warning = null;

        try {
            $laboratorios = (new LaboratorioModel())->listActive();
            $tiposProblema = (new TipoProblemaModel())->listActive();

            $idLaboratorioAtual = (int)($input['id_laboratorio'] ?? $existing['id_laboratorio'] ?? 0);
            if ($editing && $idLaboratorioAtual > 0) {
                $equipamentos = (new EquipamentoModel())->listActiveByLaboratorio($idLaboratorioAtual);
            }
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Form data error: ' . $exception->getMessage());
            $warning = 'Nao foi possivel carregar os dados de apoio.';
        }

        $ocorrencia = $editing
            ? array_merge($existing ?? [], $input ?? [])
            : ($input !== null ? $input : null);

        if ($editing) {
            $this->render('ocorrencias/edit', [
                'laboratorios' => $laboratorios,
                'equipamentos' => $equipamentos,
                'tipos_problema' => $tiposProblema,
                'tiposProblema' => $tiposProblema,
                'ocorrencia' => $ocorrencia,
                'erros' => $errors,
                'errors' => $errors,
                'warning' => $warning,
            ], false);
            return;
        }

        $this->render('ocorrencias/create', [
            'laboratorios' => $laboratorios,
            'tiposProblema' => $tiposProblema,
            'ocorrencia' => $ocorrencia,
            'podeEditar' => $editing ? $this->podeEditar($existing ?? []) : true,
            'errors' => $errors,
            'warning' => $warning,
        ], false);
    }

    /** @return array<string, string> */
    private function filtros(): array
    {
        return [
            'status' => trim((string)($_GET['status'] ?? '')),
            'id_laboratorio' => trim((string)($_GET['id_laboratorio'] ?? '')),
            'data_inicio' => trim((string)($_GET['data_inicio'] ?? '')),
            'data_fim' => trim((string)($_GET['data_fim'] ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return [
            'id_laboratorio' => trim((string)($_POST['id_laboratorio'] ?? '')),
            'id_equipamento' => trim((string)($_POST['id_equipamento'] ?? '')),
            'id_tipo_problema' => trim((string)($_POST['id_tipo_problema'] ?? '')),
            'descricao' => trim((string)($_POST['descricao'] ?? '')),
        ];
    }

    /** @return array<string, string> */
    private function rules(): array
    {
        return [
            'id_laboratorio' => 'required|numeric|exists_active:laboratorios,id',
            'id_equipamento' => 'numeric|exists_active:equipamentos,id',
            'id_tipo_problema' => 'required|numeric|exists_active:tipos_problema,id',
            'descricao' => 'required|min:10|max:5000',
        ];
    }

    /** @return array<string, string> */
    private function labels(): array
    {
        return [
            'id_laboratorio' => 'Laboratorio',
            'id_equipamento' => 'Equipamento',
            'id_tipo_problema' => 'Tipo de problema',
            'descricao' => 'Descricao',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function payload(array $input): array
    {
        return [
            'id_professor' => $this->usuarioId(),
            'id_laboratorio' => (int)$input['id_laboratorio'],
            'id_equipamento' => trim((string)($input['id_equipamento'] ?? '')) === ''
                ? null
                : (int)$input['id_equipamento'],
            'id_tipo_problema' => (int)$input['id_tipo_problema'],
            'descricao' => trim((string)$input['descricao']),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateEquipamentoLaboratorio(array $input): array
    {
        $idEquipamento = trim((string)($input['id_equipamento'] ?? ''));
        if ($idEquipamento === '') {
            return [];
        }

        $idLaboratorio = (int)($input['id_laboratorio'] ?? 0);
        $equipamento = (new EquipamentoModel())->findById((int)$idEquipamento);

        if (
            $equipamento === false
            || (int)$equipamento['ativo'] !== 1
            || (int)$equipamento['laboratorio_id'] !== $idLaboratorio
        ) {
            return ['id_equipamento' => 'Equipamento nao pertence ao laboratorio selecionado.'];
        }

        return [];
    }

    /** @return array<string, mixed> */
    private function findVisibleOrAbort(int $id): array
    {
        if ($id <= 0) {
            $this->abort(404);
        }

        try {
            $ocorrencia = $this->model->findById($id);
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Find error: ' . $exception->getMessage());
            $this->abort(500);
        }

        if ($ocorrencia === false) {
            $this->abort(404);
        }

        if ($this->usuarioPerfil() === 'professor' && (int)$ocorrencia['id_professor'] !== $this->usuarioId()) {
            $this->abort(403);
        }

        return $ocorrencia;
    }

    /** @param array<string, mixed> $ocorrencia */
    private function podeEditar(array $ocorrencia): bool
    {
        return $this->usuarioPerfil() === 'professor'
            && (int)($ocorrencia['id_professor'] ?? 0) === $this->usuarioId()
            && (string)($ocorrencia['status'] ?? '') === 'Nao Atendida';
    }

    private function ensureProfessor(): void
    {
        if ($this->usuarioPerfil() !== 'professor') {
            $this->abort(403);
        }
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

    private function flashSuccess(string $message): void
    {
        $_SESSION['flash_success'] = $message;
        SessionHelper::flash('success', $message);
    }

    private function flashError(string $message): void
    {
        $_SESSION['flash_error'] = $message;
        SessionHelper::flash('danger', $message);
    }

    private function notificarGestoresNovaOcorrencia(int $idOcorrencia): void
    {
        // ── INÍCIO CORREÇÃO QA ──
        try {
            $ocorrencia = $this->model->findById($idOcorrencia);
            if ($ocorrencia === false) {
                return;
            }

            $gestores = (new UsuarioModel())->listActiveByPerfil('gestor');
            foreach ($gestores as $gestor) {
                MailService::enviarNovaOcorrencia((string)$gestor['email'], $ocorrencia);
            }
        } catch (Throwable $exception) {
            error_log('[OcorrenciaController] Mail nova ocorrencia error: ' . $exception->getMessage());
        }
        // ── FIM CORREÇÃO QA ──
    }

    /**
     * Fallback para ambientes onde views/ocorrencias/show.php ainda nao foi entregue.
     *
     * @param array<string, mixed> $ocorrencia
     * @param array<int, array<string, mixed>> $historico
     */
    private function renderInlineShow(array $ocorrencia, array $historico): void
    {
        $pageTitle = 'Detalhe da Ocorrencia';
        $activeRoute = 'ocorrencia';
        $h = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        include $this->viewsPath . '/layouts/header.php';
        ?>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <h1 class="h4 mb-1">Ocorrencia #OC-<?= $h(str_pad((string)(int)$ocorrencia['id'], 3, '0', STR_PAD_LEFT)) ?></h1>
            <p class="text-muted mb-0"><?= $h($ocorrencia['laboratorio_nome'] ?? '-') ?></p>
          </div>
          <a href="/ocorrencia" class="btn btn-outline-secondary" style="border-radius:8px">Voltar</a>
        </div>

        <div class="sr-card card mb-3">
          <div class="sr-card-header">
            <h2 class="sr-card-title h6">Dados do chamado</h2>
          </div>
          <div class="p-3">
            <dl class="row mb-0">
              <dt class="col-sm-3">Status</dt>
              <dd class="col-sm-9"><?= $h($ocorrencia['status'] ?? '-') ?></dd>
              <dt class="col-sm-3">Tipo</dt>
              <dd class="col-sm-9"><?= $h($ocorrencia['tipo_problema_desc'] ?? $ocorrencia['tipo_problema_nome'] ?? '-') ?></dd>
              <dt class="col-sm-3">Equipamento</dt>
              <dd class="col-sm-9"><?= $h($ocorrencia['equipamento_nome'] ?? '-') ?></dd>
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
            <h2 class="sr-card-title h6">Historico</h2>
          </div>
          <div class="table-responsive">
            <table class="sr-table table mb-0">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Usuario</th>
                  <th>Anterior</th>
                  <th>Novo</th>
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
