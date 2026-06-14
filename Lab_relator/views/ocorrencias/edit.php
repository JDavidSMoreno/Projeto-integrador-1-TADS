<?php
// Arquivo: views/ocorrencias/edit.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Tarefa: view de edição de ocorrência

$ocorrencia = is_array($ocorrencia ?? null) ? $ocorrencia : [];
$laboratorios = is_array($laboratorios ?? null) ? $laboratorios : [];
$equipamentos = is_array($equipamentos ?? null) ? $equipamentos : [];
$tiposProblema = is_array($tipos_problema ?? null)
    ? $tipos_problema
    : (is_array($tiposProblema ?? null) ? $tiposProblema : []);
$erros = is_array($erros ?? null) ? $erros : (is_array($errors ?? null) ? $errors : []);
$basePath = defined('APP_BASE_PATH') ? (string)APP_BASE_PATH : '';

$pageTitle = 'Editar Ocorrência #' . (string)($ocorrencia['id'] ?? '');
$activeRoute = 'ocorrencia';

$h = static fn (mixed $valor): string => htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
$erroClasse = static fn (array $erros, string $campo): string => isset($erros[$campo]) ? ' is-invalid' : '';
$erroMensagem = static function (array $erros, string $campo) use ($h): void {
    if (!isset($erros[$campo])) {
        return;
    }

    echo '<div class="invalid-feedback">' . $h($erros[$campo]) . '</div>';
};

$idOcorrencia = (int)($ocorrencia['id'] ?? 0);
$idLaboratorioAtual = (string)($ocorrencia['id_laboratorio'] ?? '');
$idEquipamentoAtual = (string)($ocorrencia['id_equipamento'] ?? '');
$idTipoAtual = (string)($ocorrencia['id_tipo_problema'] ?? '');
$descricaoAtual = (string)($ocorrencia['descricao'] ?? '');

include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
          <i class="bi bi-pencil me-2"></i>Editar Ocorrência #<?php echo $h($idOcorrencia); ?>
        </h4>
        <a href="<?php echo $h($basePath); ?>/ocorrencia/ver/<?php echo $h($idOcorrencia); ?>"
           class="btn btn-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
      </div>

      <div class="alert alert-info" role="alert">
        Esta ocorrência só pode ser editada enquanto estiver com status Não Atendida.
        Após o início do atendimento, não será possível realizar alterações.
      </div>

      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo $h($_SESSION['flash_success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $h($_SESSION['flash_error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>

      <?php if (!empty($warning)): ?>
        <div class="alert alert-warning" role="alert">
          <?php echo $h($warning); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($erros['geral'])): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo $h($erros['geral']); ?>
        </div>
      <?php endif; ?>

      <div class="card sr-card">
        <div class="sr-card-header">
          <h5 class="sr-card-title mb-0">
            <i class="bi bi-exclamation-triangle"></i>Dados da Ocorrência
          </h5>
        </div>
        <div class="card-body">
          <form action="<?php echo $h($basePath); ?>/ocorrencia/atualizar/<?php echo $h($idOcorrencia); ?>"
                method="POST"
                novalidate
                id="form-ocorrencia">
            <input type="hidden" name="_csrf_token"
                   value="<?php echo $h($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="row g-3">
              <div class="col-md-6">
                <label for="oc-lab" class="form-label">
                  Laboratório <span class="text-danger">*</span>
                </label>
                <select class="form-select<?php echo $erroClasse($erros, 'id_laboratorio'); ?>"
                        id="oc-lab"
                        name="id_laboratorio"
                        required>
                  <option value="">Selecione o laboratório...</option>
                  <?php foreach ($laboratorios as $laboratorio): ?>
                    <?php $idLaboratorioOpcao = (string)($laboratorio['id'] ?? ''); ?>
                    <option value="<?php echo $h($idLaboratorioOpcao); ?>"
                            <?php echo $idLaboratorioOpcao === $idLaboratorioAtual ? 'selected' : ''; ?>>
                      <?php echo $h($laboratorio['nome'] ?? ''); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php $erroMensagem($erros, 'id_laboratorio'); ?>
              </div>

              <div class="col-md-6">
                <label for="oc-equip" class="form-label">Equipamento</label>
                <select class="form-select<?php echo $erroClasse($erros, 'id_equipamento'); ?>"
                        id="oc-equip"
                        name="id_equipamento">
                  <option value="">Nenhum equipamento específico</option>
                  <?php foreach ($equipamentos as $equipamento): ?>
                    <?php $idEquipamentoOpcao = (string)($equipamento['id'] ?? ''); ?>
                    <option value="<?php echo $h($idEquipamentoOpcao); ?>"
                            <?php echo $idEquipamentoOpcao === $idEquipamentoAtual ? 'selected' : ''; ?>>
                      <?php echo $h($equipamento['nome'] ?? ''); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php $erroMensagem($erros, 'id_equipamento'); ?>
                <div class="form-text">
                  Os equipamentos são carregados conforme o laboratório selecionado.
                </div>
              </div>

              <div class="col-md-6">
                <label for="oc-tipo" class="form-label">
                  Tipo de Problema <span class="text-danger">*</span>
                </label>
                <select class="form-select<?php echo $erroClasse($erros, 'id_tipo_problema'); ?>"
                        id="oc-tipo"
                        name="id_tipo_problema"
                        required>
                  <option value="">Selecione o tipo de problema...</option>
                  <?php foreach ($tiposProblema as $tipo): ?>
                    <?php $idTipoOpcao = (string)($tipo['id'] ?? ''); ?>
                    <option value="<?php echo $h($idTipoOpcao); ?>"
                            <?php echo $idTipoOpcao === $idTipoAtual ? 'selected' : ''; ?>>
                      <?php echo $h($tipo['descricao'] ?? $tipo['nome'] ?? ''); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php $erroMensagem($erros, 'id_tipo_problema'); ?>
              </div>

              <div class="col-12">
                <label for="oc-desc" class="form-label">
                  Descrição <span class="text-danger">*</span>
                </label>
                <textarea class="form-control<?php echo $erroClasse($erros, 'descricao'); ?>"
                          id="oc-desc"
                          name="descricao"
                          rows="5"
                          required
                          minlength="10"
                          maxlength="5000"
                          data-counter="descricao"><?php echo $h($descricaoAtual); ?></textarea>
                <?php $erroMensagem($erros, 'descricao'); ?>
                <div class="d-flex justify-content-between mt-1">
                  <span class="form-text">Informe o problema com clareza para orientar o atendimento.</span>
                  <span class="form-text" id="oc-desc-count">
                    <?php echo $h(function_exists('mb_strlen') ? mb_strlen($descricaoAtual, 'UTF-8') : strlen($descricaoAtual)); ?> / 5000 caracteres
                  </span>
                </div>
              </div>
            </div>

            <div class="row mt-4">
              <div class="col-md-6">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check-lg me-1"></i>Salvar alterações
                </button>
              </div>
              <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <a href="<?php echo $h($basePath); ?>/ocorrencia/ver/<?php echo $h($idOcorrencia); ?>"
                   class="btn btn-outline-secondary">
                  <i class="bi bi-x-circle me-1"></i>Cancelar
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
