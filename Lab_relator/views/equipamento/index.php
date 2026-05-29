<?php
$equipamento = $equipamento ?? null;
$equipamentos = $equipamentos ?? [];
$laboratorios = $laboratorios ?? [];
$pagination = $pagination ?? ['total' => 0, 'pagina' => 1, 'porPagina' => 20];
$errors = $errors ?? [];
$busca = (string)($busca ?? '');
$status = (string)($status ?? 'ativos');
$filtroLab = (int)($filtroLab ?? 0);
$isEdit = !empty($equipamento['id']);
$h = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$fieldClass = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8') . '</div>'
    : '';
$totalPaginas = (int)ceil(((int)$pagination['total']) / max(1, (int)$pagination['porPagina']));
$statusLabels = [
    'disponivel' => 'Disponivel',
    'em_manutencao' => 'Em manutencao',
    'inutilizavel' => 'Inutilizavel',
];
?>

<?php if (!empty($warning)): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
    <div><?= $h($warning) ?></div>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1">Equipamentos</h1>
    <p class="text-muted mb-0">Gerencie equipamentos vinculados aos laboratorios.</p>
  </div>
  <a href="/equipamento/novo" class="btn btn-sr">
    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Novo
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?>" aria-hidden="true"></i>
          <?= $isEdit ? 'Editar Equipamento' : 'Novo Equipamento' ?>
        </h2>
      </div>
      <div class="p-3">
        <form action="<?= $isEdit ? '/equipamento/' . (int)$equipamento['id'] . '/atualizar' : '/equipamento/salvar' ?>" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">

          <div class="mb-3">
            <label for="eq-lab" class="form-label">Laboratorio <span class="text-danger">*</span></label>
            <select class="form-select<?= $fieldClass('laboratorio_id') ?>" id="eq-lab" name="laboratorio_id" required>
              <option value="">Selecione...</option>
              <?php foreach ($laboratorios as $lab): ?>
                <option value="<?= (int)$lab['id'] ?>" <?= (string)($equipamento['laboratorio_id'] ?? '') === (string)$lab['id'] ? 'selected' : '' ?>>
                  <?= $h($lab['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?= $fieldError('laboratorio_id') ?>
          </div>

          <div class="mb-3">
            <label for="eq-nome" class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= $fieldClass('nome') ?>" id="eq-nome" name="nome" maxlength="100" required
                   value="<?= $h($equipamento['nome'] ?? '') ?>" placeholder="Ex.: Computador Dell Optiplex">
            <?= $fieldError('nome') ?>
          </div>

          <div class="mb-3">
            <label for="eq-patrimonio" class="form-label">Patrimonio</label>
            <input type="text" class="form-control<?= $fieldClass('patrimonio') ?>" id="eq-patrimonio" name="patrimonio" maxlength="50"
                   value="<?= $h($equipamento['patrimonio'] ?? '') ?>" placeholder="Ex.: PAT-2026-001">
            <?= $fieldError('patrimonio') ?>
          </div>

          <div class="mb-3">
            <label for="eq-tipo" class="form-label">Tipo</label>
            <input type="text" class="form-control<?= $fieldClass('tipo') ?>" id="eq-tipo" name="tipo" maxlength="50"
                   value="<?= $h($equipamento['tipo'] ?? '') ?>" placeholder="Ex.: Desktop, Monitor, Projetor">
            <?= $fieldError('tipo') ?>
          </div>

          <div class="mb-3">
            <label for="eq-status" class="form-label">Condicao</label>
            <select class="form-select<?= $fieldClass('status') ?>" id="eq-status" name="status">
              <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= $h($value) ?>" <?= (string)($equipamento['status'] ?? 'disponivel') === $value ? 'selected' : '' ?>>
                  <?= $h($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?= $fieldError('status') ?>
          </div>

          <div class="mb-3">
            <label for="eq-observacao" class="form-label">Observacao</label>
            <textarea class="form-control<?= $fieldClass('observacao') ?>" id="eq-observacao" name="observacao" rows="3" maxlength="1000"
                      placeholder="Detalhes tecnicos ou observacoes"><?= $h($equipamento['observacao'] ?? '') ?></textarea>
            <?= $fieldError('observacao') ?>
          </div>

          <div class="mb-3">
            <label for="eq-ativo" class="form-label">Status do cadastro</label>
            <select class="form-select<?= $fieldClass('ativo') ?>" id="eq-ativo" name="ativo">
              <option value="1" <?= (string)($equipamento['ativo'] ?? '1') === '1' ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= (string)($equipamento['ativo'] ?? '1') === '0' ? 'selected' : '' ?>>Inativo</option>
            </select>
            <?= $fieldError('ativo') ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <a href="/equipamento" class="btn btn-outline-secondary" style="border-radius:8px" aria-label="Cancelar">
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
          Equipamentos Cadastrados
        </h2>
        <form action="/equipamento" method="GET" class="d-flex align-items-center gap-2" role="search">
          <select class="form-select form-select-sm" name="laboratorio" style="width:180px;border-radius:8px" onchange="this.form.submit()">
            <option value="">Todos os labs</option>
            <?php foreach ($laboratorios as $lab): ?>
              <option value="<?= (int)$lab['id'] ?>" <?= $filtroLab === (int)$lab['id'] ? 'selected' : '' ?>>
                <?= $h($lab['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <select class="form-select form-select-sm" name="status" style="width:120px;border-radius:8px" onchange="this.form.submit()">
            <option value="ativos" <?= $status === 'ativos' ? 'selected' : '' ?>>Ativos</option>
            <option value="inativos" <?= $status === 'inativos' ? 'selected' : '' ?>>Inativos</option>
            <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos</option>
          </select>
          <div class="input-group" style="max-width:210px">
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input type="search" class="form-control" name="busca" placeholder="Buscar..." value="<?= $h($busca) ?>">
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de equipamentos">
          <thead>
            <tr>
              <th>#</th>
              <th>Equipamento</th>
              <th>Laboratorio</th>
              <th>Patrimonio</th>
              <th>Condicao</th>
              <th>Status</th>
              <th><span class="visually-hidden">Acoes</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($equipamentos === []): ?>
              <tr>
                <td colspan="7">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-pc-display" aria-hidden="true"></i>
                    <p>Nenhum equipamento encontrado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($equipamentos as $eq): ?>
                <tr>
                  <td><span class="text-muted" style="font-size:11px">#<?= str_pad((string)(int)$eq['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                  <td>
                    <strong style="font-family:'Poppins',sans-serif;font-size:13px"><?= $h($eq['nome']) ?></strong>
                    <?php if (!empty($eq['tipo'])): ?>
                      <div class="text-muted" style="font-size:11px"><?= $h($eq['tipo']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= $h($eq['laboratorio_nome'] ?? '-') ?></td>
                  <td><?= !empty($eq['patrimonio']) ? '<code style="font-size:11.5px">' . $h($eq['patrimonio']) . '</code>' : '<span class="text-muted">-</span>' ?></td>
                  <td><?= $h($statusLabels[$eq['status']] ?? $eq['status'] ?? '-') ?></td>
                  <td>
                    <span class="badge <?= (int)$eq['ativo'] === 1 ? 'badge-enc' : 'badge-na' ?>">
                      <?= (int)$eq['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="/equipamento/<?= (int)$eq['id'] ?>/editar" class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px" aria-label="Editar equipamento">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="/equipamento/<?= (int)$eq['id'] ?>/toggle" method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">
                      <button type="submit"
                              class="btn btn-sm <?= (int)$eq['ativo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              data-confirm="Confirma alterar o status deste equipamento?"
                              aria-label="<?= (int)$eq['ativo'] === 1 ? 'Desativar' : 'Reativar' ?> equipamento">
                        <i class="bi <?= (int)$eq['ativo'] === 1 ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
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
        <small class="text-muted"><?= (int)$pagination['total'] ?> equipamento(s)</small>
        <?php if ($totalPaginas > 1): ?>
          <nav aria-label="Paginacao de equipamentos">
            <ul class="pagination pagination-sm mb-0">
              <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <?php $query = http_build_query(['pagina' => $p, 'busca' => $busca, 'status' => $status, 'laboratorio' => $filtroLab]); ?>
                <li class="page-item <?= $p === (int)$pagination['pagina'] ? 'active' : '' ?>">
                  <a class="page-link" href="/equipamento?<?= $h($query) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
