<?php
/**
 * views/equipamento/index.php
 * CRUD de equipamentos (RF: CADASTRO DE EQUIPAMENTOS – seção 3.3).
 * Variáveis do EquipamentoController:
 *   array  $equipamentos  – lista de equipamentos
 *   array  $laboratorios  – para popular selects
 *   ?array $equipamento   – dados para edição (null = novo)
 *   ?int   $filtroLab     – laboratório filtrado na listagem
 */
$pageTitle   = 'Equipamentos';
$activeRoute = 'equipamento';
include __DIR__ . '/../layouts/header.php';

$equipamento  = $equipamento  ?? null;
$equipamentos = $equipamentos ?? [];
$laboratorios = $laboratorios ?? [];
$filtroLab    = (int)($filtroLab ?? 0);
?>

<div class="row g-3">

  <!-- ── Formulário ──────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $equipamento ? 'bi-pencil-square' : 'bi-plus-circle' ?>" aria-hidden="true"></i>
          <?= $equipamento ? 'Editar Equipamento' : 'Novo Equipamento' ?>
        </h2>
      </div>
      <div class="p-3">
        <form action="/equipamento/<?= $equipamento ? 'atualizar' : 'salvar' ?>"
              method="POST" novalidate id="form-equip"
              aria-label="<?= $equipamento ? 'Editar equipamento' : 'Cadastrar novo equipamento' ?>">

          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
          <?php if ($equipamento): ?>
            <input type="hidden" name="id" value="<?= (int)($equipamento['id'] ?? 0) ?>" />
          <?php endif; ?>

          <!-- Laboratório -->
          <div class="mb-3">
            <label for="eq-lab" class="form-label">
              Laboratório <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <select class="form-select" id="eq-lab" name="id_laboratorio"
                    required aria-required="true">
              <option value="">Selecione o laboratório...</option>
              <?php foreach ($laboratorios as $lab): ?>
                <option value="<?= (int)$lab['id'] ?>"
                  <?= ($equipamento['id_laboratorio'] ?? 0) == $lab['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback" role="alert">Selecione um laboratório.</div>
          </div>

          <!-- Nome -->
          <div class="mb-3">
            <label for="eq-nome" class="form-label">
              Nome do Equipamento <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="text" class="form-control" id="eq-nome" name="nome"
                   maxlength="100" required aria-required="true"
                   placeholder="Ex.: Computador Desktop"
                   value="<?= htmlspecialchars($equipamento['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="invalid-feedback" role="alert">O nome é obrigatório.</div>
          </div>

          <!-- Nº de Série -->
          <div class="mb-3">
            <label for="eq-serie" class="form-label">
              Nº de Série / Patrimônio
            </label>
            <input type="text" class="form-control" id="eq-serie" name="numero_serie"
                   maxlength="100" placeholder="Ex.: PAT-2024-0042"
                   aria-describedby="eq-serie-help"
                   value="<?= htmlspecialchars($equipamento['numero_serie'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div id="eq-serie-help" class="form-text">Deve ser único por equipamento.</div>
          </div>

          <!-- Descrição -->
          <div class="mb-3">
            <label for="eq-desc" class="form-label">Descrição / Especificações</label>
            <textarea class="form-control" id="eq-desc" name="descricao"
                      rows="2" maxlength="255"
                      placeholder="Ex.: Intel Core i5, 8 GB RAM, SSD 256 GB"><?= htmlspecialchars($equipamento['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <!-- Status -->
          <div class="mb-3">
            <label for="eq-ativo" class="form-label">Status</label>
            <select class="form-select" id="eq-ativo" name="ativo">
              <option value="1" <?= ($equipamento['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= ($equipamento['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Baixado</option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <?php if ($equipamento): ?>
              <a href="/equipamento" class="btn btn-outline-secondary"
                 style="border-radius:8px" aria-label="Cancelar edição">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </a>
            <?php else: ?>
              <button type="reset" class="btn btn-outline-secondary"
                      style="border-radius:8px" aria-label="Limpar formulário">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div><!-- /col formulário -->

  <!-- ── Listagem ─────────────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="sr-card card">
      <div class="sr-card-header flex-wrap gap-2">
        <h2 class="sr-card-title h6">
          <i class="bi bi-list-ul" aria-hidden="true"></i>
          Equipamentos Cadastrados
        </h2>
        <!-- Filtro por laboratório -->
        <form action="/equipamento" method="GET" class="d-flex align-items-center gap-2"
              aria-label="Filtrar por laboratório">
          <label for="filtro-lab-eq" class="visually-hidden">Filtrar por laboratório</label>
          <select class="form-select form-select-sm" id="filtro-lab-eq" name="laboratorio"
                  onchange="this.form.submit()" style="width:190px;border-radius:8px">
            <option value="">Todos os laboratórios</option>
            <?php foreach ($laboratorios as $lab): ?>
              <option value="<?= (int)$lab['id'] ?>"
                      <?= $filtroLab === (int)$lab['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de equipamentos">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Equipamento</th>
              <th scope="col">Laboratório</th>
              <th scope="col">Nº Série</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($equipamentos)): ?>
              <tr>
                <td colspan="6">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-pc-display" aria-hidden="true"></i>
                    <p>Nenhum equipamento encontrado<?= $filtroLab ? ' para o laboratório selecionado' : '' ?>.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($equipamentos as $eq): ?>
                <tr>
                  <td><span class="text-muted" style="font-size:11px">
                    #<?= str_pad((int)$eq['id'], 3, '0', STR_PAD_LEFT) ?>
                  </span></td>
                  <td>
                    <div style="font-weight:600;font-family:'Poppins',sans-serif;font-size:13px">
                      <?= htmlspecialchars($eq['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php if (!empty($eq['descricao'])): ?>
                      <div class="text-muted" style="font-size:11px">
                        <?= htmlspecialchars(mb_strimwidth($eq['descricao'], 0, 45, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:13px">
                    <?= htmlspecialchars($eq['laboratorio_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td>
                    <?php if (!empty($eq['numero_serie'])): ?>
                      <code style="font-size:11.5px">
                        <?= htmlspecialchars($eq['numero_serie'], ENT_QUOTES, 'UTF-8') ?>
                      </code>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= $eq['ativo']
                      ? '<span class="badge badge-enc">Ativo</span>'
                      : '<span class="badge badge-na">Baixado</span>' ?>
                  </td>
                  <td class="text-end">
                    <a href="/equipamento/editar/<?= (int)$eq['id'] ?>"
                       class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px"
                       aria-label="Editar equipamento <?= htmlspecialchars($eq['nome'], ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="/equipamento/status" method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token"
                             value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="id" value="<?= (int)$eq['id'] ?>" />
                      <input type="hidden" name="ativo" value="<?= $eq['ativo'] ? 0 : 1 ?>" />
                      <button type="submit"
                              class="btn btn-sm <?= $eq['ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              aria-label="<?= $eq['ativo'] ? 'Baixar' : 'Reativar' ?> equipamento <?= htmlspecialchars($eq['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi <?= $eq['ativo'] ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div><!-- /col listagem -->
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
