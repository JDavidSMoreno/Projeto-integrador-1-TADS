<?php
/**
 * views/ocorrencias/list.php
 * Consulta de ocorrências (RF: REGISTRO DE OCORRÊNCIAS / MONITORAMENTO – seção 3.3).
 * Professor vê apenas suas próprias; Gestor vê todas.
 * Variáveis do OcorrenciaController:
 *   array  $ocorrencias – lista com joins (lab, equip, tipo, professor, tecnico)
 *   array  $stats       – ['nao_atendida'=>int,'em_atendimento'=>int,'encerrada'=>int,'total'=>int]
 *   array  $laboratorios
 *   array  $filtros     – ['status'=>?string,'id_laboratorio'=>?int,'data_inicio'=>?string,'data_fim'=>?string]
 *   array  $pagination  – ['pagina'=>int,'total'=>int,'porPagina'=>int]
 */
$pageTitle   = ($_SESSION['tipo_usuario'] ?? '') === 'gestor'
               ? 'Todas as Ocorrências' : 'Minhas Ocorrências';
$activeRoute = 'ocorrencia';
include __DIR__ . '/../layouts/header.php';

$ocorrencias  = $ocorrencias  ?? [];
$stats        = $stats        ?? ['nao_atendida' => 0,'em_atendimento' => 0,'encerrada' => 0,'total' => 0];
$laboratorios = $laboratorios ?? [];
$filtros      = $filtros      ?? [];
$pagination   = $pagination   ?? ['pagina' => 1,'total' => 0,'porPagina' => 10];

/* Helper: badge de status ------------------------------------------------ */
function statusBadge(string $status): string {
    return match($status) {
        'Nao Atendida'   => '<span class="badge badge-na" aria-label="Não Atendida">Não Atendida</span>',
        'Em Edicao'      => '<span class="badge badge-ee" aria-label="Em Edição">Em Edição</span>',
        'Em Atendimento' => '<span class="badge badge-ea" aria-label="Em Atendimento">Em Atendimento</span>',
        'Encerrada'      => '<span class="badge badge-enc" aria-label="Encerrada">Encerrada</span>',
        default          => '<span class="badge bg-secondary">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>',
    };
}

$tipoUsuario = $_SESSION['tipo_usuario'] ?? 'professor';
?>

<!-- ── KPI cards ──────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#dc3545">
      <div class="sr-stat-icon" style="background:#fdecea">
        <i class="bi bi-exclamation-circle text-danger" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#dc3545">
          <?= number_format($stats['nao_atendida']) ?>
        </div>
        <div class="sr-stat-lbl">Não Atendidas</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#fd7e14">
      <div class="sr-stat-icon" style="background:#fff3e0">
        <i class="bi bi-gear text-warning" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#fd7e14">
          <?= number_format($stats['em_atendimento']) ?>
        </div>
        <div class="sr-stat-lbl">Em Atendimento</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat" style="border-left-color:#198754">
      <div class="sr-stat-icon" style="background:#e8f5e9">
        <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val" style="color:#198754">
          <?= number_format($stats['encerrada']) ?>
        </div>
        <div class="sr-stat-lbl">Encerradas</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sr-stat">
      <div class="sr-stat-icon" style="background:#e3f2fd">
        <i class="bi bi-folder2 text-primary" aria-hidden="true"></i>
      </div>
      <div>
        <div class="sr-stat-val"><?= number_format($stats['total']) ?></div>
        <div class="sr-stat-lbl">Total</div>
      </div>
    </div>
  </div>
</div>

