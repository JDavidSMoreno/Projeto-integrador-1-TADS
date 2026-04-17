<?php
/**
 * views/ocorrencias/create.php
 * Registro/edição de ocorrência (RF: REGISTRO DE OCORRÊNCIAS – seção 3.3).
 * Regra negócio: edição permitida APENAS quando status = 'Nao Atendida'.
 * Variáveis do OcorrenciaController:
 *   array  $laboratorios   – lista de labs ativos
 *   array  $tiposProblema  – lista de tipos ativos
 *   ?array $ocorrencia     – dados para edição (null = nova)
 *   bool   $podeEditar     – false se status != 'Nao Atendida'
 *
 * Equipamentos carregados via AJAX em app.js:
 *   GET /equipamento/por-laboratorio?id_laboratorio={id}
 *   → JSON: [{"id":1,"nome":"Computador #01"},...]
 */
$pageTitle   = ($ocorrencia ?? null) ? 'Editar Ocorrência' : 'Registrar Ocorrência';
$activeRoute = 'ocorrencia-criar';
include __DIR__ . '/../layouts/header.php';

$laboratorios  = $laboratorios  ?? [];
$tiposProblema = $tiposProblema ?? [];
$ocorrencia    = $ocorrencia    ?? null;
$podeEditar    = $podeEditar    ?? true;
$idProf        = (int)($_SESSION['id_usuario'] ?? 0);
$nomeProf      = htmlspecialchars($_SESSION['nome_usuario'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<?php if (!$podeEditar): ?>
  <!-- Alerta bloqueio de edição (RF seção 1.2) -->
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-3"
       role="alert" aria-live="polite">
    <i class="bi bi-lock-fill flex-shrink-0" aria-hidden="true"></i>
    <div>
      <strong>Edição bloqueada.</strong>
      Esta ocorrência está em status
      <strong><?= htmlspecialchars($ocorrencia['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
      e não pode mais ser alterada.
      <a href="/ocorrencia" class="alert-link">Ver minhas ocorrências</a>
    </div>
  </div>
<?php else: ?>
  <!-- Aviso informativo sobre regra de bloqueio -->
  <div class="alert alert-info d-flex align-items-center gap-2 mb-3"
       role="note" style="background:#e3f2fd;border:none;border-left:4px solid #1565c0;border-radius:10px">
    <i class="bi bi-info-circle-fill text-primary flex-shrink-0" aria-hidden="true"></i>
    <div style="font-size:13px">
      <strong>Atenção:</strong> Após o início do atendimento pelo técnico,
      a ocorrência <strong>não poderá mais ser editada</strong>.
      Descreva o problema com o máximo de detalhes.
    </div>
  </div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-xl-8 col-lg-10">
    <div class="sr-card card">
      <div class="sr-card-header">
        <h2 class="sr-card-title h6">
          <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
          Dados da Ocorrência
        </h2>
        <span style="font-size:11.5px;color:#6c757d" aria-hidden="true">
          <span class="text-danger">*</span> campos obrigatórios
        </span>
      </div>

      <div class="p-4">
        <!-- action: /ocorrencia/registrar (POST) ou /ocorrencia/atualizar -->
        <form action="/ocorrencia/<?= $ocorrencia ? 'atualizar' : 'registrar' ?>"
              method="POST" novalidate id="form-ocorrencia"
              aria-label="<?= $ocorrencia ? 'Editar ocorrência' : 'Registrar nova ocorrência' ?>"
              <?= !$podeEditar ? 'aria-disabled="true"' : '' ?>>

          <input type="hidden" name="csrf_token"
                 value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="id_professor" value="<?= $idProf ?>" />
          <?php if ($ocorrencia): ?>
            <input type="hidden" name="id" value="<?= (int)($ocorrencia['id'] ?? 0) ?>" />
          <?php endif; ?>

          <fieldset <?= !$podeEditar ? 'disabled' : '' ?>>
            <legend class="visually-hidden">Dados da ocorrência</legend>

            <div class="row g-3">
              <!-- Laboratório -->
              <div class="col-md-6">
                <label for="oc-lab" class="form-label">
                  Laboratório <span class="text-danger" aria-hidden="true">*</span>
                  <span class="visually-hidden">(obrigatório)</span>
                </label>
                <select class="form-select" id="oc-lab" name="id_laboratorio"
                        required aria-required="true"
                        data-equip-target="oc-equip"
                        aria-describedby="oc-lab-help">
                  <option value="">Selecione o laboratório...</option>
                  <?php foreach ($laboratorios as $lab): ?>
                    <option value="<?= (int)$lab['id'] ?>"
                            <?= ($ocorrencia['id_laboratorio'] ?? 0) == $lab['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($lab['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="oc-lab-help" class="form-text">
                  Apenas laboratórios ativos são listados.
                </div>
                <div class="invalid-feedback" role="alert">Selecione o laboratório.</div>
              </div>

              <!-- Equipamento (AJAX) -->
              <div class="col-md-6">
                <label for="oc-equip" class="form-label">
                  Equipamento Afetado
                  <small class="text-muted fw-normal">(opcional)</small>
                </label>
                <select class="form-select" id="oc-equip" name="id_equipamento"
                        aria-describedby="oc-equip-help">
                  <option value="">
                    <?= ($ocorrencia['id_laboratorio'] ?? 0)
                        ? 'Nenhum (problema geral do laboratório)'
                        : 'Selecione o laboratório primeiro...' ?>
                  </option>
                  <?php if ($ocorrencia && !empty($ocorrencia['id_equipamento'])): ?>
                    <!-- Opção pré-selecionada ao editar -->
                    <option value="<?= (int)$ocorrencia['id_equipamento'] ?>" selected>
                      <?= htmlspecialchars($ocorrencia['equipamento_nome'] ?? 'Equipamento', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endif; ?>
                </select>
                <div id="oc-equip-help" class="form-text">
                  Deixe em branco para problema geral do laboratório.
                </div>
              </div>

              <!-- Tipo de Problema -->
              <div class="col-md-6">
                <label for="oc-tipo" class="form-label">
                  Tipo de Problema <span class="text-danger" aria-hidden="true">*</span>
                  <span class="visually-hidden">(obrigatório)</span>
                </label>
                <select class="form-select" id="oc-tipo" name="id_tipo_problema"
                        required aria-required="true">
                  <option value="">Selecione o tipo de problema...</option>
                  <?php foreach ($tiposProblema as $tipo): ?>
                    <option value="<?= (int)$tipo['id'] ?>"
                            <?= ($ocorrencia['id_tipo_problema'] ?? 0) == $tipo['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback" role="alert">Selecione o tipo de problema.</div>
              </div>

              <!-- Professor (somente leitura) -->
              <div class="col-md-6">
                <label for="oc-prof" class="form-label">Professor Responsável</label>
                <input type="text" class="form-control" id="oc-prof"
                       value="<?= $nomeProf ?>" readonly
                       aria-readonly="true"
                       style="background:#f8f9fa;color:#6c757d" />
              </div>

              <!-- Descrição -->
              <div class="col-12">
                <label for="oc-desc" class="form-label">
                  Descrição do Problema <span class="text-danger" aria-hidden="true">*</span>
                  <span class="visually-hidden">(obrigatório)</span>
                </label>
                <textarea class="form-control" id="oc-desc" name="descricao"
                          rows="5" required aria-required="true"
                          maxlength="65535"
                          placeholder="Descreva: quando aconteceu, equipamento afetado, se ocorreu antes, impacto na aula..."
                          aria-describedby="oc-desc-count"><?= htmlspecialchars($ocorrencia['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="d-flex justify-content-between mt-1">
                  <span class="form-text">Descreva com o máximo de detalhes possível.</span>
                  <span class="form-text" id="oc-desc-count" aria-live="polite">
                    <?= mb_strlen($ocorrencia['descricao'] ?? '', 'UTF-8') ?> / 1000 caracteres
                  </span>
                </div>
                <div class="invalid-feedback" role="alert">A descrição é obrigatória.</div>
              </div>
            </div><!-- /row -->

          </fieldset>

          <!-- Preview do chamado (acessível via aria-live) -->
          <div class="p-3 mt-3 rounded-3 border" style="background:#f8fbff" aria-live="polite">
            <div class="mb-2" style="font-size:11px;font-weight:700;color:#1565c0;
                text-transform:uppercase;letter-spacing:.05em">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Resumo do Chamado
            </div>
            <div class="row g-2" style="font-size:13px">
              <div class="col-4">
                <span class="text-muted d-block" style="font-size:11px">Professor</span>
                <strong><?= $nomeProf ?></strong>
              </div>
              <div class="col-4">
                <span class="text-muted d-block" style="font-size:11px">Status inicial</span>
                <span class="badge badge-na">Não Atendida</span>
              </div>
              <div class="col-4">
                <span class="text-muted d-block" style="font-size:11px">Data / Hora</span>
                <strong id="oc-now" aria-live="polite">--/--/---- --:--</strong>
              </div>
            </div>
          </div>

          <?php if ($podeEditar): ?>
            <div class="d-flex gap-3 mt-4">
              <button type="submit" class="btn btn-sr">
                <i class="bi bi-send me-2" aria-hidden="true"></i>
                <?= $ocorrencia ? 'Salvar Alterações' : 'Registrar Ocorrência' ?>
              </button>
              <a href="/ocorrencia" class="btn btn-outline-secondary" style="border-radius:8px">
                <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Cancelar
              </a>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
