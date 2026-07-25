/**
 * PG A1 — Modal Utility
 * Replaces all prompt()/alert() with styled popup modals.
 */

// Inject modal container into the page
(function initModal() {
  function inject() {
    if (document.getElementById('modal-overlay')) return; // already exists
    const container = document.createElement('div');
    container.id = 'modal-overlay';
    container.className = 'fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4';
    container.innerHTML = '<div id="modal-box" class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()"></div>';
    container.addEventListener('click', (e) => { if (e.target === container) closeModal(); });
    document.body.appendChild(container);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inject);
  } else {
    inject();
  }
})();

function openModal(html) {
  const overlay = document.getElementById('modal-overlay');
  const box = document.getElementById('modal-box');
  box.innerHTML = html;
  overlay.classList.remove('hidden');
  overlay.classList.add('flex');
  // Focus first input
  setTimeout(() => { const inp = box.querySelector('input,select,textarea'); if (inp) inp.focus(); }, 100);
}

function closeModal() {
  const overlay = document.getElementById('modal-overlay');
  overlay.classList.add('hidden');
  overlay.classList.remove('flex');
}

/**
 * Show a success/error toast notification
 */
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `fixed top-20 right-4 z-[10000] px-5 py-3 rounded-xl shadow-lg text-sm font-semibold flex items-center gap-2 transition-all animate-[slideIn_0.3s_ease] ${
    type === 'success' ? 'bg-[#d8f3dc] text-[#2d6a4f] border border-[#2d6a4f]/20' :
    type === 'error' ? 'bg-[#fde8e8] text-[#c1121f] border border-[#c1121f]/20' :
    'bg-[#f2ebe0] text-[#3d3028] border border-[#e8d8c4]'
  }`;
  toast.innerHTML = `<span class="material-symbols-outlined text-lg">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</span>${message}`;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100px)'; setTimeout(() => toast.remove(), 300); }, 3000);
}

/**
 * Show a confirmation modal
 */
function showConfirm(title, message, onConfirm) {
  openModal(`
    <div class="p-6">
      <h3 class="font-display text-lg font-bold text-[#1a1410] mb-2">${title}</h3>
      <p class="text-sm text-[#7a6655] mb-5">${message}</p>
      <div class="flex gap-3 justify-end">
        <button onclick="closeModal()" class="px-4 py-2 rounded-lg text-sm font-semibold text-[#7a6655] border border-[#e8d8c4] hover:bg-[#f2ebe0] transition-colors">Cancel</button>
        <button id="modal-confirm-btn" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-[#c8813a] hover:bg-[#e09a50] transition-colors">Confirm</button>
      </div>
    </div>
  `);
  document.getElementById('modal-confirm-btn').onclick = () => { closeModal(); onConfirm(); };
}

/**
 * Show a result/copy modal (replaces prompt for showing links)
 */
function showResult(title, value, description = '') {
  openModal(`
    <div class="p-6">
      <h3 class="font-display text-lg font-bold text-[#1a1410] mb-2">${title}</h3>
      ${description ? `<p class="text-sm text-[#7a6655] mb-3">${description}</p>` : ''}
      <div class="flex items-center gap-2 bg-[#f2ebe0] rounded-xl p-3 mb-4">
        <input id="modal-result-value" type="text" value="${value}" readonly class="flex-1 bg-transparent text-sm text-[#1a1410] font-mono border-none outline-none" />
        <button onclick="copyToClipboard()" class="p-2 rounded-lg bg-[#c8813a] text-white hover:bg-[#e09a50] transition-colors" title="Copy">
          <span class="material-symbols-outlined text-sm">content_copy</span>
        </button>
      </div>
      <button onclick="closeModal()" class="w-full py-2.5 rounded-lg text-sm font-bold text-white bg-[#c8813a] hover:bg-[#e09a50] transition-colors">Done</button>
    </div>
  `);
}

function copyToClipboard() {
  const input = document.getElementById('modal-result-value');
  input.select();
  navigator.clipboard.writeText(input.value);
  showToast('Copied to clipboard!');
}

/**
 * Modal form builder helper
 */
function buildFormField(id, label, type = 'text', placeholder = '', defaultValue = '', options = null) {
  if (type === 'select' && options) {
    return `
      <div>
        <label class="text-[10px] font-semibold uppercase tracking-widest text-[#7a6655] block mb-1">${label}</label>
        <select id="${id}" class="w-full bg-[#f2ebe0] text-sm px-3 py-2.5 rounded-xl border border-[#e8d8c4] focus:ring-2 focus:ring-[#c8813a] focus:border-[#c8813a] outline-none">
          ${options.map(o => `<option value="${o.value}" ${o.value === defaultValue ? 'selected' : ''}>${o.label}</option>`).join('')}
        </select>
      </div>`;
  }
  return `
    <div>
      <label class="text-[10px] font-semibold uppercase tracking-widest text-[#7a6655] block mb-1">${label}</label>
      <input id="${id}" type="${type}" placeholder="${placeholder}" value="${defaultValue}"
        ${type === 'tel' ? 'maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,\'\')" pattern="[0-9]{10}"' : ''}
        class="w-full bg-[#f2ebe0] text-sm px-3 py-2.5 rounded-xl border border-[#e8d8c4] focus:ring-2 focus:ring-[#c8813a] focus:border-[#c8813a] outline-none" />
    </div>`;
}
