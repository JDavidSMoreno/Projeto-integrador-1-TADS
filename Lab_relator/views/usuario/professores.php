<?php
$professor = $professor ?? null;
$professores = $professores ?? [];
$pagination = $pagination ?? ['total' => 0, 'pagina' => 1, 'porPagina' => 20];
$errors = $errors ?? [];
$busca = (string)($busca ?? '');
$status = (string)($status ?? 'ativos');
$routeBase = (string)($routeBase ?? '/usuario/professor');
$isEdit = !empty($professor['id']);
$h = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8') . '</div>'
    : '';
$totalPaginas = (int)ceil(((int)$pagination['total']) / max(1, (int)$pagination['porPagina']));
?>

<?php if (!empty($warning)): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
    <div><?= $h($warning) ?></div>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1">Professores</h1>
    <p class="text-muted mb-0">Gerencie usuarios com perfil de professor.</p>
  </div>
  <a href="<?= $h($routeBase) ?>/novo" class="btn btn-sr">
    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Novo
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?>" aria-hidden="true"></i>
          <?= $isEdit ? 'Editar Professor' : 'Novo Professor' ?>
        </h2>
      </div>
      <div class="p-3">
        <form action="<?= $isEdit ? $routeBase . '/' . (int)$professor['id'] . '/atualizar' : $routeBase . '/salvar' ?>" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">

          <div class="mb-3">
            <label for="prof-nome" class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= $fieldClass('nome') ?>" id="prof-nome" name="nome" maxlength="100" required
                   value="<?= $h($professor['nome'] ?? '') ?>" placeholder="Ex.: Carlos Souza">
            <?= $fieldError('nome') ?>
          </div>

          <div class="mb-3">
            <label for="prof-email" class="form-label">E-mail <span class="text-danger">*</span></label>
            <input type="email" class="form-control<?= $fieldClass('email') ?>" id="prof-email" name="email" maxlength="150" required
                   value="<?= $h($professor['email'] ?? '') ?>" placeholder="professor@unieinstein.edu.br">
            <?= $fieldError('email') ?>
          </div>

          <div class="mb-3">
            <label for="prof-senha" class="form-label"><?= $isEdit ? 'Nova senha' : 'Senha inicial' ?><?= $isEdit ? '' : ' <span class="text-danger">*</span>' ?></label>
            <div class="input-group">
              <input type="password" class="form-control<?= $fieldClass('senha') ?>" id="prof-senha" name="senha" minlength="8" maxlength="255" <?= $isEdit ? '' : 'required' ?>
                     autocomplete="new-password" placeholder="<?= $isEdit ? 'Deixe em branco para manter' : 'Minimo 8 caracteres' ?>" style="border-radius:8px 0 0 8px">
              <button type="button" class="btn btn-outline-secondary sr-toggle-pw" data-target="prof-senha" aria-label="Mostrar ou ocultar senha" style="border-radius:0 8px 8px 0;border-color:#dce3ea">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <?= $fieldError('senha') ?>
          </div>

          <div class="mb-3">
            <label for="prof-ativo" class="form-label">Status</label>
            <select class="form-select<?= $fieldClass('ativo') ?>" id="prof-ativo" name="ativo">
              <option value="1" <?= (string)($professor['ativo'] ?? '1') === '1' ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= (string)($professor['ativo'] ?? '1') === '0' ? 'selected' : '' ?>>Inativo</option>
            </select>
            <?= $fieldError('ativo') ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <a href="<?= $h($routeBase) ?>" class="btn btn-outline-secondary" style="border-radius:8px" aria-label="Cancelar">
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="sr-card card">
      <div class="sr-card-header flex-wrap gap-2">
        <h2 class="sr-card-title h6">
          <i class="bi bi-people" aria-hidden="true"></i>
          Professores Cadastrados
        </h2>
        <form action="<?= $h($routeBase) ?>" method="GET" class="d-flex align-items-center gap-2" role="search">
          <select class="form-select form-select-sm" name="status" style="width:120px;border-radius:8px" onchange="this.form.submit()">
            <option value="ativos" <?= $status === 'ativos' ? 'selected' : '' ?>>Ativos</option>
            <option value="inativos" <?= $status === 'inativos' ? 'selected' : '' ?>>Inativos</option>
            <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos</option>
          </select>
          <div class="input-group" style="max-width:230px">
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input type="search" class="form-control" name="busca" placeholder="Buscar..." value="<?= $h($busca) ?>">
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de professores">
          <thead>
            <tr>
              <th>#</th>
              <th>Professor</th>
              <th>E-mail</th>
              <th>Ocorrencias</th>
              <th>Status</th>
              <th><span class="visually-hidden">Acoes</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($professores === []): ?>
              <tr>
                <td colspan="6">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <p>Nenhum professor encontrado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($professores as $prof): ?>
                <?php
                $partes = explode(' ', (string)$prof['nome']);
                $iniciais = strtoupper(substr($partes[0] ?? '', 0, 1) . substr($partes[1] ?? '', 0, 1));
                ?>
                <tr>
                  <td><span class="text-muted" style="font-size:11px">#<?= str_pad((string)(int)$prof['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="sr-avatar" style="width:30px;height:30px;font-size:11px" aria-hidden="true"><?= $h($iniciais) ?></div>
                      <strong style="font-family:'Poppins',sans-serif;font-size:13px"><?= $h($prof['nome']) ?></strong>
                    </div>
                  </td>
                  <td><?= $h($prof['email']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= (int)($prof['total_ocorrencias'] ?? 0) ?></span></td>
                  <td>
                    <span class="badge <?= (int)$prof['ativo'] === 1 ? 'badge-enc' : 'badge-na' ?>">
                      <?= (int)$prof['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="<?= $h($routeBase) ?>/<?= (int)$prof['id'] ?>/editar" class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px" aria-label="Editar professor">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="<?= $h($routeBase) ?>/<?= (int)$prof['id'] ?>/toggle" method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">
                      <button type="submit"
                              class="btn btn-sm <?= (int)$prof['ativo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              data-confirm="Confirma alterar o status deste professor?"
                              aria-label="<?= (int)$prof['ativo'] === 1 ? 'Desativar' : 'Reativar' ?> professor">
                        <i class="bi <?= (int)$prof['ativo'] === 1 ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
        <small class="text-muted"><?= (int)$pagination['total'] ?> professor(es)</small>
        <?php if ($totalPaginas > 1): ?>
          <nav aria-label="Paginacao de professores">
            <ul class="pagination pagination-sm mb-0">
              <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <?php $query = http_build_query(['pagina' => $p, 'busca' => $busca, 'status' => $status]); ?>
                <li class="page-item <?= $p === (int)$pagination['pagina'] ? 'active' : '' ?>">
                  <a class="page-link" href="<?= $h($routeBase) ?>?<?= $h($query) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
