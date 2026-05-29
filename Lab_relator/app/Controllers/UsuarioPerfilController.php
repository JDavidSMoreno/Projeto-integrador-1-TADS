<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SessionHelper;
use App\Helpers\Validator;
use App\Models\UsuarioModel;
use Throwable;

abstract class UsuarioPerfilController extends BaseController
{
    abstract protected function perfil(): string;

    abstract protected function itemKey(): string;

    abstract protected function listKey(): string;

    abstract protected function viewName(): string;

    abstract protected function routeBase(): string;

    abstract protected function pageTitle(): string;

    abstract protected function activeRoute(): string;

    abstract protected function label(): string;

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
        $usuario = $this->findOrAbort($id);

        $this->renderIndex($usuario);
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
            (new UsuarioModel())->createWithPerfil($input, $this->perfil());
            SessionHelper::flash('success', ucfirst($this->label()) . ' cadastrado com sucesso.');
            $this->redirect($this->routeBase());
        } catch (Throwable $exception) {
            error_log('[' . static::class . '] Save error: ' . $exception->getMessage());
            $this->renderIndex($input, ['geral' => 'Nao foi possivel salvar ' . $this->label() . '.']);
        }
    }

    /** @param array<string, string> $params */
    public function atualizar(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $existing = $this->findOrAbort($id);
        $input = ['id' => $id] + $this->input();
        $errors = Validator::validar($input, $this->rules($id, false), $this->labels());

        if ($errors !== []) {
            $this->renderIndex(array_merge($existing, $input), $errors);
            return;
        }

        try {
            (new UsuarioModel())->updatePerfil($id, $this->perfil(), $input);
            SessionHelper::flash('success', ucfirst($this->label()) . ' atualizado com sucesso.');
            $this->redirect($this->routeBase());
        } catch (Throwable $exception) {
            error_log('[' . static::class . '] Update error: ' . $exception->getMessage());
            $this->renderIndex(array_merge($existing, $input), ['geral' => 'Nao foi possivel atualizar ' . $this->label() . '.']);
        }
    }

    /** @param array<string, string> $params */
    public function toggle(array $params = []): void
    {
        $id = (int)($params['id'] ?? $_POST['id'] ?? 0);
        $usuario = $this->findOrAbort($id);

        try {
            (new UsuarioModel())->toggleActiveByPerfil($id, $this->perfil());
            $state = (int)$usuario['ativo'] === 1 ? 'desativado' : 'reativado';
            SessionHelper::flash('success', ucfirst($this->label()) . ' ' . $state . ' com sucesso.');
        } catch (Throwable $exception) {
            error_log('[' . static::class . '] Toggle error: ' . $exception->getMessage());
            SessionHelper::flash('danger', 'Nao foi possivel alterar o status de ' . $this->label() . '.');
        }

        $this->redirect($this->routeBase());
    }

    /**
     * @param array<string, mixed>|null $usuario
     * @param array<string, string> $errors
     */
    protected function renderIndex(?array $usuario = null, array $errors = []): void
    {
        $busca = trim((string)($_GET['busca'] ?? ''));
        $status = $this->statusFilter();
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $pagination = ['items' => [], 'total' => 0, 'pagina' => $pagina, 'porPagina' => 20];
        $warning = null;

        try {
            $pagination = (new UsuarioModel())->paginateByPerfil($this->perfil(), $busca, $status, $pagina, 20);
        } catch (Throwable $exception) {
            error_log('[' . static::class . '] Index error: ' . $exception->getMessage());
            $warning = 'Nao foi possivel carregar usuarios. Verifique o banco e o schema das Fases 2 e 3.';
        }

        $data = [
            'pageTitle' => $this->pageTitle(),
            'activeRoute' => $this->activeRoute(),
            $this->itemKey() => $usuario,
            $this->listKey() => $pagination['items'],
            'routeBase' => $this->routeBase(),
            'perfilCadastro' => $this->perfil(),
            'labelCadastro' => $this->label(),
            'pagination' => $pagination,
            'busca' => $busca,
            'status' => $status,
            'errors' => $errors,
            'warning' => $warning,
        ];

        $this->render($this->viewName(), $data);
    }

    /** @return array<string, mixed> */
    protected function input(): array
    {
        return [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'email' => mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8'),
            'senha' => (string)($_POST['senha'] ?? ''),
            'ativo' => in_array((string)($_POST['ativo'] ?? '1'), ['0', '1'], true) ? (string)$_POST['ativo'] : '1',
        ];
    }

    /** @return array<string, string> */
    protected function rules(?int $exceptId = null, bool $creating = true): array
    {
        $rules = [
            'nome' => 'required|min:3|max:100',
            'email' => 'required|email|max:150|unique:usuarios,email,' . ($exceptId ?? ''),
            'ativo' => 'required|in:0,1',
        ];

        $rules['senha'] = $creating ? 'required|min:8|max:255' : 'min:8|max:255';

        return $rules;
    }

    /** @return array<string, string> */
    protected function labels(): array
    {
        return [
            'nome' => 'Nome',
            'email' => 'E-mail',
            'senha' => 'Senha',
            'ativo' => 'Status',
        ];
    }

    protected function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            $this->abort(404);
        }

        try {
            $usuario = (new UsuarioModel())->findByPerfil($id, $this->perfil());
        } catch (Throwable $exception) {
            error_log('[' . static::class . '] Find error: ' . $exception->getMessage());
            $this->abort(500);
        }

        if ($usuario === false) {
            $this->abort(404);
        }

        return $usuario;
    }

    private function statusFilter(): string
    {
        $status = (string)($_GET['status'] ?? 'ativos');

        return in_array($status, ['ativos', 'inativos', 'todos'], true) ? $status : 'ativos';
    }
}
