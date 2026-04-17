<?php
/**
 * views/monitor/index.php
 * Monitor de chamados em Kanban (RF: MONITORAMENTO DE CHAMADAS / ALTERAÇÃO DE STATUS – seção 3.3).
 * Acesso exclusivo: perfil tecnico.
 * Variáveis do MonitorController:
 *   array $ocorrencias    – todas as ocorrências (status != 'Encerrada' + Encerradas recentes)
 *   array $laboratorios   – para filtro
 *   array $stats          – ['nao_atendida','em_atendimento','encerrada','total']
 *   array $filtros        – ['id_laboratorio'=>?int,'status_col'=>?string]
 *
 * Atualização de status: POST /monitor/atualizar-status
 *   → id, novo_status, csrf_token
 */
$pageTitle   = 'Monitor de Chamados';
$activeRoute = 'monitor';
include __DIR__ . '/../layouts/header.php';

$ocorrencias  = $ocorrencias  ?? [];
$laboratorios = $laboratorios ?? [];
$stats        = $stats        ?? ['nao_atendida' => 0,'em_atendimento' => 0,'encerrada' => 0,'total' => 0];
$filtros      = $filtros      ?? [];

/* Agrupa por status usando array_filter + arrow fn (PHP 8.0+) ------------ */
$naoAtendidas   = array_values(array_filter($ocorrencias, fn($o) => $o['status'] === 'Nao Atendida'));
$emAtendimento  = array_values(array_filter($ocorrencias, fn($o) => $o['status'] === 'Em Atendimento'));
$encerradas     = array_values(array_filter($ocorrencias, fn($o) => $o['status'] === 'Encerrada'));
?>

<!-- ── KPI Row ──────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#dc3545">
      <div class="sr-stat-icon" style="background:#fdecea">
        <i class="bi bi-inbox text-danger" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#dc3545"><?= (int)$stats['nao_atendida'] ?></div>
        <div class="sr-stat-lbl">Não Atendidos</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#fd7e14">
      <div class="sr-stat-icon" style="background:#fff3e0">
        <i class="bi bi-gear-wide-connected" style="color:#fd7e14" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#fd7e14"><?= (int)$stats['em_atendimento'] ?></div>
        <div class="sr-stat-lbl">Em Atendimento</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#198754">
      <div class="sr-stat-icon" style="background:#e8f5e9">
        <i class="bi bi-check2-all text-success" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#198754"><?= (int)$stats['encerrada'] ?></div>
        <div class="sr-stat-lbl">Encerrados</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat">
      <div class="sr-stat-icon" style="background:#e3f2fd">
        <i class="bi bi-kanban text-primary" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val"><?= (int)$stats['total'] ?></div>
        <div class="sr-stat-lbl">Total Geral</div>
      </div>
    </div>
  </div>
</div>

