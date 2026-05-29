<?php
$error = isset($error) ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : null;
$email = htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8');
$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Acesso ao Sistema Relator de Problemas em Laboratorio" />
  <title>Login - Sistema Relator - UNIEINSTEIN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet" />
  <style>
    :root { --sr-primary:#1565c0; --sr-primary-dk:#0d47a1; }
    body { font-family:'DM Sans',sans-serif; }
    h1,h2,h3,h4,h5,label { font-family:'Poppins',sans-serif; }
    :focus-visible { outline:3px solid var(--sr-primary); outline-offset:2px; }
    .login-bg {
      min-height:100vh;
      background:linear-gradient(135deg,#0d2137 0%,#1565c0 60%,#0288d1 100%);
      display:flex; align-items:center; justify-content:center; padding:20px;
    }
    .login-card {
      background:#fff; border-radius:16px; padding:40px 36px 32px;
      width:100%; max-width:400px; box-shadow:0 20px 60px rgba(13,33,55,.35);
    }
    .login-logo {
      width:50px; height:50px; background:var(--sr-primary); border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      color:#fff; font-size:24px; margin:0 auto 18px;
    }
    .login-title { font-size:22px; font-weight:700; text-align:center; margin-bottom:4px; }
    .login-sub { font-size:13px; color:#6c757d; text-align:center; margin-bottom:26px; }
    .form-control { border-radius:8px; border-color:#dce3ea; font-size:13.5px; padding:10px 12px; height:auto; }
    .form-control:focus { border-color:var(--sr-primary); box-shadow:0 0 0 3px rgba(21,101,192,.12); }
    .input-group-text { background:#f8f9fa; border-color:#dce3ea; }
    .btn-login {
      width:100%; padding:11px; background:var(--sr-primary); color:#fff;
      border:none; border-radius:10px; font-size:15px; font-weight:600;
      font-family:'Poppins',sans-serif; cursor:pointer; transition:background .2s;
    }
    .btn-login:hover { background:var(--sr-primary-dk); }
    .btn-login:focus-visible { outline:3px solid #fff; outline-offset:2px; }
    .login-footer { text-align:center; margin-top:18px; font-size:11.5px; color:#90a4ae; }
  </style>
</head>
<body>
<div class="login-bg" role="main">
  <div class="login-card">
    <div class="login-logo" aria-hidden="true">
      <i class="bi bi-shield-lock-fill"></i>
    </div>

    <h1 class="login-title">Sistema Relator</h1>
    <p class="login-sub" id="login-desc">Problemas em Laboratorio - UNIEINSTEIN</p>

    <?php if (is_array($flash)): ?>
      <div class="alert alert-<?= htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8') ?> py-2 px-3 mb-3"
           role="alert" aria-live="polite" style="font-size:13px">
        <?= htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form action="/auth/login" method="POST" novalidate aria-labelledby="login-desc" id="login-form">
      <input type="hidden" name="csrf_token"
             value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />

      <div class="mb-3">
        <label for="email" class="form-label">
          E-mail institucional <span class="text-danger" aria-hidden="true">*</span>
          <span class="visually-hidden">(obrigatorio)</span>
        </label>
        <div class="input-group">
          <span class="input-group-text" id="icon-email" aria-hidden="true">
            <i class="bi bi-envelope"></i>
          </span>
          <input type="email" class="form-control" id="email" name="email"
                 placeholder="professor@unieinstein.edu.br"
                 autocomplete="email" required
                 aria-describedby="icon-email"
                 aria-required="true"
                 value="<?= $email ?>"
                 style="border-radius:0 8px 8px 0" />
        </div>
        <div class="invalid-feedback" role="alert">Informe um e-mail valido.</div>
      </div>

      <div class="mb-3">
        <label for="senha" class="form-label">
          Senha <span class="text-danger" aria-hidden="true">*</span>
          <span class="visually-hidden">(obrigatorio)</span>
        </label>
        <div class="input-group">
          <span class="input-group-text" id="icon-senha" aria-hidden="true">
            <i class="bi bi-lock"></i>
          </span>
          <input type="password" class="form-control" id="senha" name="senha"
                 placeholder="********"
                 autocomplete="current-password" required minlength="8"
                 aria-describedby="icon-senha"
                 aria-required="true"
                 style="border-radius:0 8px 8px 0" />
          <button type="button" class="btn btn-outline-secondary"
                  id="toggle-senha"
                  aria-label="Mostrar ou ocultar senha"
                  aria-pressed="false"
                  style="border-radius:0 8px 8px 0;border-color:#dce3ea">
            <i class="bi bi-eye" aria-hidden="true"></i>
          </button>
        </div>
        <div class="invalid-feedback" role="alert">Informe a senha (minimo 8 caracteres).</div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" />
          <label class="form-check-label" for="remember" style="font-size:12.5px">Manter conectado</label>
        </div>
        <a href="/auth/recuperar" style="font-size:12.5px;color:var(--sr-primary)">Esqueci a senha</a>
      </div>

      <?php if ($error !== null): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-3"
             role="alert" aria-live="assertive" style="font-size:13px">
          <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
          <div><?= $error ?></div>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn-login" id="btn-login">
        <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Entrar
      </button>
    </form>

    <p class="login-footer" role="contentinfo">
      <i class="bi bi-shield-check me-1" style="color:#198754" aria-hidden="true"></i>
      Acesso restrito - Autenticacao bcrypt
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/public/js/app.js"></script>
</body>
</html>
