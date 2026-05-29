<?php
$error = isset($error) ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : null;
$token = htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8');
$reset = $reset ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nova senha - Sistema Relator</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5" style="max-width:520px">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <h1 class="h4 mb-2">Definir nova senha</h1>

        <?php if ($error !== null): ?>
          <div class="alert alert-danger mt-3"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($reset === false): ?>
          <p class="text-muted mb-3">Este link de recuperacao e invalido ou expirou.</p>
          <a class="btn btn-primary" href="/auth/recuperar">Gerar novo link</a>
        <?php else: ?>
          <p class="text-muted mb-4">
            Link valido para <?= htmlspecialchars((string)($reset['usuario_email'] ?? $reset['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.
          </p>

          <form action="/auth/resetar/<?= $token ?>" method="POST" novalidate>
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
              <label for="senha" class="form-label">Nova senha</label>
              <input type="password" class="form-control" id="senha" name="senha"
                     minlength="8" required autocomplete="new-password">
            </div>

            <div class="mb-3">
              <label for="senha_confirmacao" class="form-label">Confirmar nova senha</label>
              <input type="password" class="form-control" id="senha_confirmacao" name="senha_confirmacao"
                     minlength="8" required autocomplete="new-password">
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Alterar senha
              </button>
              <a class="btn btn-outline-secondary" href="/auth/login">Cancelar</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
