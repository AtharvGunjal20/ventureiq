/* ============================================================
   VentureIQ – Main Application JavaScript
   BASE_PATH-aware: all URLs use window.BASE_PATH prefix
   ============================================================ */
'use strict';

// BASE_PATH is set by header.php as: window.BASE_PATH = '/ventureiq/public' or ''
const BP = window.BASE_PATH || '';

const VIQ = {

  csrf: () => document.querySelector('meta[name="csrf"]')?.content || '',

  // All API calls go through this — auto-prefixes BASE_PATH
  async post(path, data) {
    const res = await fetch(BP + path, {
      method:  'POST',
      headers: {
        'Content-Type':  'application/json',
        'X-CSRF-Token':  VIQ.csrf(),
      },
      body: JSON.stringify(data),
    });
    return res.json();
  },

  // Navigate to a root-relative path
  goto(path) {
    window.location.href = BP + path;
  },

  // Toast notification
  toast(msg, type = 'info', duration = 3800) {
    const icons  = { success:'✓', error:'✕', info:'ℹ', warn:'⚠' };
    const colors = { success:'#00e5a0', error:'#ff6b6b', info:'#4f9fff', warn:'#ffb347' };
    const el = document.createElement('div');
    el.innerHTML = `<span style="color:${colors[type]}">${icons[type]}</span> ${msg}`;
    Object.assign(el.style, {
      position:'fixed', bottom:'24px', right:'24px', zIndex:'9999',
      background:'#1a1a2e', border:'1px solid rgba(255,255,255,.14)',
      color:'#f0f0f9', padding:'12px 18px', borderRadius:'12px',
      fontSize:'.88rem', boxShadow:'0 8px 32px rgba(0,0,0,.5)',
      display:'flex', alignItems:'center', gap:'10px',
      maxWidth:'340px', transition:'opacity .4s',
    });
    document.body.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, duration);
  },

  // ── Dropdown menus ────────────────────────────────────────
  initDropdown() {
    document.querySelectorAll('[data-dropdown-trigger]').forEach(trigger => {
      const targetId = trigger.dataset.dropdownTrigger;
      trigger.addEventListener('click', e => {
        e.stopPropagation();
        document.querySelectorAll('.nav-dropdown').forEach(d => {
          if (d.id !== targetId) d.classList.remove('open');
        });
        document.getElementById(targetId)?.classList.toggle('open');
      });
      // Keyboard support
      trigger.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') trigger.click();
      });
    });
    document.addEventListener('click', () =>
      document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.remove('open'))
    );
  },

  // ── Active nav link ───────────────────────────────────────
  initActiveNav() {
    const current = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
      const href = link.getAttribute('href') || '';
      // Strip BASE_PATH for comparison
      const path = href.replace(BP, '') || '/';
      if (path !== '/' && current.includes(path.replace('.php',''))) {
        link.classList.add('active');
      } else if (path === '/' && (current === BP + '/' || current === BP + '/index.php')) {
        link.classList.add('active');
      }
    });
  },

  // ── Password strength meter ───────────────────────────────
  initPasswordStrength() {
    const input = document.getElementById('password');
    const segs  = document.querySelectorAll('.strength-seg');
    if (!input || !segs.length) return;
    input.addEventListener('input', () => {
      const v = input.value;
      let score = 0;
      if (v.length >= 8)            score++;
      if (/[A-Z]/.test(v))         score++;
      if (/[0-9]/.test(v))         score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      const cls = ['','weak','fair','good','strong'];
      segs.forEach((s, i) => {
        s.className = 'strength-seg';
        if (i < score) s.classList.add(cls[score]);
      });
    });
  },

  // ── Toggle password visibility ────────────────────────────
  initPasswordToggle() {
    document.querySelectorAll('.input-toggle[data-toggle-pass]').forEach(btn => {
      btn.addEventListener('click', () => {
        const inp = document.getElementById(btn.dataset.togglePass);
        if (!inp) return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.textContent = inp.type === 'password' ? '👁' : '🙈';
      });
    });
  },
};

/* ── Charts helper (Results Page) ────────────────────────────── */
const Charts = {
  init(radarData, barData) {
    this.radar(radarData);
    this.bar(barData);
  },

  radar({ labels, userScores, modelScores, modelName }) {
    const ctx = document.getElementById('radarChart');
    if (!ctx) return;
    new Chart(ctx, {
      type: 'radar',
      data: {
        labels,
        datasets: [
          {
            label: 'Your Profile',
            data: userScores,
            borderColor: '#7c6dfa',
            backgroundColor: 'rgba(124,109,250,.15)',
            pointBackgroundColor: '#7c6dfa',
            borderWidth: 2, pointRadius: 4,
          },
          {
            label: modelName,
            data: modelScores,
            borderColor: '#00d4aa',
            backgroundColor: 'rgba(0,212,170,.1)',
            pointBackgroundColor: '#00d4aa',
            borderWidth: 2, pointRadius: 4,
          },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          r: {
            min: 0, max: 5,
            ticks: { display: false },
            grid: { color: 'rgba(255,255,255,.07)' },
            pointLabels: { color: '#9898b8', font: { size: 11 } },
          },
        },
        plugins: { legend: { display: false } },
      },
    });
  },

  bar({ labels, scores }) {
    const ctx = document.getElementById('barChart');
    if (!ctx) return;
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Confidence %',
          data: scores,
          backgroundColor: [
            'rgba(124,109,250,.75)',
            'rgba(79,159,255,.75)',
            'rgba(0,212,170,.75)',
          ],
          borderRadius: 8,
          borderSkipped: false,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true, max: 100,
            grid: { color: 'rgba(255,255,255,.05)' },
            ticks: { color: '#9898b8', font: { size: 11 }, callback: v => v + '%' },
          },
          x: {
            grid: { display: false },
            ticks: { color: '#9898b8', font: { size: 11 } },
          },
        },
        plugins: { legend: { display: false } },
      },
    });
  },
};

/* ── Admin helpers ────────────────────────────────────────────── */
function confirmDelete(path, name) {
  if (confirm(`Delete "${name}"? This cannot be undone.`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = path; // already a full url() call from PHP
    form.innerHTML = `<input type="hidden" name="_csrf" value="${VIQ.csrf()}">
                      <input type="hidden" name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
  }
}

async function toggleStatus(el, path) {
  const val = el.checked ? 1 : 0;
  const res = await VIQ.post(path.replace(BP, ''), { active: val });
  VIQ.toast(res.message || 'Updated', res.success ? 'success' : 'error');
  if (!res.success) el.checked = !el.checked;
}

function copyText(text) {
  navigator.clipboard.writeText(text)
    .then(() => VIQ.toast('Copied!', 'success', 2000));
}

/* ── DOM Ready ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  VIQ.initDropdown();
  VIQ.initActiveNav();
  VIQ.initPasswordStrength();
  VIQ.initPasswordToggle();

  // Auto-dismiss flash alerts
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // Animate score bars on results page
  document.querySelectorAll('.score-fill[data-width]').forEach(el => {
    setTimeout(() => { el.style.width = el.dataset.width + '%'; }, 200);
  });
});
