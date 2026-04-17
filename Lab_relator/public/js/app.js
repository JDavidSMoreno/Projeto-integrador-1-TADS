/**
 * public/js/app.js
 * Sistema Relator de Problemas em Laboratório – UNIEINSTEIN
 * JavaScript ES2025 – cliente-side complementar ao Bootstrap 5.3.3
 *
 * Módulos:
 *   1. Bootstrap form validation
 *   2. Toggle visibilidade de senha
 *   3. Select dinâmico de equipamentos por laboratório (AJAX)
 *   4. Contador de caracteres em textareas
 *   5. Clock em tempo real (preview do chamado)
 *   6. Confirmação de ações destrutivas
 *   7. Toast helper
 *   8. Auto-refresh do monitor de chamados
 *   9. Inicialização geral (DOMContentLoaded)
 */

'use strict';

/* ════════════════════════════════════════════════════════════════
   1. Bootstrap 5 Form Validation (WCAG 3.3.1 – Error Identification)
   ════════════════════════════════════════════════════════════════ */
const srInitFormValidation = () => {
  document.querySelectorAll('form[novalidate]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        // Foca o primeiro campo inválido (WCAG 2.4.3)
        const firstInvalid = form.querySelector(':invalid');
        firstInvalid?.focus();
      }
      form.classList.add('was-validated');
    });
  });
};

/* ════════════════════════════════════════════════════════════════
   2. Toggle visibilidade de senha (WCAG 1.3.5)
   ════════════════════════════════════════════════════════════════ */
const srInitPasswordToggle = () => {
  // Botão login (id="toggle-senha")
  const btnLogin = document.getElementById('toggle-senha');
  if (btnLogin) {
    btnLogin.addEventListener('click', () => {
      const inp = document.getElementById('senha');
      if (!inp) return;
      const isHidden = inp.type === 'password';
      inp.type = isHidden ? 'text' : 'password';
      btnLogin.setAttribute('aria-pressed', String(isHidden));
      btnLogin.querySelector('i')?.classList.toggle('bi-eye', !isHidden);
      btnLogin.querySelector('i')?.classList.toggle('bi-eye-slash', isHidden);
    });
  }

  // Botões genéricos .sr-toggle-pw com data-target
  document.querySelectorAll('.sr-toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const inp = document.getElementById(targetId);
      if (!inp) return;
      const isHidden = inp.type === 'password';
      inp.type = isHidden ? 'text' : 'password';
      btn.setAttribute('aria-pressed', String(isHidden));
      const icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye',      !isHidden);
        icon.classList.toggle('bi-eye-slash', isHidden);
      }
    });
  });
};

/* ════════════════════════════════════════════════════════════════
   3. Select dinâmico de equipamentos por laboratório
   GET /equipamento/por-laboratorio?id_laboratorio={id}
   Resposta JSON: [{"id":1,"nome":"Computador #01"}, ...]
   ════════════════════════════════════════════════════════════════ */