<!-- ── Filtros ──────────────────────────────────────────────────── -->
<div class="sr-card card mb-3">
  <div class="p-3">
    <form action="/ocorrencia" method="GET" id="form-filtros"
          aria-label="Filtros de ocorrências">
      <div class="row g-2 align-items-end">

        <div class="col-md-3 col-6">
          <label for="f-status" class="form-label" style="font-size:12px">Status</label>
          <select class="form-select form-select-sm" id="f-status" name="status"
                  style="border-radius:8px">
            <option value="">Todos os status</option>
            <?php foreach (['Nao Atendida','Em Edicao','Em Atendimento','Encerrada'] as $s): ?>
              <option value="<?= $s ?>"
                      <?= ($filtros['status'] ?? '') === $s ? 'selected' : '' ?>>
                <?= match($s) {
                    'Nao Atendida'   => 'Não Atendida',
                    'Em Edicao'      => 'Em Edição',
                    'Em Atendimento' => 'Em Atendimento',
                    'Encerrada'      => 'Encerrada',
                } ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 col-6">
          <label for="f-lab" class="form-label" style="font-size:12px">Laboratório</label>
          <select class="form-select form-select-sm" id="f-lab" name="id_laboratorio"
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

        <div class="col-md-2 col-6">
          <label for="f-di" class="form-label" style="font-size:12px">De</label>
          <input type="date" class="form-control form-control-sm" id="f-di" name="data_inicio"
                 value="<?= htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 style="border-radius:8px" />
        </div>

        <div class="col-md-2 col-6">
          <label for="f-df" class="form-label" style="font-size:12px">Até</label>
          <input type="date" class="form-control form-control-sm" id="f-df" name="data_fim"
                 value="<?= htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 style="border-radius:8px" />
        </div>

        <div class="col-md-2 col-12">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill" style="padding:6px 12px;font-size:13px">
              <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar
            </button>
            <a href="/ocorrencia" class="btn btn-outline-secondary" style="border-radius:8px;padding:6px 10px"
               aria-label="Limpar filtros">
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </a>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<!-- ── Tabela de ocorrências ─────────────────────────────────────── -->
<div class="sr-card card">
  <div class="sr-card-header flex-wrap gap-2">
    <h2 class="sr-card-title h6">
      <i class="bi bi-journal-text" aria-hidden="true"></i>
      Ocorrências
    </h2>
    <?php if ($tipoUsuario === 'professor'): ?>
      <a href="/ocorrencia/criar" class="btn btn-sr btn-sm" style="padding:5px 14px;font-size:12.5px">
        <i class="bi bi-plus me-1" aria-hidden="true"></i>Nova Ocorrência
      </a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="sr-table table mb-0" aria-label="Lista de ocorrências">
      <thead>
        <tr>
          <th scope="col">Chamado</th>
          <th scope="col">Laboratório</th>
          <th scope="col">Tipo</th>
          <th scope="col">Equipamento</th>
          <th scope="col">Abertura</th>
          <th scope="col">Status</th>
          <th scope="col"><span class="visually-hidden">Ações</span></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ocorrencias)): ?>
          <tr>
            <td colspan="7">
              <div class="sr-empty" role="status">
                <i class="bi bi-journal-x" aria-hidden="true"></i>
                <p>Nenhuma ocorrência encontrada com os filtros aplicados.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($ocorrencias as $oc): ?>
            <?php
            $idOc      = (int)$oc['id'];
            $status    = $oc['status'] ?? '';
            $ehProfProprietario = ($_SESSION['id_usuario'] ?? 0) == ($oc['id_professor'] ?? 0);
            $podeEdit  = $status === 'Nao Atendida' && ($tipoUsuario === 'gestor' || $ehProfProprietario);
            ?>
            <tr>
              <td>
                <strong style="font-family:'Poppins',sans-serif">
                  #OC-<?= str_pad($idOc, 3, '0', STR_PAD_LEFT) ?>
                </strong>
              </td>
              <td><?= htmlspecialchars($oc['laboratorio_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size:12.5px">
                <?= htmlspecialchars($oc['tipo_problema_desc'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td style="font-size:12px">
                <?php if (!empty($oc['equipamento_nome'])): ?>
                  <code><?= htmlspecialchars($oc['equipamento_nome'], ENT_QUOTES, 'UTF-8') ?></code>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px">
                <time datetime="<?= htmlspecialchars($oc['data_criacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <?= !empty($oc['data_criacao'])
                      ? date('d/m/Y H:i', strtotime($oc['data_criacao']))
                      : '—' ?>
                </time>
              </td>
              <td><?= statusBadge($status) ?></td>
              <td class="text-end">
                <!-- Ver sempre disponível -->
                <a href="/ocorrencia/ver/<?= $idOc ?>"
                   class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px"
                   aria-label="Ver ocorrência #OC-<?= str_pad($idOc, 3, '0', STR_PAD_LEFT) ?>">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </a>
                <!-- Editar somente quando permitido -->
                <?php if ($podeEdit): ?>
                  <a href="/ocorrencia/editar/<?= $idOc ?>"
                     class="btn btn-sm btn-outline-warning me-1" style="border-radius:6px"
                     aria-label="Editar ocorrência #OC-<?= str_pad($idOc, 3, '0', STR_PAD_LEFT) ?>">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                  </a>
                  <!-- Cancelar/excluir somente quando Nao Atendida -->
                  <form action="/ocorrencia/cancelar" method="POST" class="d-inline"
                        aria-label="Cancelar ocorrência">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="id" value="<?= $idOc ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:6px"
                            aria-label="Cancelar ocorrência #OC-<?= str_pad($idOc, 3, '0', STR_PAD_LEFT) ?>"
                            data-confirm="Confirma o cancelamento desta ocorrência?">
                      <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
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
      'status'        => $filtros['status']         ?? '',
      'id_laboratorio'=> $filtros['id_laboratorio'] ?? '',
      'data_inicio'   => $filtros['data_inicio']    ?? '',
      'data_fim'      => $filtros['data_fim']       ?? '',
  ]));
  ?>
  <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top flex-wrap gap-2">
    <small class="text-muted">
      <?= number_format($pagination['total']) ?> ocorrência(s) encontrada(s)
    </small>
    <?php if ($totalPag > 1): ?>
      <nav aria-label="Paginação de ocorrências">
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

</div><!-- /card -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
