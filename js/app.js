// js/app.js

// ── Modal helpers ──
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('open');
    }
  });
});

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});

// ── Class hint for section select ──
const classMap = {
  A: 'Computer & Optional Maths',
  B: 'Computer & Optional Maths',
  C: 'Computer & Optional Maths',
  D: 'Economics & Accounts',
  E: 'Economics & Accounts',
};
const colorMap = {
  Computer: '#4f8ef7',
  Economics: '#7c6af7',
};

function updateClassHint(selectId, hintId) {
  const sel   = document.getElementById(selectId);
  const hint  = document.getElementById(hintId);
  if (!sel || !hint) return;

  const section = sel.value;
  if (!section) {
    hint.textContent = 'Select a section to see class assignment';
    hint.style.color = 'var(--muted)';
    return;
  }

  const label = classMap[section] || '—';
  const type  = ['A','B','C'].includes(section) ? 'Computer' : 'Economics';
  hint.textContent = label;
  hint.style.color = colorMap[type];
}

// ── Auto-dismiss alerts after 4s ──
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity    = '0';
    setTimeout(() => el.remove(), 400);
  }, 4000);
});

// ── Highlight active nav link by current path ──
const current = window.location.pathname.split('/').pop();
document.querySelectorAll('.nav-link').forEach(a => {
  if (a.getAttribute('href') === current) a.classList.add('active');
});
