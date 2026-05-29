<?php
$nome = htmlspecialchars((string)($user['nome'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
$perfil = (string)($perfil ?? 'professor');
$usuariosPorPerfil = $usuariosPorPerfil ?? ['gestor' => 0, 'professor' => 0, 'tecnico' => 0];
$ocorrencias = $ocorrencias ?? ['total' => 0, 'nao_atendida' => 0, 'em_atendimento' => 0, 'encerrada' => 0];
?>

<?php if (!empty($warning)): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
    <div><?= htmlspecialchars((string)$warning, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1">Dashboard</h1>
    <p class="text-muted mb-0">Bem-vindo, <?= $nome ?>.</p>
  </div>
  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
    <?= htmlspecialchars(ucfirst($perfil), ENT_QUOTES, 'UTF-8') ?>
  </span>
</div>

<?php if ($perfil === 'gestor'): ?>
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="sr-stat">
        <div class="sr-stat-icon" style="background:#e3f2fd">
          <i class="bi bi-people text-primary" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$usuariosAtivos ?></div>
          <div class="sr-stat-lbl">Usuarios Ativos</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="sr-stat" style="border-left-color:#198754">
        <div class="sr-stat-icon" style="background:#e8f5e9">
          <i class="bi bi-person-badge text-success" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$usuariosPorPerfil['professor'] ?></div>
          <div class="sr-stat-lbl">Professores</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="sr-stat" style="border-left-color:#fd7e14">
        <div class="sr-stat-icon" style="background:#fff3e0">
          <i class="bi bi-tools" style="color:#fd7e14" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$usuariosPorPerfil['tecnico'] ?></div>
          <div class="sr-stat-lbl">Tecnicos</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="sr-stat" style="border-left-color:#dc3545">
        <div class="sr-stat-icon" style="background:#fdecea">
          <i class="bi bi-shield-lock text-danger" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$loginFailures24h ?></div>
          <div class="sr-stat-lbl">Falhas Login 24h</div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="sr-stat">
        <div class="sr-stat-icon" style="background:#e3f2fd">
          <i class="bi bi-folder2-open text-primary" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$ocorrencias['total'] ?></div>
          <div class="sr-stat-lbl"><?= $perfil === 'tecnico' ? 'Chamados Visiveis' : 'Minhas Ocorrencias' ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="sr-stat" style="border-left-color:#dc3545">
        <div class="sr-stat-icon" style="background:#fdecea">
          <i class="bi bi-inbox text-danger" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$ocorrencias['nao_atendida'] ?></div>
          <div class="sr-stat-lbl">Nao Atendidas</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="sr-stat" style="border-left-color:#fd7e14">
        <div class="sr-stat-icon" style="background:#fff3e0">
          <i class="bi bi-gear text-warning" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$ocorrencias['em_atendimento'] ?></div>
          <div class="sr-stat-lbl">Em Atendimento</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="sr-stat" style="border-left-color:#198754">
        <div class="sr-stat-icon" style="background:#e8f5e9">
          <i class="bi bi-check2-circle text-success" aria-hidden="true"></i>
        </div>
        <div>
          <div class="sr-stat-val"><?= (int)$ocorrencias['encerrada'] ?></div>
          <div class="sr-stat-lbl">Encerradas</div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-activity" aria-hidden="true"></i>
          Visao do Perfil
        </h2>
      </div>
      <div class="p-3">
        <?php if ($perfil === 'gestor'): ?>
          <p class="mb-0 text-muted">Acompanhe cadastros, seguranca de login e relatorios gerenciais.</p>
        <?php elseif ($perfil === 'tecnico'): ?>
          <p class="mb-0 text-muted">Priorize chamados nao atendidos e finalize atendimentos em andamento.</p>
        <?php else: ?>
          <p class="mb-0 text-muted">Registre problemas encontrados nos laboratorios e acompanhe suas ocorrencias.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-key" aria-hidden="true"></i>
          Recuperacao
        </h2>
      </div>
      <div class="p-3">
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Tokens ativos</span>
          <strong><?= (int)$passwordResetsAbertos ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>
