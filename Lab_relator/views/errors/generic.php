<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Erro', ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
  <h1><?= htmlspecialchars($title ?? 'Erro', ENT_QUOTES, 'UTF-8') ?></h1>
  <p><?= htmlspecialchars($message ?? 'Nao foi possivel concluir a requisicao.', ENT_QUOTES, 'UTF-8') ?></p>
</body>
</html>
