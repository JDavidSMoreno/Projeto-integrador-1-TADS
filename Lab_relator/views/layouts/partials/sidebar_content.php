<?php
/**
 * Sidebar compartilhada entre desktop e offcanvas mobile.
 *
 * @var array<int, array<string, mixed>> $navItems
 * @var string $activeRoute
 * @var string $iniciais
 * @var string $nomeUsuario
 * @var string $rotuloTipo
 * @var string $tipoUsuario
 */
?>
<div class="sr-sidebar-brand d-flex align-items-center gap-2">
  <div class="sr-brand-logo" aria-hidden="true">
    <i class="bi bi-shield-lock-fill"></i>
  </div>
  <div>
    <div class="sr-brand-name">Sistema Relator</div>
    <div class="sr-brand-sub">UNIEINSTEIN</div>
  </div>
</div>

<div class="sr-nav flex-grow-1 px-2 py-2"
     role="navigation"
     data-role="<?= htmlspecialchars($tipoUsuario, ENT_QUOTES, 'UTF-8') ?>">
  <?php foreach ($navItems as $item): ?>
    <?php if (($item['type'] ?? '') === 'sep'): ?>
      <div class="sr-nav-sep">
        <?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php else: ?>
      <a href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>"
         <?= $activeRoute === ($item['route'] ?? '') ? 'class="active" aria-current="page"' : '' ?>
         aria-label="<?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi <?= htmlspecialchars((string)$item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
        <?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>
        <?php if (!empty($item['badge'])): ?>
          <span class="sr-badge" aria-label="<?= (int)$item['badge'] ?> chamados nao atendidos">
            <?= (int)$item['badge'] ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<div class="sr-user-bar">
  <div class="sr-avatar" aria-hidden="true">
    <?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?>
  </div>
  <div class="flex-grow-1 overflow-hidden">
    <div class="sr-user-name"><?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="sr-user-role"><?= htmlspecialchars($rotuloTipo, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
</div>
