<?php
/**
 * views/usuario/tecnicos.php
 * CRUD de técnicos (RF: CADASTRO DE TÉCNICOS – seção 3.3).
 * Variáveis do UsuarioController:
 *   array  $tecnicos  – lista de usuários tipo=tecnico (com total_abertos)
 *   ?array $tecnico   – dados para edição (null = novo)
 */
$pageTitle   = 'Técnicos';
$activeRoute = 'tecnico';
include __DIR__ . '/../layouts/header.php';

$tecnico  = $tecnico  ?? null;
$tecnicos = $tecnicos ?? [];
?>

<div class="row g-3">

  <!-- ── Formulário ──────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi <?= $tecnico ? 'bi-pencil-square' : 'bi-person-plus' ?>" aria-hidden="true"></i>
          <?= $tecnico ? 'Editar Técnico' : 'Novo Técnico' ?>
        </h2>
      </div>
      <div class="p-3">
        <form action="/usuario/<?= $tecnico ? 'atualizar' : 'salvar' ?>"
              method="POST" novalidate id="form-tecnico"
              aria-label="<?= $tecnico ? 'Editar técnico' : 'Cadastrar novo técnico' ?>">

          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="tipo" value="tecnico" />
          <?php if ($tecnico): ?>
            <input type="hidden" name="id" value="<?= (int)($tecnico['id'] ?? 0) ?>" />
          <?php endif; ?>

          <!-- Nome -->
          <div class="mb-3">
            <label for="tec-nome" class="form-label">
              Nome Completo <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="text" class="form-control" id="tec-nome" name="nome"
                   maxlength="100" required aria-required="true"
                   placeholder="Ex.: Téc. João Lima"
                   value="<?= htmlspecialchars($tecnico['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="invalid-feedback" role="alert">O nome é obrigatório.</div>
          </div>

          <!-- E-mail -->
          <div class="mb-3">
            <label for="tec-email" class="form-label">
              E-mail Institucional <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="email" class="form-control" id="tec-email" name="email"
                   maxlength="150" required aria-required="true"
                   autocomplete="off"
                   placeholder="tecnico@unieinstein.edu.br"
                   value="<?= htmlspecialchars($tecnico['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="invalid-feedback" role="alert">Informe um e-mail válido.</div>
          </div>

          <!-- Senha (apenas no cadastro) -->
          <?php if (!$tecnico): ?>
            <div class="mb-3">
              <label for="tec-senha" class="form-label">
                Senha Inicial <span class="text-danger" aria-hidden="true">*</span>
                <span class="visually-hidden">(obrigatório)</span>
              </label>
              <div class="input-group">
                <input type="password" class="form-control" id="tec-senha" name="senha"
                       minlength="8" maxlength="255" required aria-required="true"
                       autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres"
                       style="border-radius:8px 0 0 8px" />
                <button type="button" class="btn btn-outline-secondary sr-toggle-pw"
                        data-target="tec-senha"
                        aria-label="Mostrar ou ocultar senha" aria-pressed="false"
                        style="border-radius:0 8px 8px 0;border-color:#dce3ea">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
              </div>
              <div class="invalid-feedback" role="alert">A senha deve ter no mínimo 8 caracteres.</div>
            </div>
          <?php endif; ?>

          <!-- Status -->
          <div class="mb-3">
            <label for="tec-ativo" class="form-label">Status</label>
            <select class="form-select" id="tec-ativo" name="ativo">
              <option value="1" <?= ($tecnico['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= ($tecnico['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <?php if ($tecnico): ?>
              <a href="/usuario/tecnico" class="btn btn-outline-secondary"
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
  </div>

  <!-- ── Listagem ─────────────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-wrench-adjustable-circle" aria-hidden="true"></i>
          Técnicos Cadastrados
        </h2>
      </div>
      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de técnicos">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Técnico</th>
              <th scope="col">E-mail</th>
              <th scope="col">Chamados Abertos</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tecnicos)): ?>
              <tr>
                <td colspan="6">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-tools" aria-hidden="true"></i>
                    <p>Nenhum técnico cadastrado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tecnicos as $tec): ?>
                <?php
                $partesNomeTec = explode(' ', $tec['nome']);
                $iniciaisTec = mb_strtoupper(
                    mb_substr($partesNomeTec[0], 0, 1, 'UTF-8') .
                    (isset($partesNomeTec[1]) ? mb_substr($partesNomeTec[1], 0, 1, 'UTF-8') : ''),
                    'UTF-8'
                );
                $abertos = (int)($tec['total_abertos'] ?? 0);
                ?>
                <tr>
                  <td><span class="text-muted" style="font-size:11px">
                    #<?= str_pad((int)$tec['id'], 2, '0', STR_PAD_LEFT) ?>
                  </span></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="sr-avatar" style="width:30px;height:30px;font-size:11px;flex-shrink:0;background:#2e7d32"
                           aria-hidden="true">
                        <?= htmlspecialchars($iniciaisTec, ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <span style="font-weight:600;font-family:'Poppins',sans-serif;font-size:13px">
                        <?= htmlspecialchars($tec['nome'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </div>
                  </td>
                  <td style="font-size:12.5px">
                    <?= htmlspecialchars($tec['email'], ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td>
                    <?php if ($abertos > 0): ?>
                      <span class="badge bg-warning text-dark"
                            aria-label="<?= $abertos ?> chamado(s) em atendimento">
                        <?= $abertos ?> em atendimento
                      </span>
                    <?php else: ?>
                      <span class="badge bg-light text-secondary border"
                            aria-label="Nenhum chamado aberto">Nenhum</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= $tec['ativo']
                      ? '<span class="badge badge-enc">Ativo</span>'
                      : '<span class="badge badge-na">Inativo</span>' ?>
                  </td>
                  <td class="text-end">
                    <a href="/usuario/tecnico/editar/<?= (int)$tec['id'] ?>"
                       class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px"
                       aria-label="Editar técnico <?= htmlspecialchars($tec['nome'], ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="/usuario/status" method="POST" class="d-inline">
                      <input type="hidden" name="csrf_token"
                             value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="id" value="<?= (int)$tec['id'] ?>" />
                      <input type="hidden" name="ativo" value="<?= $tec['ativo'] ? 0 : 1 ?>" />
                      <button type="submit"
                              class="btn btn-sm <?= $tec['ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                              style="border-radius:6px"
                              aria-label="<?= $tec['ativo'] ? 'Inativar' : 'Ativar' ?> técnico <?= htmlspecialchars($tec['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi <?= $tec['ativo'] ? 'bi-slash-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
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
