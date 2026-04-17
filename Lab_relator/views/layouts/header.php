<?php
/**
 * views/layouts/header.php
 * Layout principal: <head>, sidebar condicional por perfil, topbar.
 * Variáveis esperadas do Controller:
 *   string  $pageTitle   – título da página (ex.: "Laboratórios")
 *   string  $activeRoute – rota ativa para highlight do menu
 *   int     $totalAbertos – total de chamados não atendidos (sidebar badge, técnico)
 */
$tipoUsuario  = $_SESSION['tipo_usuario']  ?? 'professor';
$nomeUsuario  = htmlspecialchars($_SESSION['nome_usuario']  ?? 'Usuário',   ENT_QUOTES, 'UTF-8');
$emailUsuario = htmlspecialchars($_SESSION['email_usuario'] ?? '',           ENT_QUOTES, 'UTF-8');
$pageTitle    = htmlspecialchars($pageTitle  ?? 'Dashboard',                 ENT_QUOTES, 'UTF-8');
$activeRoute  = $activeRoute ?? '';
$totalAbertos = (int)($totalAbertos ?? 0);

/* Iniciais do avatar --------------------------------------------------- */
$partesNome = explode(' ', $nomeUsuario);

// Versão compatível sem mbstring (funciona em qualquer PHP)
$primeira = strtoupper(substr($partesNome[0] ?? '', 0, 1));
$segunda  = strtoupper(substr($partesNome[1] ?? '', 0, 1));
$iniciais = $primeira . $segunda;

/* Rótulo de perfil ------------------------------------------------------- */
$rotuloTipo = match($tipoUsuario) {
    'gestor'   => 'Gestor',
    'tecnico'  => 'Técnico',
    default    => 'Professor',
};

