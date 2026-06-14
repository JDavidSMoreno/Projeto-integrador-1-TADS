<?php
// Arquivo: views/auth/recuperar.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

$error = isset($error) ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : null;
$success = isset($success) ? htmlspecialchars($success, ENT_QUOTES, 'UTF-8') : null;
$email = htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8');
$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar senha - Sistema Relator</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5" style="max-width:520px">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <h1 class="h4 mb-2">Recuperacao de senha</h1>
        <p class="text-muted mb-4">Informe o e-mail institucional para gerar um link temporario.</p>

        <?php if (is_array($flash)): ?>
          <div class="alert alert-<?= htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success !== null): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- ── INÍCIO CORREÇÃO QA ── -->
        <!-- Link de redefinicao nunca e exibido em output HTTP. -->
        <!-- ── FIM CORREÇÃO QA ── -->

        <form action="/auth/recuperar" method="POST" novalidate>
          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label for="email" class="form-label">E-mail institucional</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= $email ?>" required autocomplete="email"
                   placeholder="professor@unieinstein.edu.br">
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-send me-1" aria-hidden="true"></i>Gerar link
            </button>
            <a class="btn btn-outline-secondary" href="/auth/login">Voltar</a>
          </div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>
