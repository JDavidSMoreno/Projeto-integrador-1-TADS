<?php
// Arquivo: views/ocorrencias/show.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Tarefa: view de detalhe de ocorrência

$pageTitle = 'Ocorrência #' . (string)($ocorrencia['id'] ?? '');
$activeRoute = 'ocorrencia';

$ocorrencia = is_array($ocorrencia ?? null) ? $ocorrencia : [];
$historico = is_array($historico ?? null) ? $historico : [];
$basePath = defined('APP_BASE_PATH') ? (string)APP_BASE_PATH : '';

$h = static fn (mixed $valor): string => htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');

$valor = static function (array $dados, array $chaves, mixed $padrao = ''): mixed {
    foreach ($chaves as $chave) {
        if (array_key_exists($chave, $dados) && $dados[$chave] !== null && $dados[$chave] !== '') {
            return $dados[$chave];
        }
    }

    return $padrao;
};

$formatarData = static function (mixed $data) use ($h): string {
    if ($data === null || $data === '') {
        return $h('—');
    }

    $timestamp = strtotime((string)$data);
    if ($timestamp === false) {
        return $h('—');
    }

    return $h(date('d/m/Y H:i', $timestamp));
};

$statusBadge = static function (?string $status) use ($h): string {
    return match ($status) {
        'Nao Atendida' => '<span class="badge bg-secondary">Não Atendida</span>',
        'Em Edicao' => '<span class="badge bg-warning text-dark">Em Edição</span>',
        'Em Atendimento' => '<span class="badge bg-primary">Em Atendimento</span>',
        'Encerrada' => '<span class="badge bg-success">Encerrada</span>',
        null, '' => $h('—'),
        default => '<span class="badge bg-light text-dark border">' . $h($status) . '</span>',
    };
};

