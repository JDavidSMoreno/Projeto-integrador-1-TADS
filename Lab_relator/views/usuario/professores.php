<?php
/**
 * views/usuario/professores.php
 * CRUD de professores (RF: CADASTRO DE PROFESSORES – seção 3.3).
 * Variáveis do UsuarioController:
 *   array  $professores – lista de usuários tipo=professor
 *   ?array $professor   – dados para edição (null = novo)
 */
$pageTitle   = 'Professores';
$activeRoute = 'professor';
include __DIR__ . '/../layouts/header.php';

$professor  = $professor  ?? null;
$professores = $professores ?? [];
?>

<div class="row g-3">

  <!-- ── Formulário ──────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $professor ? 'bi-pencil-square' : 'bi-person-plus' ?>" aria-hidden="true"></i>
          <?= $professor ? 'Editar Professor' : 'Novo Professor' ?>
        </h2>
      </div>
      <div class="p-3">
        <form action="/usuario/<?= $professor ? 'atualizar' : 'salvar' ?>"
              method="POST" novalidate id="form-professor"
              aria-label="<?= $professor ? 'Editar professor' : 'Cadastrar novo professor' ?>">

          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="tipo" value="professor" />
          <?php if ($professor): ?>
            <input type="hidden" name="id" value="<?= (int)($professor['id'] ?? 0) ?>" />
          <?php endif; ?>

          <!-- Nome -->
          <div class="mb-3">
            <label for="prof-nome" class="form-label">
              Nome Completo <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="text" class="form-control" id="prof-nome" name="nome"
                   maxlength="100" required aria-required="true"
                   placeholder="Ex.: Prof. Carlos Souza"
                   value="<?= htmlspecialchars($professor['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="invalid-feedback" role="alert">O nome é obrigatório.</div>
          </div>

          <!-- E-mail -->
          <div class="mb-3">
            <label for="prof-email" class="form-label">
              E-mail Institucional <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="email" class="form-control" id="prof-email" name="email"
                   maxlength="150" required aria-required="true"
                   autocomplete="off"
                   placeholder="professor@unieinstein.edu.br"
                   aria-describedby="prof-email-help"
                   value="<?= htmlspecialchars($professor['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div id="prof-email-help" class="form-text">
              Usado para login e recebimento de notificações (seção 3.3 – ENVIO DE E-MAILS).
            </div>
            <div class="invalid-feedback" role="alert">Informe um e-mail válido.</div>
          </div>

          <!-- Senha (apenas no cadastro; na edição, campo separado) -->
          <?php if (!$professor): ?>
            <div class="mb-3">
              <label for="prof-senha" class="form-label">
                Senha Inicial <span class="text-danger" aria-hidden="true">*</span>
                <span class="visually-hidden">(obrigatório)</span>
              </label>
              <div class="input-group">
                <input type="password" class="form-control" id="prof-senha" name="senha"
                       minlength="8" maxlength="255" required aria-required="true"
                       autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres"
                       aria-describedby="prof-senha-help"
                       style="border-radius:8px 0 0 8px" />
                <button type="button" class="btn btn-outline-secondary sr-toggle-pw"
                        data-target="prof-senha"
                        aria-label="Mostrar ou ocultar senha" aria-pressed="false"
                        style="border-radius:0 8px 8px 0;border-color:#dce3ea">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
              </div>
              <div id="prof-senha-help" class="form-text">
                O professor poderá alterar no primeiro acesso.
              </div>
              <div class="invalid-feedback" role="alert">
                A senha deve ter no mínimo 8 caracteres.
              </div>
            </div>
          <?php endif; ?>

          <!-- Status -->
          <div class="mb-3">
            <label for="prof-ativo" class="form-label">Status</label>
            <select class="form-select" id="prof-ativo" name="ativo">
              <option value="1" <?= ($professor['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= ($professor['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <?php if ($professor): ?>
              <a href="/usuario/professor" class="btn btn-outline-secondary"
                 style="border-radius:8px" aria-label="Cancelar edição">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </a>
            <?php else: ?>
              <button type="reset" class="btn btn-outline-secondary"
                      style="border-radius:8px" aria-label="Limpar formulário">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div><!-- /col formulário -->

  <!-- ── Listagem ─────────────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="sr-card card">
      <div class="sr-card-header flex-wrap gap-2">
        <h2 class="sr-card-title h6">
          <i class="bi bi-people" aria-hidden="true"></i>
          Professores Cadastrados
        </h2>
        <form action="/usuario/professor" method="GET" role="search"
              aria-label="Buscar professor">
          <div class="input-group" style="max-width:220px">
            <label for="busca-prof" class="visually-hidden">Buscar professor</label>
            <span class="input-group-text" aria-hidden="true">
              <i class="bi bi-search"></i>
            </span>
            <input type="search" class="form-control" id="busca-prof" name="busca"
                   placeholder="Buscar..." style="border-radius:0 8px 8px 0" />
          </div>
          <button type="submit" class="visually-hidden">Buscar</button>
        </form>
      </div>

      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de professores">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Professor</th>
              <th scope="col">E-mail</th>
              <th scope="col">Ocorrências</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($professores)): ?>
              <tr>
                <td colspan="6">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <p>Nenhum professor cadastrado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($professores as $prof): ?>
                <?php
                $partesNomeProf = explode(' ', $prof['nome']);
                $iniciaisProf = mb_strtoupper(
                    mb_substr($partesNomeProf[0], 0, 1, 'UTF-8') .
                    (isset($partesNomeProf[1]) ? mb_substr($partesNomeProf[1], 0, 1, 'UTF-8') : ''),
                    'UTF-8'
                );
                ?>
                <tr>
                  <td><span class="text-muted" style="font-size:11px">
                    #<?= str_pad((int)$prof['id'], 2, '0', STR_PAD_LEFT) ?>
                  </span></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="sr-avatar" style="width:30px;height:30px;font-size:11px;flex-shrink:0"
                           aria-hidden="true">
                        <?= htmlspecialchars($iniciaisProf, ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <span style="font-weight:600;font-family:'Poppins',sans-serif;font-size:13px">
                        <?= htmlspecialchars($prof['nome'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </div>
                  </td>
                  <td style="font-size:12.5px">
                    <?= htmlspecialchars($prof['email'], ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border" aria-label="<?= (int)($prof['total_ocorrencias'] ?? 0) ?> ocorrências">
                      <?= (int)($prof['total_ocorrencias'] ?? 0) ?>
                    </span>
                  </td>
                  <td>
                    <?= $prof['ativo']
                      ? '<span class="badge badge-enc">Ativo</span>'
                      : '<span class="badge badge-na">Inativo</span>' ?>
                  </td>
                  <td class="text-end">
                    <a href="/usuario/professor/editar/<?= (int)$prof['id'] ?>"
                       class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px"
                       aria-label="Editar professor <?= htmlspecialchars($prof['nome'], ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="/usuario/status" method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token"
                             value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="id" value="<?= (int)$prof['id'] ?>" />
                      <input type="hidden" name="ativo" value="<?= $prof['ativo'] ? 0 : 1 ?>" />
                      <button type="submit"
                              class="btn btn-sm <?= $prof['ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              aria-label="<?= $prof['ativo'] ? 'Inativar' : 'Ativar' ?> professor <?= htmlspecialchars($prof['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi <?= $prof['ativo'] ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
