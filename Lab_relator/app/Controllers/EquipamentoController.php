<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Helpers\Validator;
use App\Models\EquipamentoModel;
use App\Models\LaboratorioModel;
use Throwable;

final class EquipamentoController extends BaseController
{
    public function index(): void
    {
        $this->renderIndex();
    }

    public function novo(): void
    {
        $this->renderIndex();
    }

    /** @param array<string, string> $params */
    public function editar(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $equipamento = $this->findOrAbort($id);

        $this->renderIndex($equipamento);
    }

    public function salvar(): void
    {
        $input = $this->input();
        $errors = Validator::validar($input, $this->rules(), $this->labels());

        if ($errors !== []) {
            $this->renderIndex($input, $errors);
            return;
        }

        try {
            (new EquipamentoModel())->insert($this->payload($input));
            SessionHelper::flash('success', 'Equipamento cadastrado com sucesso.');
            $this->redirect('/equipamento');
        } catch (Throwable $exception) {
            error_log('[EquipamentoController] Save error: ' . $exception->getMessage());
            $this->renderIndex($input, ['geral' => 'Nao foi possivel salvar o equipamento.']);
        }
    }

    /** @param array<string, string> $params */
    public function atualizar(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $existing = $this->findOrAbort($id);
        $input = ['id' => $id] + $this->input();
        $errors = Validator::validar($input, $this->rules(), $this->labels());

        if ($errors !== []) {
            $this->renderIndex(array_merge($existing, $input), $errors);
            return;
        }

        try {
            (new EquipamentoModel())->update($id, $this->payload($input));
            SessionHelper::flash('success', 'Equipamento atualizado com sucesso.');
            $this->redirect('/equipamento');
        } catch (Throwable $exception) {
            error_log('[EquipamentoController] Update error: ' . $exception->getMessage());
            $this->renderIndex(array_merge($existing, $input), ['geral' => 'Nao foi possivel atualizar o equipamento.']);
        }
    }

    /** @param array<string, string> $params */
    public function toggle(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $equipamento = $this->findOrAbort($id);

        try {
            (new EquipamentoModel())->toggleActive($id);
            $state = (int)$equipamento['ativo'] === 1 ? 'desativado' : 'reativado';
            SessionHelper::flash('success', 'Equipamento ' . $state . ' com sucesso.');
        } catch (Throwable $exception) {
            error_log('[EquipamentoController] Toggle error: ' . $exception->getMessage());
            SessionHelper::flash('danger', 'Nao foi possivel alterar o status do equipamento.');
        }

        $this->redirect('/equipamento');
    }

    public function porLaboratorio(): void
    {
        $laboratorioId = (int)($_GET['laboratorio_id'] ?? $_GET['id_laboratorio'] ?? 0);

        if ($laboratorioId <= 0) {
            $this->json([], 200);
        }

        try {
            $this->json((new EquipamentoModel())->listActiveByLaboratorio($laboratorioId));
        } catch (Throwable $exception) {
            error_log('[EquipamentoController] JSON error: ' . $exception->getMessage());
            $this->json([], 500);
        }
    }

    /**
     * @param array<string, mixed>|null $equipamento
     * @param array<string, string> $errors
     */
    private function renderIndex(?array $equipamento = null, array $errors = []): void
    {
        $busca = trim((string)($_GET['busca'] ?? ''));
        $status = $this->statusFilter();
        $filtroLab = (int)($_GET['laboratorio'] ?? $_GET['laboratorio_id'] ?? 0);
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $pagination = ['items' => [], 'total' => 0, 'pagina' => $pagina, 'porPagina' => 20];
        $laboratorios = [];
        $warning = null;

        try {
            $laboratorios = (new LaboratorioModel())->listActive();
            $pagination = (new EquipamentoModel())->paginate($filtroLab > 0 ? $filtroLab : null, $busca, $status, $pagina, 20);
        } catch (Throwable $exception) {
            error_log('[EquipamentoController] Index error: ' . $exception->getMessage());
            $warning = 'Nao foi possivel carregar equipamentos. Verifique o banco e o schema da Fase 3.';
        }

        $this->render('equipamento/index', [
            'pageTitle' => 'Equipamentos',
            'activeRoute' => 'equipamento',
            'equipamento' => $equipamento,
            'equipamentos' => $pagination['items'],
            'laboratorios' => $laboratorios,
            'pagination' => $pagination,
            'busca' => $busca,
            'status' => $status,
            'filtroLab' => $filtroLab,
            'errors' => $errors,
            'warning' => $warning,
        ]);
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return [
            'laboratorio_id' => trim((string)($_POST['laboratorio_id'] ?? $_POST['id_laboratorio'] ?? '')),
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'patrimonio' => trim((string)($_POST['patrimonio'] ?? $_POST['numero_serie'] ?? '')),
            'tipo' => trim((string)($_POST['tipo'] ?? '')),
            'status' => (string)($_POST['status'] ?? 'disponivel'),
            'observacao' => trim((string)($_POST['observacao'] ?? $_POST['descricao'] ?? '')),
            'ativo' => in_array((string)($_POST['ativo'] ?? '1'), ['0', '1'], true) ? (string)$_POST['ativo'] : '1',
        ];
    }

    /** @return array<string, string> */
    private function rules(): array
    {
        return [
            'laboratorio_id' => 'required|numeric|exists_active:laboratorios,id',
            'nome' => 'required|min:3|max:100',
            'patrimonio' => 'max:50',
            'tipo' => 'max:50',
            'status' => 'required|in:disponivel,em_manutencao,inutilizavel',
            'observacao' => 'max:1000',
            'ativo' => 'required|in:0,1',
        ];
    }

    /** @return array<string, string> */
    private function labels(): array
    {
        return [
            'laboratorio_id' => 'Laboratorio',
            'nome' => 'Nome',
            'patrimonio' => 'Patrimonio',
            'tipo' => 'Tipo',
            'status' => 'Status',
            'observacao' => 'Observacao',
            'ativo' => 'Ativo',
        ];
    }

    /** @param array<string, mixed> $input */
    private function payload(array $input): array
    {
        return [
            'laboratorio_id' => (int)$input['laboratorio_id'],
            'nome' => trim((string)$input['nome']),
            'patrimonio' => trim((string)($input['patrimonio'] ?? '')) ?: null,
            'tipo' => trim((string)($input['tipo'] ?? '')) ?: null,
            'status' => (string)($input['status'] ?? 'disponivel'),
            'observacao' => trim((string)($input['observacao'] ?? '')) ?: null,
            'ativo' => (int)($input['ativo'] ?? 1),
        ];
    }

    private function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            $this->abort(404);
        }

        try {
            $equipamento = (new EquipamentoModel())->findById($id);
        } catch (Throwable $exception) {
            error_log('[EquipamentoController] Find error: ' . $exception->getMessage());
            $this->abort(500);
        }

        if ($equipamento === false) {
            $this->abort(404);
        }

        return $equipamento;
    }

    private function statusFilter(): string
    {
        $status = (string)($_GET['status'] ?? 'ativos');

        return in_array($status, ['ativos', 'inativos', 'todos'], true) ? $status : 'ativos';
    }
}
