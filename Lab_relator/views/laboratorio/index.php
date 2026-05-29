<?php
$laboratorio = $laboratorio ?? null;
$laboratorios = $laboratorios ?? [];
$pagination = $pagination ?? ['total' => 0, 'pagina' => 1, 'porPagina' => 20];
$errors = $errors ?? [];
$busca = (string)($busca ?? '');
$status = (string)($status ?? 'ativos');
$isEdit = !empty($laboratorio['id']);
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
    <h1 class="h4 mb-1">Laboratorios</h1>
    <p class="text-muted mb-0">Cadastre, edite e ative ou desative laboratorios.</p>
  </div>
  <a href="/laboratorio/novo" class="btn btn-sr">
    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Novo
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?>" aria-hidden="true"></i>
          <?= $isEdit ? 'Editar Laboratorio' : 'Novo Laboratorio' ?>
        </h2>
      </div>
      <div class="p-3">
        <form action="<?= $isEdit ? '/laboratorio/' . (int)$laboratorio['id'] . '/atualizar' : '/laboratorio/salvar' ?>" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">

          <div class="mb-3">
            <label for="lab-nome" class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= $fieldClass('nome') ?>" id="lab-nome" name="nome" maxlength="100" required
                   value="<?= $h($laboratorio['nome'] ?? '') ?>" placeholder="Ex.: Lab Informatica 1">
            <?= $fieldError('nome') ?>
          </div>

          <div class="mb-3">
            <label for="lab-bloco" class="form-label">Bloco/local</label>
            <input type="text" class="form-control<?= $fieldClass('bloco') ?>" id="lab-bloco" name="bloco" maxlength="50"
                   value="<?= $h($laboratorio['bloco'] ?? '') ?>" placeholder="Ex.: Bloco B">
            <?= $fieldError('bloco') ?>
          </div>

          <div class="mb-3">
            <label for="lab-capacidade" class="form-label">Capacidade</label>
            <input type="number" class="form-control<?= $fieldClass('capacidade') ?>" id="lab-capacidade" name="capacidade" min="1"
                   value="<?= $h($laboratorio['capacidade'] ?? '') ?>" placeholder="Ex.: 30">
            <?= $fieldError('capacidade') ?>
          </div>

          <div class="mb-3">
            <label for="lab-descricao" class="form-label">Descricao</label>
            <textarea class="form-control<?= $fieldClass('descricao') ?>" id="lab-descricao" name="descricao" rows="3" maxlength="1000"
                      placeholder="Observacoes do laboratorio"><?= $h($laboratorio['descricao'] ?? '') ?></textarea>
            <?= $fieldError('descricao') ?>
          </div>

          <div class="mb-3">
            <label for="lab-ativo" class="form-label">Status</label>
            <select class="form-select<?= $fieldClass('ativo') ?>" id="lab-ativo" name="ativo">
              <option value="1" <?= (string)($laboratorio['ativo'] ?? '1') === '1' ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= (string)($laboratorio['ativo'] ?? '1') === '0' ? 'selected' : '' ?>>Inativo</option>
            </select>
            <?= $fieldError('ativo') ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <a href="/laboratorio" class="btn btn-outline-secondary" style="border-radius:8px" aria-label="Cancelar">
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
          <i class="bi bi-list-ul" aria-hidden="true"></i>
          Laboratorios Cadastrados
        </h2>
        <form action="/laboratorio" method="GET" class="d-flex align-items-center gap-2" role="search">
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
        <table class="sr-table table mb-0" aria-label="Lista de laboratorios">
          <thead>
            <tr>
              <th>#</th>
              <th>Nome</th>
              <th>Bloco/local</th>
              <th>Cap.</th>
              <th>Status</th>
              <th><span class="visually-hidden">Acoes</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($laboratorios === []): ?>
              <tr>
                <td colspan="6">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <p>Nenhum laboratorio encontrado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($laboratorios as $lab): ?>
                <tr>
                  <td><span class="text-muted" style="font-size:11px">#<?= str_pad((string)(int)$lab['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                  <td>
                    <strong style="font-family:'Poppins',sans-serif;font-size:13px"><?= $h($lab['nome']) ?></strong>
                    <?php if (!empty($lab['descricao'])): ?>
                      <div class="text-muted" style="font-size:11px"><?= $h(mb_strimwidth((string)$lab['descricao'], 0, 52, '...', 'UTF-8')) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= $h($lab['bloco'] ?? '-') ?></td>
                  <td><?= !empty($lab['capacidade']) ? (int)$lab['capacidade'] : '-' ?></td>
                  <td>
                    <span class="badge <?= (int)$lab['ativo'] === 1 ? 'badge-enc' : 'badge-na' ?>">
                      <?= (int)$lab['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="/laboratorio/<?= (int)$lab['id'] ?>/editar" class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px" aria-label="Editar laboratorio">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="/laboratorio/<?= (int)$lab['id'] ?>/toggle" method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">
                      <button type="submit"
                              class="btn btn-sm <?= (int)$lab['ativo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              data-confirm="Confirma alterar o status deste laboratorio?"
                              aria-label="<?= (int)$lab['ativo'] === 1 ? 'Desativar' : 'Reativar' ?> laboratorio">
                        <i class="bi <?= (int)$lab['ativo'] === 1 ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
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
        <small class="text-muted"><?= (int)$pagination['total'] ?> laboratorio(s)</small>
        <?php if ($totalPaginas > 1): ?>
          <nav aria-label="Paginacao de laboratorios">
            <ul class="pagination pagination-sm mb-0">
              <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <?php $query = http_build_query(['pagina' => $p, 'busca' => $busca, 'status' => $status]); ?>
                <li class="page-item <?= $p === (int)$pagination['pagina'] ? 'active' : '' ?>">
                  <a class="page-link" href="/laboratorio?<?= $h($query) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