<!-- ── Filtros rápidos ─────────────────────────────────────────── -->
<form action="/monitor" method="GET" class="d-flex flex-wrap gap-2 mb-3 align-items-end"
      aria-label="Filtrar chamados">
  <div>
    <label for="m-lab" class="form-label" style="font-size:11.5px">Laboratório</label>
    <select class="form-select form-select-sm" id="m-lab" name="id_laboratorio"
            onchange="this.form.submit()" style="width:190px;border-radius:8px">
      <option value="">Todos os laboratórios</option>
      <?php foreach ($laboratorios as $lab): ?>
        <option value="<?= (int)$lab['id'] ?>"
                <?= ($filtros['id_laboratorio'] ?? 0) == $lab['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <button type="button" class="btn btn-outline-secondary btn-sm"
            id="btn-refresh-monitor" style="border-radius:8px"
            aria-label="Atualizar monitor">
      <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Atualizar
    </button>
  </div>
</form>

<!-- ── Kanban ──────────────────────────────────────────────────── -->
<div class="row g-3" role="list" aria-label="Kanban de chamados">

  <!-- Coluna: Não Atendidos -->
  <div class="col-md-4" role="listitem">
    <div class="kanban-col-head" style="background:#fdecea;color:#c62828">
      <i class="bi bi-inbox-fill" aria-hidden="true"></i>
      Não Atendidos
      <span class="badge ms-auto" style="background:#dc3545;color:#fff;border-radius:12px;padding:2px 8px"
            aria-label="<?= count($naoAtendidas) ?> chamados não atendidos">
        <?= count($naoAtendidas) ?>
      </span>
    </div>

    <?php if (empty($naoAtendidas)): ?>
      <div class="sr-empty" role="status">
        <i class="bi bi-inbox" aria-hidden="true"></i>
        <p>Nenhum chamado não atendido.</p>
      </div>
    <?php else: ?>
      <?php foreach ($naoAtendidas as $oc): ?>
        <article class="chamado-card c-na"
                 aria-label="Chamado #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>, Não Atendido">
          <div class="chamado-id">#OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?></div>
          <div class="chamado-titulo">
            <?= htmlspecialchars($oc['tipo_problema_desc'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div class="chamado-meta">
            <span>
              <i class="bi bi-building" aria-hidden="true"></i>
              <?= htmlspecialchars($oc['laboratorio_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span>
              <i class="bi bi-person" aria-hidden="true"></i>
              <?= htmlspecialchars($oc['professor_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span>
              <i class="bi bi-clock" aria-hidden="true"></i>
              <time datetime="<?= htmlspecialchars($oc['data_criacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?= !empty($oc['data_criacao']) ? date('d/m/Y H:i', strtotime($oc['data_criacao'])) : '—' ?>
              </time>
            </span>
          </div>
          <div class="d-flex gap-1 mt-2">
            <a href="/ocorrencia/ver/<?= (int)$oc['id'] ?>"
               class="btn btn-sm btn-outline-primary" style="font-size:11px;border-radius:6px"
               aria-label="Ver detalhes do chamado #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Ver
            </a>
            <!-- Iniciar atendimento: POST /monitor/atualizar-status -->
            <form action="/monitor/atualizar-status" method="POST" class="d-inline">
              <input type="hidden" name="csrf_token"
                     value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
              <input type="hidden" name="id" value="<?= (int)$oc['id'] ?>" />
              <input type="hidden" name="novo_status" value="Em Atendimento" />
              <button type="submit"
                      class="btn btn-sm btn-warning" style="font-size:11px;border-radius:6px;color:#212529"
                      aria-label="Iniciar atendimento do chamado #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>">
                <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Iniciar
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /col não atendidos -->

  <!-- Coluna: Em Atendimento -->
  <div class="col-md-4" role="listitem">
    <div class="kanban-col-head" style="background:#fff3e0;color:#e65100">
      <i class="bi bi-gear-wide-connected" aria-hidden="true"></i>
      Em Atendimento
      <span class="badge ms-auto" style="background:#fd7e14;color:#fff;border-radius:12px;padding:2px 8px"
            aria-label="<?= count($emAtendimento) ?> chamados em atendimento">
        <?= count($emAtendimento) ?>
      </span>
    </div>

    <?php if (empty($emAtendimento)): ?>
      <div class="sr-empty" role="status">
        <i class="bi bi-gear" aria-hidden="true"></i>
        <p>Nenhum chamado em atendimento.</p>
      </div>
    <?php else: ?>
      <?php foreach ($emAtendimento as $oc): ?>
        <?php
        $ehMeu = ($_SESSION['id_usuario'] ?? 0) == ($oc['id_tecnico'] ?? 0);
        ?>
        <article class="chamado-card c-ea"
                 aria-label="Chamado #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>, Em Atendimento">
          <div class="chamado-id">#OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?></div>
          <div class="chamado-titulo">
            <?= htmlspecialchars($oc['tipo_problema_desc'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div class="chamado-meta">
            <span>
              <i class="bi bi-building" aria-hidden="true"></i>
              <?= htmlspecialchars($oc['laboratorio_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span>
              <i class="bi bi-clock" aria-hidden="true"></i>
              <time datetime="<?= htmlspecialchars($oc['data_criacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?= !empty($oc['data_criacao']) ? date('d/m/Y H:i', strtotime($oc['data_criacao'])) : '—' ?>
              </time>
            </span>
            <?php if (!empty($oc['tecnico_nome'])): ?>
              <span style="<?= $ehMeu ? 'color:#fd7e14;font-weight:600' : '' ?>">
                <i class="bi bi-person-check" aria-hidden="true"></i>
                <?= $ehMeu ? 'Você' : htmlspecialchars($oc['tecnico_nome'], ENT_QUOTES, 'UTF-8') ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="d-flex gap-1 mt-2">
            <a href="/ocorrencia/ver/<?= (int)$oc['id'] ?>"
               class="btn btn-sm btn-outline-primary" style="font-size:11px;border-radius:6px"
               aria-label="Ver detalhes">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Ver
            </a>
            <!-- Encerrar: disponível para o técnico responsável ou gestor -->
            <form action="/monitor/atualizar-status" method="POST" class="d-inline">
              <input type="hidden" name="csrf_token"
                     value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
              <input type="hidden" name="id" value="<?= (int)$oc['id'] ?>" />
              <input type="hidden" name="novo_status" value="Encerrada" />
              <button type="submit"
                      class="btn btn-sm btn-success" style="font-size:11px;border-radius:6px"
                      aria-label="Encerrar chamado #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>">
                <i class="bi bi-check2 me-1" aria-hidden="true"></i>Encerrar
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /col em atendimento -->

  <!-- Coluna: Encerrados (recentes) -->
  <div class="col-md-4" role="listitem">
    <div class="kanban-col-head" style="background:#e8f5e9;color:#2e7d32">
      <i class="bi bi-check2-all" aria-hidden="true"></i>
      Encerrados <span style="font-weight:400">(recentes)</span>
      <span class="badge ms-auto" style="background:#198754;color:#fff;border-radius:12px;padding:2px 8px"
            aria-label="<?= count($encerradas) ?> chamados encerrados">
        <?= count($encerradas) ?>
      </span>
    </div>

    <?php if (empty($encerradas)): ?>
      <div class="sr-empty" role="status">
        <i class="bi bi-check-circle" aria-hidden="true"></i>
        <p>Nenhum chamado encerrado recentemente.</p>
      </div>
    <?php else: ?>
      <?php foreach ($encerradas as $oc): ?>
        <article class="chamado-card c-enc"
                 aria-label="Chamado #OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?>, Encerrado">
          <div class="chamado-id">#OC-<?= str_pad((int)$oc['id'], 3, '0', STR_PAD_LEFT) ?></div>
          <div class="chamado-titulo">
            <?= htmlspecialchars($oc['tipo_problema_desc'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div class="chamado-meta">
            <span>
              <i class="bi bi-building" aria-hidden="true"></i>
              <?= htmlspecialchars($oc['laboratorio_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php if (!empty($oc['data_encerramento'])): ?>
              <span>
                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                <time datetime="<?= htmlspecialchars($oc['data_encerramento'], ENT_QUOTES, 'UTF-8') ?>">
                  Enc.: <?= date('d/m/Y H:i', strtotime($oc['data_encerramento'])) ?>
                </time>
              </span>
            <?php endif; ?>
          </div>
          <div class="mt-2">
            <a href="/ocorrencia/ver/<?= (int)$oc['id'] ?>"
               class="btn btn-sm btn-outline-secondary" style="font-size:11px;border-radius:6px"
               aria-label="Ver chamado encerrado">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Ver
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /col encerrados -->

</div><!-- /kanban row -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
