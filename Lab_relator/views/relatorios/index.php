<?php
/**
 * views/relatorios/index.php
 * Relatórios gerenciais (RF: seção 3.3 – acesso Gestor).
 * Variáveis do RelatorioController:
 *   array  $ocorrencias   – resultado filtrado com todos os joins
 *   array  $stats         – ['total','encerrada','nao_atendida','em_atendimento',
 *                            'tempo_medio_dias'=>float]
 *   array  $por_tipo      – [['descricao'=>string,'total'=>int], ...]
 *   array  $laboratorios  – para filtro
 *   array  $tiposProblema – para filtro
 *   array  $filtros       – filtros ativos
 *   array  $pagination    – paginação
 */
$pageTitle   = 'Relatórios de Ocorrências';
$activeRoute = 'relatorio';
include __DIR__ . '/../layouts/header.php';

$ocorrencias  = $ocorrencias  ?? [];
$stats        = $stats        ?? ['total' => 0,'encerrada' => 0,'nao_atendida' => 0,'em_atendimento' => 0,'tempo_medio_dias' => 0.0];
$por_tipo     = $por_tipo     ?? [];
$laboratorios = $laboratorios ?? [];
$tiposProblema= $tiposProblema?? [];
$filtros      = $filtros      ?? [];
$pagination   = $pagination   ?? ['pagina' => 1,'total' => 0,'porPagina' => 15];

/* Máximo para escala do gráfico ----------------------------------------- */
$maxTipo = !empty($por_tipo) ? max(array_column($por_tipo, 'total')) : 1;

/* Helper status badge ---------------------------------------------------- */
function relStatusBadge(string $s): string {
    return match($s) {
        'Nao Atendida'   => '<span class="badge badge-na">Não Atendida</span>',
        'Em Edicao'      => '<span class="badge badge-ee">Em Edição</span>',
        'Em Atendimento' => '<span class="badge badge-ea">Em Atendimento</span>',
        'Encerrada'      => '<span class="badge badge-enc">Encerrada</span>',
        default          => '<span class="badge bg-secondary">' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '</span>',
    };
}
?>