$idOcorrencia = (int)($ocorrencia['id'] ?? 0);
$statusAtual = (string)($ocorrencia['status'] ?? '');
$idProfessor = (int)($ocorrencia['id_professor'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);
$usuarioTipo = (string)($_SESSION['usuario_tipo'] ?? $_SESSION['tipo_usuario'] ?? $_SESSION['usuario']['perfil'] ?? '');
$podeEditar = $statusAtual === 'Nao Atendida'
    && $usuarioTipo === 'professor'
    && $idProfessor === $usuarioId;
$podeVerMonitor = in_array($usuarioTipo, ['tecnico', 'gestor'], true) && $statusAtual !== 'Encerrada';
$mostrarAcoes = $podeEditar || $podeVerMonitor;

$nomeLaboratorio = $valor($ocorrencia, ['nome_laboratorio', 'laboratorio_nome'], '—');
$localizacaoLaboratorio = $valor($ocorrencia, ['localizacao_laboratorio', 'laboratorio_bloco'], '');
$nomeEquipamento = $valor($ocorrencia, ['nome_equipamento', 'equipamento_nome'], '');
$nomeTipo = $valor($ocorrencia, ['nome_tipo_problema', 'tipo_problema_desc', 'tipo_problema_nome'], '—');
$nomeProfessor = $valor($ocorrencia, ['nome_professor', 'professor_nome'], '—');
$nomeTecnico = $valor($ocorrencia, ['nome_tecnico', 'tecnico_nome'], '');

include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
          <i class="bi bi-exclamation-triangle me-2"></i>Ocorrência #<?php echo $h($idOcorrencia); ?>
        </h4>
        <a href="<?php echo $h($basePath); ?>/ocorrencia" class="btn btn-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
      </div>

      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo $h($_SESSION['flash_success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $h($_SESSION['flash_error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-md-8">
          <div class="card sr-card h-100">
            <div class="sr-card-header">
              <h5 class="sr-card-title mb-0">
                <i class="bi bi-eye"></i>Detalhes da Ocorrência
              </h5>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><?php echo $statusBadge($statusAtual); ?></dd>

                <dt class="col-sm-3">Laboratório</dt>
                <dd class="col-sm-9">
                  <?php echo $h($nomeLaboratorio); ?>
                  <?php if ((string)$localizacaoLaboratorio !== ''): ?>
                    <span class="text-muted">— <?php echo $h($localizacaoLaboratorio); ?></span>
                  <?php endif; ?>
                </dd>

                <dt class="col-sm-3">Equipamento</dt>
                <dd class="col-sm-9">
                  <?php if ((string)$nomeEquipamento !== ''): ?>
                    <?php echo $h($nomeEquipamento); ?>
                  <?php else: ?>
                    <em>Não especificado</em>
                  <?php endif; ?>
                </dd>

                <dt class="col-sm-3">Tipo</dt>
                <dd class="col-sm-9"><?php echo $h($nomeTipo); ?></dd>

                <dt class="col-sm-3">Descrição</dt>
                <dd class="col-sm-9">
                  <p class="mb-0"><?php echo nl2br($h($ocorrencia['descricao'] ?? '')); ?></p>
                </dd>

                <dt class="col-sm-3">Aberta em</dt>
                <dd class="col-sm-9"><?php echo $formatarData($ocorrencia['data_criacao'] ?? null); ?></dd>

                <dt class="col-sm-3">Encerrada em</dt>
                <dd class="col-sm-9"><?php echo $formatarData($ocorrencia['data_encerramento'] ?? null); ?></dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card sr-card mb-3">
            <div class="sr-card-header">
              <h5 class="sr-card-title mb-0">
                <i class="bi bi-person"></i>Responsáveis
              </h5>
            </div>
            <div class="card-body">
              <dl class="mb-0">
                <dt>Professor</dt>
                <dd><?php echo $h($nomeProfessor); ?></dd>

                <dt>Técnico</dt>
                <dd class="mb-0">
                  <?php if ((string)$nomeTecnico !== ''): ?>
                    <?php echo $h($nomeTecnico); ?>
                  <?php else: ?>
                    <em>Não atribuído</em>
                  <?php endif; ?>
                </dd>
              </dl>
            </div>
          </div>

          <?php if ($mostrarAcoes): ?>
            <div class="card sr-card">
              <div class="sr-card-header">
                <h5 class="sr-card-title mb-0">
                  <i class="bi bi-check-lg"></i>Ações
                </h5>
              </div>
              <div class="card-body">
                <?php if ($podeEditar): ?>
                  <a href="<?php echo $h($basePath); ?>/ocorrencia/editar/<?php echo $h($idOcorrencia); ?>"
                     class="btn btn-warning btn-sm w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i>Editar ocorrência
                  </a>
                <?php endif; ?>

                <?php if ($podeVerMonitor): ?>
                  <a href="<?php echo $h($basePath); ?>/monitor"
                     class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-tools me-1"></i>Ver no Monitor
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card sr-card">
        <div class="sr-card-header">
          <h5 class="sr-card-title mb-0">
            <i class="bi bi-clock-history"></i>Histórico de Alterações
          </h5>
        </div>
        <div class="card-body">
          <?php if ($historico === []): ?>
            <p class="text-muted mb-0">Nenhum histórico registrado.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Status Anterior</th>
                    <th>Novo Status</th>
                    <th>Observação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($historico as $item): ?>
                    <?php $statusAnterior = $item['status_anterior'] ?? null; ?>
                    <tr>
                      <td><?php echo $formatarData($item['criado_em'] ?? null); ?></td>
                      <td><?php echo $h($valor($item, ['nome_usuario', 'usuario_nome'], '—')); ?></td>
                      <td>
                        <?php if ($statusAnterior === null || $statusAnterior === ''): ?>
                          <?php echo $h('—'); ?>
                        <?php else: ?>
                          <?php echo $statusBadge((string)$statusAnterior); ?>
                        <?php endif; ?>
                      </td>
                      <td><?php echo $statusBadge((string)($item['status_novo'] ?? '')); ?></td>
                      <td><?php echo $h($item['observacao'] ?? '—'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
