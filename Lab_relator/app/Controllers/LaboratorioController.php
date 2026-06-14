<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Helpers\Validator;
use App\Models\LaboratorioModel;
use Throwable;

final class LaboratorioController extends BaseController
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
        $laboratorio = $this->findOrAbort($id);

        $this->renderIndex($laboratorio);
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
            (new LaboratorioModel())->insert($this->payload($input));
            SessionHelper::flash('success', 'Laboratorio cadastrado com sucesso.');
            $this->redirect('/laboratorio');
        } catch (Throwable $exception) {
            error_log('[LaboratorioController] Save error: ' . $exception->getMessage());
            $this->renderIndex($input, ['geral' => 'Nao foi possivel salvar o laboratorio.']);
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
            (new LaboratorioModel())->update($id, $this->payload($input));
            SessionHelper::flash('success', 'Laboratorio atualizado com sucesso.');
            $this->redirect('/laboratorio');
        } catch (Throwable $exception) {
            error_log('[LaboratorioController] Update error: ' . $exception->getMessage());
            $this->renderIndex(array_merge($existing, $input), ['geral' => 'Nao foi possivel atualizar o laboratorio.']);
        }
    }

    /** @param array<string, string> $params */
    public function toggle(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $laboratorio = $this->findOrAbort($id);

        try {
            (new LaboratorioModel())->toggleActive($id);
            $state = (int)$laboratorio['ativo'] === 1 ? 'desativado' : 'reativado';
            SessionHelper::flash('success', 'Laboratorio ' . $state . ' com sucesso.');
        } catch (Throwable $exception) {
            error_log('[LaboratorioController] Toggle error: ' . $exception->getMessage());
            SessionHelper::flash('danger', 'Nao foi possivel alterar o status do laboratorio.');
        }

        $this->redirect('/laboratorio');
    }

    /**
     * @param array<string, mixed>|null $laboratorio
     * @param array<string, string> $errors
     */
    private function renderIndex(?array $laboratorio = null, array $errors = []): void
    {
        $busca = trim((string)($_GET['busca'] ?? ''));
        $status = $this->statusFilter();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $pagination = ['items' => [], 'total' => 0, 'pagina' => $pagina, 'porPagina' => 20];
        $warning = null;

        try {
            $pagination = (new LaboratorioModel())->paginate($busca, $status, $pagina, 20);
        } catch (Throwable $exception) {
            error_log('[LaboratorioController] Index error: ' . $exception->getMessage());
            $warning = 'Nao foi possivel carregar laboratorios. Verifique o banco e o arquivo database/schema.sql.';
        }

        $this->render('laboratorio/index', [
            'pageTitle' => 'Laboratorios',
            'activeRoute' => 'laboratorio',
            'laboratorio' => $laboratorio,
            'laboratorios' => $pagination['items'],
            'pagination' => $pagination,
            'busca' => $busca,
            'status' => $status,
            'errors' => $errors,
            'warning' => $warning,
        ]);
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'bloco' => trim((string)($_POST['bloco'] ?? $_POST['localizacao'] ?? '')),
            'capacidade' => trim((string)($_POST['capacidade'] ?? '')),
            'descricao' => trim((string)($_POST['descricao'] ?? '')),
            'ativo' => in_array((string)($_POST['ativo'] ?? '1'), ['0', '1'], true) ? (string)$_POST['ativo'] : '1',
        ];
    }

    /** @return array<string, string> */
    private function rules(): array
    {
        return [
            'nome' => 'required|min:3|max:100',
            'bloco' => 'max:50',
            'capacidade' => 'numeric',
            'descricao' => 'max:1000',
            'ativo' => 'required|in:0,1',
        ];
    }

    /** @return array<string, string> */
    private function labels(): array
    {
        return [
            'nome' => 'Nome',
            'bloco' => 'Bloco/local',
            'capacidade' => 'Capacidade',
            'descricao' => 'Descricao',
            'ativo' => 'Status',
        ];
    }

    /** @param array<string, mixed> $input */
    private function payload(array $input): array
    {
        return [
            'nome' => trim((string)$input['nome']),
            'bloco' => trim((string)($input['bloco'] ?? '')) ?: null,
            'capacidade' => ($input['capacidade'] ?? '') === '' ? null : (int)$input['capacidade'],
            'descricao' => trim((string)($input['descricao'] ?? '')) ?: null,
            'ativo' => (int)($input['ativo'] ?? 1),
        ];
    }

    private function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            $this->abort(404);
        }

        try {
            $laboratorio = (new LaboratorioModel())->findById($id);
        } catch (Throwable $exception) {
            error_log('[LaboratorioController] Find error: ' . $exception->getMessage());
            $this->abort(500);
        }

        if ($laboratorio === false) {
            $this->abort(404);
        }

        return $laboratorio;
    }

    private function statusFilter(): string
    {
        $status = (string)($_GET['status'] ?? 'ativos');

        return in_array($status, ['ativos', 'inativos', 'todos'], true) ? $status : 'ativos';
    }
}