<!-- ── Filtros ──────────────────────────────────────────────────── -->
<div class="sr-card card mb-3">
  <div class="sr-card-header">
    <h2 class="sr-card-title h6">
      <i class="bi bi-funnel" aria-hidden="true"></i> Filtros do Relatório
    </h2>
    <a href="/relatorio/exportar-pdf?<?= http_build_query(array_filter($filtros)) ?>"
       class="btn btn-outline-secondary btn-sm" style="border-radius:8px"
       aria-label="Exportar relatório em PDF">
      <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF
    </a>
  </div>
  <div class="p-3">
    <form action="/relatorio" method="GET" id="form-relatorio"
          aria-label="Filtros do relatório">
      <div class="row g-2 align-items-end">

        <div class="col-6 col-md-2">
          <label for="r-di" class="form-label" style="font-size:12px">De</label>
          <input type="date" class="form-control form-control-sm" id="r-di" name="data_inicio"
                 value="<?= htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 style="border-radius:8px" />
        </div>

        <div class="col-6 col-md-2">
          <label for="r-df" class="form-label" style="font-size:12px">Até</label>
          <input type="date" class="form-control form-control-sm" id="r-df" name="data_fim"
                 value="<?= htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 style="border-radius:8px" />
        </div>

        <div class="col-6 col-md-2">
          <label for="r-lab" class="form-label" style="font-size:12px">Laboratório</label>
          <select class="form-select form-select-sm" id="r-lab" name="id_laboratorio"
                  style="border-radius:8px">
            <option value="">Todos</option>
            <?php foreach ($laboratorios as $lab): ?>
              <option value="<?= (int)$lab['id'] ?>"
                      <?= ($filtros['id_laboratorio'] ?? 0) == $lab['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label for="r-tipo" class="form-label" style="font-size:12px">Tipo Problema</label>
          <select class="form-select form-select-sm" id="r-tipo" name="id_tipo_problema"
                  style="border-radius:8px">
            <option value="">Todos</option>
            <?php foreach ($tiposProblema as $tp): ?>
              <option value="<?= (int)$tp['id'] ?>"
                      <?= ($filtros['id_tipo_problema'] ?? 0) == $tp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($tp['descricao'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label for="r-status" class="form-label" style="font-size:12px">Status</label>
          <select class="form-select form-select-sm" id="r-status" name="status"
                  style="border-radius:8px">
            <option value="">Todos</option>
            <option value="Nao Atendida"   <?= ($filtros['status'] ?? '') === 'Nao Atendida'   ? 'selected' : '' ?>>Não Atendida</option>
            <option value="Em Atendimento" <?= ($filtros['status'] ?? '') === 'Em Atendimento' ? 'selected' : '' ?>>Em Atendimento</option>
            <option value="Encerrada"      <?= ($filtros['status'] ?? '') === 'Encerrada'      ? 'selected' : '' ?>>Encerrada</option>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill" style="padding:6px 12px;font-size:13px">
              <i class="bi bi-search me-1" aria-hidden="true"></i>Gerar
            </button>
            <a href="/relatorio" class="btn btn-outline-secondary"
               style="border-radius:8px;padding:6px 10px" aria-label="Limpar filtros">
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </a>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<!-- ── KPI Cards ─────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="sr-stat">
      <div class="sr-stat-icon" style="background:#e3f2fd">
        <i class="bi bi-folder2-open text-primary" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val"><?= number_format($stats['total']) ?></div>
        <div class="sr-stat-lbl">Total no Período</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#198754">
      <div class="sr-stat-icon" style="background:#e8f5e9">
        <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#198754"><?= number_format($stats['encerrada']) ?></div>
        <div class="sr-stat-lbl">Encerradas</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#dc3545">
      <div class="sr-stat-icon" style="background:#fdecea">
        <i class="bi bi-exclamation-circle text-danger" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#dc3545"><?= number_format($stats['nao_atendida']) ?></div>
        <div class="sr-stat-lbl">Não Atendidas</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#ffc107">
      <div class="sr-stat-icon" style="background:#fff8e1">
        <i class="bi bi-stopwatch" style="color:#f57f17" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val">
          <?= number_format((float)($stats['tempo_medio_dias'] ?? 0), 1) ?>d
        </div>
        <div class="sr-stat-lbl">Tempo Médio de Resolução</div>
      </div>
    </div>
  </div>
</div>

<!-- ── Gráficos ──────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">

  <!-- Barras: por tipo de problema -->
  <div class="col-md-7">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-bar-chart" aria-hidden="true"></i>
          Ocorrências por Tipo de Problema
        </h2>
      </div>
      <div class="p-3">
        <?php if (empty($por_tipo)): ?>
          <div class="sr-empty"><p>Sem dados para o período.</p></div>
        <?php else: ?>
          <!-- Gráfico acessível: tabela hidden para screen readers -->
          <div class="chart-wrap" role="img"
               aria-label="Gráfico de barras: ocorrências por tipo de problema">
            <?php foreach ($por_tipo as $tp): ?>
              <?php $altura = (int)round(($tp['total'] / $maxTipo) * 140); ?>
              <div class="d-flex flex-column align-items-center" style="flex:1;min-width:0">
                <div class="chart-bar" style="height:<?= $altura ?>px;width:100%"
                     title="<?= htmlspecialchars($tp['descricao'], ENT_QUOTES, 'UTF-8') ?>: <?= (int)$tp['total'] ?>">
                </div>
                <div class="chart-lbl mt-1"><?= (int)$tp['total'] ?></div>
                <div class="chart-lbl" style="max-width:60px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                     title="<?= htmlspecialchars($tp['descricao'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars(mb_strimwidth($tp['descricao'], 0, 10, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <!-- Tabela alternativa para screen readers (WCAG 1.1.1) -->
          <table class="visually-hidden" aria-label="Dados: ocorrências por tipo de problema">
            <thead><tr><th>Tipo</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($por_tipo as $tp): ?>
                <tr>
                  <td><?= htmlspecialchars($tp['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= (int)$tp['total'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Donut SVG: distribuição por status -->
  <div class="col-md-5">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-pie-chart" aria-hidden="true"></i>
          Distribuição por Status
        </h2>
      </div>
      <div class="p-3 d-flex align-items-center justify-content-center gap-3">
        <?php
        $total = max((int)$stats['total'], 1);
        $enc   = (int)$stats['encerrada'];
        $ea    = (int)$stats['em_atendimento'];
        $na    = (int)$stats['nao_atendida'];
        $r = 15.91549430918954; // raio donut
        function arcDash(int $val, int $total, float $r): string {
            $circ = 2 * M_PI * $r;
            $pct  = $circ * ($val / $total);
            return number_format($pct, 4) . ' ' . number_format($circ - $pct, 4);
        }
        $offEnc = 25;
        $offEa  = $offEnc - round(100 * $enc  / $total);
        $offNa  = $offEa  - round(100 * $ea   / $total);
        ?>
        <svg width="120" height="120" viewBox="0 0 42 42"
             role="img" aria-label="Gráfico de pizza: distribuição por status">
          <circle cx="21" cy="21" r="<?= $r ?>" fill="transparent"
                  stroke="#f0f0f0" stroke-width="6"></circle>
          <circle cx="21" cy="21" r="<?= $r ?>" fill="transparent"
                  stroke="#198754" stroke-width="6"
                  stroke-dasharray="<?= arcDash($enc, $total, $r) ?>"
                  stroke-dashoffset="<?= $offEnc ?>"></circle>
          <circle cx="21" cy="21" r="<?= $r ?>" fill="transparent"
                  stroke="#fd7e14" stroke-width="6"
                  stroke-dasharray="<?= arcDash($ea, $total, $r) ?>"
                  stroke-dashoffset="<?= $offEa ?>"></circle>
          <circle cx="21" cy="21" r="<?= $r ?>" fill="transparent"
                  stroke="#dc3545" stroke-width="6"
                  stroke-dasharray="<?= arcDash($na, $total, $r) ?>"
                  stroke-dashoffset="<?= $offNa ?>"></circle>
          <text x="50%" y="46%" dominant-baseline="middle" text-anchor="middle"
                font-size="5" fill="#1a2a3a" font-family="Poppins,sans-serif" font-weight="700">
            <?= number_format($total) ?>
          </text>
          <text x="50%" y="60%" dominant-baseline="middle" text-anchor="middle"
                font-size="3" fill="#78909c" font-family="DM Sans,sans-serif">total</text>
        </svg>
        <dl style="font-size:13px;margin:0">
          <div class="d-flex align-items-center gap-2 mb-2">
            <span style="width:12px;height:12px;background:#198754;border-radius:3px;display:inline-block"
                  aria-hidden="true"></span>
            <dt class="fw-normal">Encerradas</dt>
            <dd class="ms-auto mb-0 fw-bold"><?= number_format($enc) ?></dd>
          </div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span style="width:12px;height:12px;background:#fd7e14;border-radius:3px;display:inline-block"
                  aria-hidden="true"></span>
            <dt class="fw-normal">Em Atendimento</dt>
            <dd class="ms-auto mb-0 fw-bold"><?= number_format($ea) ?></dd>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span style="width:12px;height:12px;background:#dc3545;border-radius:3px;display:inline-block"
                  aria-hidden="true"></span>
            <dt class="fw-normal">Não Atendidas</dt>
            <dd class="ms-auto mb-0 fw-bold"><?= number_format($na) ?></dd>
          </div>
        </dl>
      </div>
    </div>
  </div>

</div><!-- /gráficos -->

<!-- ── Tabela detalhada ──────────────────────────────────────────── -->
<div class="sr-card card">
  <div class="sr-card-header flex-wrap gap-2">
    <h2 class="sr-card-title h6">
      <i class="bi bi-table" aria-hidden="true"></i>
      Detalhamento das Ocorrências
    </h2>
    <a href="/relatorio/exportar-csv?<?= http_build_query(array_filter($filtros)) ?>"
       class="btn btn-outline-secondary btn-sm" style="border-radius:8px"
       aria-label="Exportar tabela em CSV">
      <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>CSV
    </a>
  </div>

  <div class="table-responsive">
    <table class="sr-table table mb-0" aria-label="Detalhamento de ocorrências">
      <thead>
        <tr>
          <th scope="col">Chamado</th>
          <th scope="col">Laboratório</th>
          <th scope="col">Tipo</th>
          <th scope="col">Professor</th>
          <th scope="col">Técnico</th>
          <th scope="col">Abertura</th>
          <th scope="col">Encerramento</th>
          <th scope="col">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ocorrencias)): ?>
          <tr>
            <td colspan="8">
              <div class="sr-empty" role="status">
                <i class="bi bi-journal-x" aria-hidden="true"></i>
                <p>Nenhuma ocorrência encontrada com os filtros aplicados.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($ocorrencias as $oc): ?>
            <tr>
              <td><strong style="font-family:'Poppins',sans-serif">
                #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>
              </strong></td>
              <td style="font-size:13px">
                <?= htmlspecialchars($oc['laboratorio_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td style="font-size:12.5px">
                <?= htmlspecialchars($oc['tipo_problema_desc'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td style="font-size:12.5px">
                <?= htmlspecialchars($oc['professor_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td style="font-size:12.5px">
                <?= !empty($oc['tecnico_nome'])
                    ? htmlspecialchars($oc['tecnico_nome'], ENT_QUOTES, 'UTF-8')
                    : '<span class="text-muted">—</span>' ?>
              </td>
              <td style="font-size:12px">
                <time datetime="<?= htmlspecialchars($oc['data_criacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <?= !empty($oc['data_criacao']) ? date('d/m/Y H:i', strtotime($oc['data_criacao'])) : '—' ?>
                </time>
              </td>
              <td style="font-size:12px">
                <?php if (!empty($oc['data_encerramento'])): ?>
                  <time datetime="<?= htmlspecialchars($oc['data_encerramento'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= date('d/m/Y H:i', strtotime($oc['data_encerramento'])) ?>
                  </time>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= relStatusBadge($oc['status'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php
  $totalPag = (int)ceil(($pagination['total'] ?: 1) / $pagination['porPagina']);
  $pagAtual = (int)$pagination['pagina'];
  $qsBase   = http_build_query(array_filter([
      'data_inicio'      => $filtros['data_inicio']      ?? '',
      'data_fim'         => $filtros['data_fim']         ?? '',
      'id_laboratorio'   => $filtros['id_laboratorio']   ?? '',
      'id_tipo_problema' => $filtros['id_tipo_problema'] ?? '',
      'status'           => $filtros['status']           ?? '',
  ]));
  ?>
  <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top flex-wrap gap-2">
    <small class="text-muted">
      <?= number_format($pagination['total']) ?> ocorrência(s) no período
    </small>
    <?php if ($totalPag > 1): ?>
      <nav aria-label="Paginação do relatório">
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $pagAtual <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagAtual - 1 ?>&<?= $qsBase ?>"
               aria-label="Página anterior">‹</a>
          </li>
          <?php for ($p = max(1, $pagAtual - 2); $p <= min($totalPag, $pagAtual + 2); $p++): ?>
            <li class="page-item <?= $p === $pagAtual ? 'active' : '' ?>"
                <?= $p === $pagAtual ? 'aria-current="page"' : '' ?>>
              <a class="page-link" href="?pagina=<?= $p ?>&<?= $qsBase ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $pagAtual >= $totalPag ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $pagAtual + 1 ?>&<?= $qsBase ?>"
               aria-label="Próxima página">›</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

</div><!-- /card tabela -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
