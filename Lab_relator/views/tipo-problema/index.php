<?php
/**
 * views/tipo-problema/index.php
 * Cadastro de tipos de problema.
 */
$pageTitle   = 'Tipos de Problema';
$activeRoute = 'tipo-problema';
include __DIR__ . '/../layouts/header.php';

$tipos = $tipos ?? [];
?>

<div class="row g-3">

  <div class="col-lg-4">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-plus-circle" aria-hidden="true"></i>
          Novo Tipo de Problema
        </h2>
      </div>
      <div class="p-3">
        <form action="/tipo-problema/salvar" method="POST" novalidate aria-label="Cadastrar tipo de problema">
          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />

          <div class="mb-3">
            <label for="tp-descricao" class="form-label">
              Descrição do Tipo <span class="text-danger" aria-hidden="true">*</span>
              <span class="visually-hidden">(obrigatório)</span>
            </label>
            <input type="text" id="tp-descricao" name="descricao" class="form-control"
                   maxlength="100" required aria-required="true"
                   placeholder="Ex.: Falha no projetor" />
            <div class="invalid-feedback" role="alert">A descrição é obrigatória.</div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sr flex-fill">
              <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Salvar
            </button>
            <button type="reset" class="btn btn-outline-secondary" style="border-radius:8px" aria-label="Limpar formulário">
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-tags" aria-hidden="true"></i>
          Tipos de Problema Cadastrados
        </h2>
      </div>

      <div class="table-responsive">
        <table class="sr-table table mb-0" aria-label="Lista de tipos de problema">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Descrição</th>
              <th scope="col"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tipos)): ?>
              <tr>
                <td colspan="3">
                  <div class="sr-empty" role="status">
                    <i class="bi bi-tags" aria-hidden="true"></i>
                    <p>Nenhum tipo de problema cadastrado.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($tipos as $t): ?>
                <tr>
                  <td>
                    <span class="text-muted" style="font-size:11px">
                      #<?= str_pad((int)$t['id'], 3, '0', STR_PAD_LEFT) ?>
                    </span>
                  </td>
                  <td>
                    <span style="font-weight:600;font-family:'Poppins',sans-serif;font-size:13px">
                      <?= htmlspecialchars($t['descricao'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius:6px" disabled aria-disabled="true" title="Edição em breve">
                      <i class="bi bi-pencil" aria-hidden="true"></i>
                    </button>
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