/* Itens de navegação por perfil (match PHP 8.0+) ------------------------ */
$navItems = match($tipoUsuario) {
    'gestor' => [
        ['label' => 'Dashboard',            'icon' => 'bi-grid-1x2',      'route' => 'dashboard',       'href' => '/dashboard'],
        ['type'  => 'sep', 'label' => 'Cadastros'],
        ['label' => 'Laboratórios',          'icon' => 'bi-building',       'route' => 'laboratorio',     'href' => '/laboratorio'],
        ['label' => 'Equipamentos',          'icon' => 'bi-pc-display',     'route' => 'equipamento',     'href' => '/equipamento'],
        ['label' => 'Professores',           'icon' => 'bi-person-badge',   'route' => 'professor',       'href' => '/usuario/professor'],
        ['label' => 'Técnicos',              'icon' => 'bi-tools',          'route' => 'tecnico',         'href' => '/usuario/tecnico'],
        ['label' => 'Tipos de Problema',     'icon' => 'bi-tags',           'route' => 'tipo-problema',   'href' => '/tipo-problema'],
        ['type'  => 'sep', 'label' => 'Ocorrências'],
        ['label' => 'Consultar Ocorrências', 'icon' => 'bi-journal-text',   'route' => 'ocorrencia',      'href' => '/ocorrencia'],
        ['label' => 'Relatórios',            'icon' => 'bi-bar-chart-line', 'route' => 'relatorio',       'href' => '/relatorio'],
    ],
    'tecnico' => [
        ['label' => 'Meu Painel',            'icon' => 'bi-grid-1x2',       'route' => 'dashboard',      'href' => '/dashboard'],
        ['type'  => 'sep', 'label' => 'Chamados'],
        ['label' => 'Monitor de Chamados',   'icon' => 'bi-kanban',          'route' => 'monitor',        'href' => '/monitor', 'badge' => $totalAbertos ?: null],
        ['label' => 'Histórico',             'icon' => 'bi-journal-check',   'route' => 'historico',      'href' => '/monitor/historico'],
    ],
    default => [ // professor
        ['label' => 'Meu Painel',            'icon' => 'bi-grid-1x2',       'route' => 'dashboard',      'href' => '/dashboard'],
        ['type'  => 'sep', 'label' => 'Ocorrências'],
        ['label' => 'Registrar Ocorrência',  'icon' => 'bi-plus-circle',    'route' => 'ocorrencia-criar','href' => '/ocorrencia/criar'],
        ['label' => 'Minhas Ocorrências',    'icon' => 'bi-journal-text',   'route' => 'ocorrencia',      'href' => '/ocorrencia'],
    ],
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Sistema Relator de Problemas em Laboratório – UNIEINSTEIN" />
  <title><?= $pageTitle ?> · Sistema Relator – UNIEINSTEIN</title>

  <!-- Bootstrap 5.3.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons 1.11.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <!-- Google Fonts: Poppins (headings) + DM Sans (body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet" />

  <style>
    /* ── Tokens ──────────────────────────────────────────────── */
    :root {
      --sr-sidebar-bg:      #0d2137;
      --sr-sidebar-hover:   #1a3a5c;
      --sr-sidebar-active:  #1565c0;
      --sr-sidebar-w:       260px;
      --sr-primary:         #1565c0;
      --sr-primary-dk:      #0d47a1;
      --sr-page-bg:         #eef2f7;
      --sr-radius:          10px;
      --sr-shadow:          0 2px 12px rgba(13,33,55,.09);
      --sr-font-head:       'Poppins', sans-serif;
      --sr-font-body:       'DM Sans', sans-serif;
    }
    body               { font-family: var(--sr-font-body); background: var(--sr-page-bg); }
    h1,h2,h3,h4,h5,h6  { font-family: var(--sr-font-head); }

    /* ── Acessibilidade: skip link & focus-visible ───────────── */
    .skip-link { position: absolute; top:-100px; left:16px; z-index:9999;
                 transition: top .15s; }
    .skip-link:focus-visible { top:12px; }
    :focus-visible { outline: 3px solid var(--sr-primary); outline-offset: 2px; }

    /* ── Sidebar (desktop ≥ lg) ──────────────────────────────── */
    .sr-sidebar {
      width: var(--sr-sidebar-w); min-height:100vh;
      background: var(--sr-sidebar-bg);
      position: fixed; top:0; left:0; z-index:200;
      display: flex; flex-direction: column;
      overflow-y: auto;
    }
    .sr-sidebar-brand { padding:18px 16px 14px; border-bottom:1px solid rgba(255,255,255,.08); }
    .sr-brand-logo    { width:36px; height:36px; background:var(--sr-primary); border-radius:8px;
                        display:inline-flex; align-items:center; justify-content:center;
                        color:#fff; font-size:18px; flex-shrink:0; }
    .sr-brand-name    { font-family:var(--sr-font-head); font-weight:700; font-size:13px; color:#fff; line-height:1.2; }
    .sr-brand-sub     { font-size:10px; color:#78909c; }
    .sr-nav-sep       { padding:12px 12px 2px; font-size:10px; font-weight:700; color:#546e7a;
                        letter-spacing:.08em; text-transform:uppercase; }
    .sr-nav a         { display:flex; align-items:center; gap:10px; padding:9px 12px;
                        border-radius:8px; color:#b0bec5; text-decoration:none;
                        font-size:13px; font-family:var(--sr-font-head); font-weight:500;
                        margin-bottom:1px; transition:background .15s, color .15s; }
    .sr-nav a:hover   { background:var(--sr-sidebar-hover); color:#fff; }
    .sr-nav a.active,[aria-current="page"]
                      { background:var(--sr-sidebar-active); color:#fff; }
    .sr-nav a i       { font-size:16px; flex-shrink:0; }
    .sr-nav a .sr-badge { margin-left:auto; background:#dc3545; color:#fff;
                          font-size:10px; padding:1px 7px; border-radius:10px; }
    .sr-user-bar      { padding:12px 14px; border-top:1px solid rgba(255,255,255,.08);
                        display:flex; align-items:center; gap:10px; }
    .sr-avatar        { width:34px; height:34px; border-radius:50%; background:var(--sr-primary);
                        display:flex; align-items:center; justify-content:center;
                        color:#fff; font-size:13px; font-weight:700;
                        font-family:var(--sr-font-head); flex-shrink:0; }
    .sr-user-name     { font-size:12px; font-weight:600; color:#eceff1;
                        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sr-user-role     { font-size:10px; color:#78909c; }

    /* ── Offcanvas sidebar (mobile < lg) ─────────────────────── */
    .offcanvas.sr-offcanvas { width:var(--sr-sidebar-w); background:var(--sr-sidebar-bg); }

    /* ── Topbar ──────────────────────────────────────────────── */
    .sr-topbar { background:#fff; border-bottom:1px solid #dee2e6;
                 height:58px; padding:0 20px; display:flex;
                 align-items:center; justify-content:space-between;
                 position:sticky; top:0; z-index:100; }
    .sr-page-title { font-family:var(--sr-font-head); font-size:15px;
                     font-weight:600; display:flex; align-items:center; gap:8px; }
    .sr-topbar-btn { background:none; border:none; padding:6px 8px;
                     border-radius:8px; color:#607d8b; font-size:20px;
                     position:relative; cursor:pointer; transition:background .15s; }
    .sr-topbar-btn:hover { background:#f0f4f8; }
    .sr-notif-dot { position:absolute; top:6px; right:6px; width:8px; height:8px;
                    background:#dc3545; border-radius:50%; border:2px solid #fff; }

    /* ── Main wrapper ────────────────────────────────────────── */
    .sr-main { margin-left:var(--sr-sidebar-w); display:flex; flex-direction:column; min-height:100vh; }
    @media (max-width:991.98px) { .sr-main { margin-left:0; } }
    .sr-content { padding:22px; flex:1; }

    /* ── Cards ───────────────────────────────────────────────── */
    .sr-card { border:none; border-radius:var(--sr-radius); box-shadow:var(--sr-shadow); }
    .sr-card-header { padding:14px 18px; border-bottom:1px solid #f0f0f0;
                      display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .sr-card-title  { font-family:var(--sr-font-head); font-size:14px; font-weight:600;
                      margin:0; display:flex; align-items:center; gap:7px; }
    .sr-card-title i { color:var(--sr-primary); }

    /* ── Stat cards ──────────────────────────────────────────── */
    .sr-stat { border-radius:var(--sr-radius); padding:16px 18px; background:#fff;
               box-shadow:var(--sr-shadow); border-left:4px solid var(--sr-primary);
               display:flex; align-items:center; gap:14px; }
    .sr-stat-icon { width:46px; height:46px; border-radius:12px;
                    display:flex; align-items:center; justify-content:center; font-size:20px; }
    .sr-stat-val   { font-size:26px; font-weight:700; font-family:var(--sr-font-head); line-height:1; }
    .sr-stat-lbl   { font-size:11px; color:#6c757d; font-weight:500; margin-top:2px; }

    /* ── Status badges ───────────────────────────────────────── */
    .badge-na   { background:#fdecea; color:#c62828; }
    .badge-ee   { background:#fff8e1; color:#f57f17; }
    .badge-ea   { background:#fff3e0; color:#e65100; }
    .badge-enc  { background:#e8f5e9; color:#2e7d32; }

    /* ── Tables ──────────────────────────────────────────────── */
    .sr-table thead th { font-family:var(--sr-font-head); font-size:11px; font-weight:700;
                         text-transform:uppercase; letter-spacing:.05em; color:#6c757d;
                         background:#fafbfc; border-bottom:2px solid #f0f0f0; padding:10px 14px; }
    .sr-table tbody td { padding:11px 14px; font-size:13px; vertical-align:middle;
                         border-bottom:1px solid #f5f5f5; }
    .sr-table tbody tr:hover { background:#f8fbff; }

    /* ── Forms ───────────────────────────────────────────────── */
    .form-label       { font-size:12.5px; font-weight:600; color:#37474f;
                        font-family:var(--sr-font-head); margin-bottom:4px; }
    .form-control,
    .form-select      { border-radius:8px; border-color:#dce3ea; font-size:13px; padding:8px 11px; }
    .form-control:focus,
    .form-select:focus { border-color:var(--sr-primary);
                         box-shadow:0 0 0 3px rgba(21,101,192,.12); }
    .input-group-text { background:#f8f9fa; border-color:#dce3ea; color:#607d8b; }

    /* ── Buttons ─────────────────────────────────────────────── */
    .btn-sr { background:var(--sr-primary); color:#fff; border:none;
              border-radius:8px; padding:8px 18px; font-size:13px;
              font-family:var(--sr-font-head); font-weight:600;
              transition:background .18s; }
    .btn-sr:hover { background:var(--sr-primary-dk); color:#fff; }

    /* ── Kanban (monitor) ────────────────────────────────────── */
    .kanban-col-head { border-radius:8px; padding:6px 12px; margin-bottom:10px;
                       font-family:var(--sr-font-head); font-size:11px; font-weight:700;
                       text-transform:uppercase; letter-spacing:.07em;
                       display:flex; align-items:center; gap:8px; }
    .chamado-card    { background:#fff; border-radius:var(--sr-radius); padding:13px 14px;
                       margin-bottom:8px; border-left:4px solid transparent;
                       box-shadow:var(--sr-shadow); transition:transform .15s; cursor:pointer; }
    .chamado-card:hover  { transform:translateY(-2px); }
    .chamado-card:focus-within { outline:3px solid var(--sr-primary); outline-offset:2px; }
    .chamado-card.c-na  { border-left-color:#dc3545; }
    .chamado-card.c-ea  { border-left-color:#fd7e14; }
    .chamado-card.c-enc { border-left-color:#198754; }
    .chamado-id    { font-size:10px; font-weight:700; color:#6c757d;
                     text-transform:uppercase; letter-spacing:.06em; }
    .chamado-titulo{ font-size:13px; font-weight:600; font-family:var(--sr-font-head); margin:2px 0; }
    .chamado-meta  { font-size:11px; color:#6c757d; display:flex; flex-wrap:wrap; gap:8px; margin-top:5px; }

    /* ── Gráfico de barras (relatorios) ──────────────────────── */
    .chart-wrap   { background:#f8fafc; border-radius:10px; padding:16px;
                    display:flex; align-items:flex-end; gap:10px; height:180px; }
    .chart-bar    { border-radius:5px 5px 0 0; width:32px; min-height:6px;
                    background:var(--sr-primary); transition:opacity .2s; }
    .chart-bar:hover { opacity:.75; }
    .chart-lbl    { font-size:10px; text-align:center; color:#6c757d; margin-top:4px;
                    font-family:var(--sr-font-head); }

    /* ── Empty state ─────────────────────────────────────────── */
    .sr-empty { text-align:center; padding:40px 20px; color:#90a4ae; }
    .sr-empty i { font-size:40px; display:block; margin-bottom:10px; }
    .sr-empty p { font-size:13px; margin:0; }

    /* ── Loading skeleton ────────────────────────────────────── */
    .skeleton { background:linear-gradient(90deg,#f0f4f8 25%,#e3ecf7 50%,#f0f4f8 75%);
                background-size:200% 100%; animation:shimmer 1.4s infinite;
                border-radius:6px; display:block; }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
  </style>
</head>
<body>

<!-- Skip navigation (WCAG 2.4.1) -->
<a class="skip-link visually-hidden-focusable btn btn-sm btn-sr"
   href="#main-content">Ir para o conteúdo principal</a>

<!-- ── SIDEBAR DESKTOP (d-none d-lg-flex) ─────────────────────────── -->
<nav class="sr-sidebar d-none d-lg-flex" aria-label="Menu de navegação principal">
  <?php include __DIR__ . '/partials/sidebar_content.php'; ?>
</nav>

<!-- ── SIDEBAR MOBILE – Offcanvas ─────────────────────────────────── -->
<div class="offcanvas sr-offcanvas d-lg-none" tabindex="-1"
     id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
  <div class="offcanvas-header p-0">
    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-2"
            data-bs-dismiss="offcanvas" aria-label="Fechar menu"></button>
  </div>
  <div class="offcanvas-body p-0">
    <?php include __DIR__ . '/partials/sidebar_content.php'; ?>
  </div>
</div>

<!-- ── MAIN WRAPPER ───────────────────────────────────────────────── -->
<div class="sr-main" id="sr-main-wrapper">

  <!-- Topbar -->
  <header class="sr-topbar" role="banner">
    <!-- Botão hamburguer (mobile) -->
    <button class="sr-topbar-btn d-lg-none me-2"
            data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"
            aria-controls="sidebarOffcanvas" aria-expanded="false"
            aria-label="Abrir menu de navegação">
      <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <div class="sr-page-title" aria-live="polite">
      <?= $pageTitle ?>
    </div>

    <div class="d-flex align-items-center gap-2">
      <?php if ($tipoUsuario === 'tecnico' && $totalAbertos > 0): ?>
        <a href="/monitor" class="position-relative sr-topbar-btn"
           aria-label="<?= $totalAbertos ?> chamado(s) não atendido(s)">
          <i class="bi bi-bell" aria-hidden="true"></i>
          <span class="sr-notif-dot" aria-hidden="true"></span>
        </a>
      <?php endif; ?>
      <div class="d-flex align-items-center gap-2 ms-1" aria-label="Usuário logado: <?= $nomeUsuario ?>, perfil: <?= $rotuloTipo ?>">
        <div class="sr-avatar" aria-hidden="true"><?= $iniciais ?></div>
        <div class="d-none d-md-block lh-1">
          <div style="font-size:12px;font-weight:600;color:#1a2a3a"><?= $nomeUsuario ?></div>
          <div style="font-size:10px;color:#78909c"><?= $rotuloTipo ?></div>
        </div>
      </div>
      <a href="/auth/logout" class="sr-topbar-btn"
         aria-label="Sair do sistema" title="Sair">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
      </a>
    </div>
  </header>

  <!-- Mensagens de feedback (flash) -->
  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 m-3 mb-0"
         role="alert" aria-live="polite">
      <i class="bi bi-check-circle-fill flex-shrink-0" aria-hidden="true"></i>
      <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>
  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-start gap-2 m-3 mb-0"
         role="alert" aria-live="assertive">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
      <ul class="mb-0 ps-3" style="font-size:13px">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <!-- Conteúdo principal (views incluem a partir daqui) -->
  <main id="main-content" class="sr-content" tabindex="-1">
<?php
/* sidebar_content.php é incluído tanto no desktop quanto no offcanvas mobile.
 * Cria-se inline via closure para evitar arquivo extra: */
if (!function_exists('sr_render_sidebar_content')) {
    function sr_render_sidebar_content(array $navItems, string $activeRoute, string $iniciais, string $nomeUsuario, string $rotuloTipo): void {
?>
  <div class="sr-sidebar-brand d-flex align-items-center gap-2">
    <div class="sr-brand-logo" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></div>
    <div>
      <div class="sr-brand-name">Sistema Relator</div>
      <div class="sr-brand-sub">UNIEINSTEIN</div>
    </div>
  </div>

  <div class="sr-nav flex-grow-1 px-2 py-2" role="navigation">
    <?php foreach ($navItems as $item): ?>
      <?php if (($item['type'] ?? '') === 'sep'): ?>
        <div class="sr-nav-sep"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php else: ?>
        <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
           <?= $activeRoute === $item['route'] ? 'class="active" aria-current="page"' : '' ?>
           aria-label="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
          <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
          <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
          <?php if (!empty($item['badge'])): ?>
            <span class="sr-badge" aria-label="<?= (int)$item['badge'] ?> chamados não atendidos">
              <?= (int)$item['badge'] ?>
            </span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="sr-user-bar">
    <div class="sr-avatar" aria-hidden="true"><?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="flex-grow-1 overflow-hidden">
      <div class="sr-user-name"><?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="sr-user-role"><?= htmlspecialchars($rotuloTipo, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>
<?php
    }
}
sr_render_sidebar_content($navItems, $activeRoute, $iniciais, $nomeUsuario, $rotuloTipo);
?>
