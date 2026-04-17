<?php
/**
 * views/laboratorio/index.php
 * CRUD de laboratórios (RF: CADASTRO DE LABORATÓRIOS – seção 3.3).
 * Variáveis do LaboratorioController:
 *   array   $laboratorios – lista de laboratórios
 *   ?array  $laboratorio  – dados para edição (null = novo)
 *   ?array  $pagination   – ['pagina'=>int,'total'=>int,'porPagina'=>int]
 *   ?string $busca        – termo de busca atual
 */
$pageTitle   = 'Laboratórios';
$activeRoute = 'laboratorio';
include __DIR__ . '/../layouts/header.php';

$laboratorio = $laboratorio ?? null;
$laboratorios = $laboratorios ?? [];
$busca = htmlspecialchars($busca ?? '', ENT_QUOTES, 'UTF-8');
$pagination = $pagination ?? ['pagina' => 1, 'total' => count($laboratorios), 'porPagina' => 10];
?>

<div class="row g-3">

  <!-- ── Formulário ──────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="sr-card card h-auto">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $laboratorio ? 'bi-pencil-square' : 'bi-plus-circle' ?>" aria-hidden="true"></i>
          <?= $laboratorio ? 'Editar Laboratório' : 'Novo Laboratório' ?>
        </h2>
      </div>
      <div class="p-3">
        <!-- action: POST /laboratorio/salvar ou /laboratorio/atualizar -->
        <form action="/laboratorio/<?= $laboratorio ? 'atualizar' : 'salvar' ?>"
              method="POST" novalidate
              id="form-laboratorio"
              aria-label="<?= $laboratorio ? 'Editar laboratório' : 'Cadastrar novo laboratório' ?>">

          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
          <?php if ($laboratorio): ?>
            <input type="hidden" name="id"
                   value="<?= (int)($laboratorio['id'] ?? 0) ?>" />
          <?php endif; ?>

          <!-- Nome -->
          <div class="mb-3">
            <label for="lab-nome" class="form-label">
              Nome <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="text" class="form-control" id="lab-nome" name="nome"
                   maxlength="100" required
                   placeholder="Ex.: Laboratório de Informática 1"
                   aria-required="true"
                   value="<?= htmlspecialchars($laboratorio['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="invalid-feedback" role="alert">O nome é obrigatório.</div>
          </div>

          <!-- Localização -->
          <div class="mb-3">
            <label for="lab-loc" class="form-label">
              Localização <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="text" class="form-control" id="lab-loc" name="localizacao"
                   maxlength="100" required
                   placeholder="Ex.: Bloco B – Sala 12"
                   aria-required="true"
                   value="<?= htmlspecialchars($laboratorio['localizacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="invalid-feedback" role="alert">A localização é obrigatória.</div>
          </div>

          <!-- Capacidade -->
          <div class="mb-3">
            <label for="lab-cap" class="form-label">Capacidade (estações)</label>
            <input type="number" class="form-control" id="lab-cap" name="capacidade"
                   min="1" max="500" placeholder="Ex.: 30"
                   aria-describedby="lab-cap-help"
                   value="<?= (int)($laboratorio['capacidade'] ?? '') ?: '' ?>" />
            <div id="lab-cap-help" class="form-text">Número de computadores/bancadas.</div>
          </div>

          <!-- Descrição -->
          <div class="mb-3">
            <label for="lab-desc" class="form-label">Descrição</label>
            <textarea class="form-control" id="lab-desc" name="descricao"
                      rows="2" maxlength="255"
                      placeholder="Informações complementares..."><?= htmlspecialchars($laboratorio['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <!-- Status -->
          <div class="mb-3">
            <label for="lab-ativo" class="form-label">Status</label>
            <select class="form-select" id="lab-ativo" name="ativo" aria-label="Status do laboratório">
              <option value="1" <?= ($laboratorio['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= ($laboratorio['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <?php if ($laboratorio): ?>
              <a href="/laboratorio" class="btn btn-outline-secondary"
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
          Laboratórios Cadastrados
        </h2>
        <!-- Busca – GET /laboratorio?busca=... -->
        <form action="/laboratorio" method="GET" class="d-flex" role="search"
              aria-label="Buscar laboratório">
          <div class="input-group" style="max-width:230px">
            <label for="busca-lab" class="visually-hidden">Buscar laboratório</label>
            <span class="input-group-text" aria-hidden="true">
              <i class="bi bi-search"></i>
            </span>
            <input type="search" class="form-control" id="busca-lab" name="busca"
                   placeholder="Buscar..." value="<?= $busca ?>"
                   style="border-radius:0 8px 8px 0" />
          </div>
          <button type="submit" class="visually-hidden">Buscar</button>
        </form>
      </div>

      <!-- Tabela -->
      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de laboratórios">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Nome</th>
              <th scope="col">Localização</th>
              <th scope="col">Capac.</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($laboratorios)): ?>
              <tr>
                <td colspan="6">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <p>Nenhum laboratório encontrado<?= $busca ? ' para "' . $busca . '"' : '' ?>.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($laboratorios as $lab): ?>
                <tr>
                  <td>
                    <span class="text-muted" style="font-size:11px">
                      #<?= str_pad((int)$lab['id'], 3, '0', STR_PAD_LEFT) ?>
                    </span>
                  </td>
                  <td>
                    <strong style="font-family:'Poppins',sans-serif;font-size:13px">
                      <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                    <?php if (!empty($lab['descricao'])): ?>
                      <div class="text-muted" style="font-size:11px">
                        <?= htmlspecialchars(mb_strimwidth($lab['descricao'], 0, 50, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <i class="bi bi-geo-alt text-primary" aria-hidden="true"></i>
                    <?= htmlspecialchars($lab['localizacao'], ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td><?= $lab['capacidade'] ? (int)$lab['capacidade'] : '—' ?></td>
                  <td>
                    <?php if ($lab['ativo']): ?>
                      <span class="badge badge-enc" aria-label="Ativo">Ativo</span>
                    <?php else: ?>
                      <span class="badge badge-na" aria-label="Inativo">Inativo</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <!-- Editar -->
                    <a href="/laboratorio/editar/<?= (int)$lab['id'] ?>"
                       class="btn btn-sm btn-outline-primary me-1"
                       style="border-radius:6px"
                       aria-label="Editar laboratório <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <!-- Ativar/Inativar -->
                    <form action="/laboratorio/status" method="POST" class="d-inline"
                          aria-label="<?= $lab['ativo'] ? 'Inativar' : 'Ativar' ?> laboratório">
                      <input type="hidden" name="csrf_token"
                             value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="id" value="<?= (int)$lab['id'] ?>" />
                      <input type="hidden" name="ativo" value="<?= $lab['ativo'] ? 0 : 1 ?>" />
                      <button type="submit"
                              class="btn btn-sm <?= $lab['ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              aria-label="<?= $lab['ativo'] ? 'Inativar' : 'Ativar' ?> laboratório <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi <?= $lab['ativo'] ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <?php if (($pagination['total'] ?? 0) > 0): ?>
        <?php
        $totalPag = (int)ceil($pagination['total'] / $pagination['porPagina']);
        $pagAtual = (int)$pagination['pagina'];
        ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
          <small class="text-muted">
            <?= number_format($pagination['total']) ?> laboratório(s) encontrado(s)
          </small>
          <?php if ($totalPag > 1): ?>
            <nav aria-label="Paginação de laboratórios">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pagAtual <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?pagina=<?= $pagAtual - 1 ?>&busca=<?= urlencode($busca) ?>"
                     aria-label="Página anterior">‹</a>
                </li>
                <?php for ($p = 1; $p <= $totalPag; $p++): ?>
                  <li class="page-item <?= $p === $pagAtual ? 'active' : '' ?>"
                      <?= $p === $pagAtual ? 'aria-current="page"' : '' ?>>
                    <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>"><?= $p ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $pagAtual >= $totalPag ? 'disabled' : '' ?>">
                  <a class="page-link" href="?pagina=<?= $pagAtual + 1 ?>&busca=<?= urlencode($busca) ?>"
                     aria-label="Próxima página">›</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div><!-- /col listagem -->
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
