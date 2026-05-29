<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Helpers\Validator;
use App\Models\TipoProblemaModel;
use Throwable;

final class TipoProblemaController extends BaseController
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
        $tipo = $this->findOrAbort($id);

        $this->renderIndex($tipo);
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
            (new TipoProblemaModel())->insert($this->payload($input));
            SessionHelper::flash('success', 'Tipo de problema cadastrado com sucesso.');
            $this->redirect('/tipo-problema');
        } catch (Throwable $exception) {
            error_log('[TipoProblemaController] Save error: ' . $exception->getMessage());
            $this->renderIndex($input, ['geral' => 'Nao foi possivel salvar o tipo de problema.']);
        }
    }

    /** @param array<string, string> $params */
    public function atualizar(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $existing = $this->findOrAbort($id);
        $input = ['id' => $id] + $this->input();
        $errors = Validator::validar($input, $this->rules($id), $this->labels());

        if ($errors !== []) {
            $this->renderIndex(array_merge($existing, $input), $errors);
            return;
        }

        try {
            (new TipoProblemaModel())->update($id, $this->payload($input));
            SessionHelper::flash('success', 'Tipo de problema atualizado com sucesso.');
            $this->redirect('/tipo-problema');
        } catch (Throwable $exception) {
            error_log('[TipoProblemaController] Update error: ' . $exception->getMessage());
            $this->renderIndex(array_merge($existing, $input), ['geral' => 'Nao foi possivel atualizar o tipo de problema.']);
        }
    }

    /** @param array<string, string> $params */
    public function toggle(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $tipo = $this->findOrAbort($id);

        try {
            (new TipoProblemaModel())->toggleActive($id);
            $state = (int)$tipo['ativo'] === 1 ? 'desativado' : 'reativado';
            SessionHelper::flash('success', 'Tipo de problema ' . $state . ' com sucesso.');
        } catch (Throwable $exception) {
            error_log('[TipoProblemaController] Toggle error: ' . $exception->getMessage());
            SessionHelper::flash('danger', 'Nao foi possivel alterar o status do tipo de problema.');
        }

        $this->redirect('/tipo-problema');
    }

    /**
     * @param array<string, mixed>|null $tipo
     * @param array<string, string> $errors
     */
    private function renderIndex(?array $tipo = null, array $errors = []): void
    {
        $busca = trim((string)($_GET['busca'] ?? ''));
        $status = $this->statusFilter();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $pagination = ['items' => [], 'total' => 0, 'pagina' => $pagina, 'porPagina' => 20];
        $warning = null;

        try {
            $pagination = (new TipoProblemaModel())->paginate($busca, $status, $pagina, 20);
        } catch (Throwable $exception) {
            error_log('[TipoProblemaController] Index error: ' . $exception->getMessage());
            $warning = 'Nao foi possivel carregar tipos de problema. Verifique o banco e o schema da Fase 3.';
        }

        $this->render('tipo-problema/index', [
            'pageTitle' => 'Tipos de Problema',
            'activeRoute' => 'tipo-problema',
            'tipo' => $tipo,
            'tipos' => $pagination['items'],
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
            'nome' => trim((string)($_POST['nome'] ?? $_POST['descricao'] ?? '')),
            'descricao' => trim((string)($_POST['descricao_detalhada'] ?? $_POST['observacao'] ?? '')),
            'ativo' => in_array((string)($_POST['ativo'] ?? '1'), ['0', '1'], true) ? (string)$_POST['ativo'] : '1',
        ];
    }

    /** @return array<string, string> */
    private function rules(?int $exceptId = null): array
    {
        return [
            'nome' => 'required|min:3|max:100|unique:tipos_problema,nome,' . ($exceptId ?? ''),
            'descricao' => 'max:1000',
            'ativo' => 'required|in:0,1',
        ];
    }

    /** @return array<string, string> */
    private function labels(): array
    {
        return [
            'nome' => 'Nome',
            'descricao' => 'Descricao',
            'ativo' => 'Status',
        ];
    }

    /** @param array<string, mixed> $input */
    private function payload(array $input): array
    {
        return [
            'nome' => trim((string)$input['nome']),
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
            $tipo = (new TipoProblemaModel())->findById($id);
        } catch (Throwable $exception) {
            error_log('[TipoProblemaController] Find error: ' . $exception->getMessage());
            $this->abort(500);
        }

        if ($tipo === false) {
            $this->abort(404);
        }

        return $tipo;
    }

    private function statusFilter(): string
    {
        $status = (string)($_GET['status'] ?? 'ativos');

        return in_array($status, ['ativos', 'inativos', 'todos'], true) ? $status : 'ativos';
    }
}