const srInitEquipSelect = () => {
  const labSelect = document.getElementById('oc-lab');
  const equipSelect = document.getElementById('oc-equip');
  if (!labSelect || !equipSelect) return;

  // Salva opção pré-selecionada (edição)
  const preSelected = equipSelect.value;

  labSelect.addEventListener('change', async () => {
    const idLab = labSelect.value;

    // Reseta equipamentos
    equipSelect.innerHTML = '<option value="">Carregando equipamentos...</option>';
    equipSelect.disabled = true;

    if (!idLab) {
      equipSelect.innerHTML = '<option value="">Selecione o laboratório primeiro...</option>';
      equipSelect.disabled = false;
      return;
    }

    try {
      const res = await fetch(`/equipamento/por-laboratorio?id_laboratorio=${encodeURIComponent(idLab)}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      /** @type {Array<{id: number, nome: string}>} */
      const equipamentos = await res.json();

      equipSelect.innerHTML = '<option value="">Nenhum (problema geral do laboratório)</option>';
      equipamentos.forEach(eq => {
        const opt = document.createElement('option');
        opt.value = eq.id;
        opt.textContent = eq.nome;
        if (String(eq.id) === preSelected) opt.selected = true;
        equipSelect.appendChild(opt);
      });

      if (equipamentos.length === 0) {
        equipSelect.innerHTML = '<option value="">Nenhum equipamento cadastrado</option>';
      }
    } catch (err) {
      console.error('[SR] Erro ao carregar equipamentos:', err);
      equipSelect.innerHTML = '<option value="">Erro ao carregar equipamentos</option>';
      srToast('Não foi possível carregar os equipamentos. Tente recarregar a página.', 'danger');
    } finally {
      equipSelect.disabled = false;
    }
  });

  // Dispara automaticamente se laboratório já estiver selecionado (edição)
  if (labSelect.value) labSelect.dispatchEvent(new Event('change'));
};

/* ════════════════════════════════════════════════════════════════
   4. Contador de caracteres em textarea
   Elemento: #oc-desc → contador #oc-desc-count
   ════════════════════════════════════════════════════════════════ */
const srInitCharCounter = () => {
  const desc    = document.getElementById('oc-desc');
  const counter = document.getElementById('oc-desc-count');
  if (!desc || !counter) return;

  const LIMIT = 1_000;
  const update = () => {
    const len = [...desc.value].length; // conta chars unicode corretamente
    counter.textContent = `${len.toLocaleString('pt-BR')} / ${LIMIT.toLocaleString('pt-BR')} caracteres`;
    counter.style.color = len > LIMIT * 0.9 ? '#dc3545' : '';
  };

  desc.addEventListener('input', update);
  update(); // estado inicial
};

/* ════════════════════════════════════════════════════════════════
   5. Clock em tempo real (preview do chamado – #oc-now)
   ════════════════════════════════════════════════════════════════ */
const srInitClock = () => {
  const el = document.getElementById('oc-now');
  if (!el) return;

  const fmt = n => String(n).padStart(2, '0');
  const tick = () => {
    const d = new Date();
    el.textContent = `${fmt(d.getDate())}/${fmt(d.getMonth() + 1)}/${d.getFullYear()} ${fmt(d.getHours())}:${fmt(d.getMinutes())}`;
    el.setAttribute('datetime', d.toISOString());
  };
  tick();
  setInterval(tick, 30_000); // atualiza a cada 30s
};

/* ════════════════════════════════════════════════════════════════
   6. Confirmação de ações destrutivas (data-confirm)
   ════════════════════════════════════════════════════════════════ */
const srInitConfirm = () => {
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    const msg = btn.dataset.confirm || 'Confirma esta ação?';
    if (!window.confirm(msg)) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
};

/* ════════════════════════════════════════════════════════════════
   7. Toast helper – srToast(mensagem, tipo)
   Tipo: 'success' | 'danger' | 'warning' | 'info'
   ════════════════════════════════════════════════════════════════ */
const srToast = (msg, type = 'info') => {
  const container = document.getElementById('sr-toast-container');
  if (!container) return;

  const iconMap = {
    success: 'bi-check-circle-fill text-success',
    danger:  'bi-exclamation-triangle-fill text-danger',
    warning: 'bi-exclamation-circle-fill text-warning',
    info:    'bi-info-circle-fill text-primary',
  };

  const toast = document.createElement('div');
  toast.className = 'toast align-items-center border-0 show';
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');
  toast.setAttribute('aria-atomic', 'true');
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center gap-2" style="font-size:13.5px">
        <i class="bi ${iconMap[type] ?? iconMap.info} flex-shrink-0" aria-hidden="true"></i>
        <span>${msg}</span>
      </div>
      <button type="button" class="btn-close me-2 m-auto"
              data-bs-dismiss="toast" aria-label="Fechar"></button>
    </div>`;

  container.appendChild(toast);

  const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
  bsToast.show();
  toast.addEventListener('hidden.bs.toast', () => toast.remove());
};

// Expõe globalmente para uso nos controllers PHP via onload
window.srToast = srToast;

/* ════════════════════════════════════════════════════════════════
   8. Auto-refresh do monitor de chamados
   Recarrega a página a cada 60s se estiver na view /monitor
   ════════════════════════════════════════════════════════════════ */
const srInitMonitorRefresh = () => {
  const btnRefresh = document.getElementById('btn-refresh-monitor');
  if (!btnRefresh) return;

  // Botão manual
  btnRefresh.addEventListener('click', () => {
    btnRefresh.disabled = true;
    btnRefresh.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Atualizando...';
    window.location.reload();
  });

  // Auto-refresh a cada 60 s
  let countdown = 60;
  const interval = setInterval(() => {
    countdown--;
    if (countdown <= 0) {
      clearInterval(interval);
      window.location.reload();
    }
  }, 1_000);
};

/* ════════════════════════════════════════════════════════════════
   9. Inicialização geral
   ════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  srInitFormValidation();
  srInitPasswordToggle();
  srInitEquipSelect();
  srInitCharCounter();
  srInitClock();
  srInitConfirm();
  srInitMonitorRefresh();

  // Exibe toasts a partir de data-toast injetado pelo Controller via PHP
  // Uso no PHP: <div id="sr-flash" data-msg="Salvo com sucesso" data-type="success"></div>
  const flash = document.getElementById('sr-flash');
  if (flash?.dataset.msg) {
    srToast(flash.dataset.msg, flash.dataset.type ?? 'info');
  }
});
