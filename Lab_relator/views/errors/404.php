<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Pagina nao encontrada', ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4">404 - <?= htmlspecialchars($title ?? 'Pagina nao encontrada', ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-3"><?= htmlspecialchars($message ?? 'A rota solicitada nao foi encontrada.', ENT_QUOTES, 'UTF-8') ?></p>
        <a class="btn btn-primary" href="/dashboard">Voltar ao dashboard</a>
      </div>
    </div>
  </main>
</body>
</html>
