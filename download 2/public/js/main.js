/* main.js */
(function(){'use strict';

// ── Theme: the site is dark-only (no switcher). Applied before first paint in <head>. ──
(function () {
  var root = document.documentElement;
  root.setAttribute('data-theme', 'dark');
  try { localStorage.removeItem('mftaraq_theme'); } catch (_) {}
  document.querySelectorAll('.logo-light').forEach(function (el) { el.style.setProperty('display', 'none', 'important'); });
  document.querySelectorAll('.logo-dark').forEach(function (el) { el.style.setProperty('display', 'block', 'important'); });
})();

// ── Navbar scroll ──────────────────────────────────────────────────────────
const navbar = document.getElementById('navbar');
if (navbar) {
  const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 16);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

// ── Reveal on scroll ───────────────────────────────────────────────────────
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('visible'); revealObs.unobserve(e.target); }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -28px 0px' });

document.querySelectorAll('.reveal').forEach(el => {
  revealObs.observe(el);
  // Immediately reveal elements already in viewport
  try {
    const r = el.getBoundingClientRect();
    if (r.top < window.innerHeight && r.bottom > 0) el.classList.add('visible');
  } catch(_) {}
});

// ── Dismissible banners (remembered for the session) ──────────────────────
(function () {
  var b = document.getElementById('tgBanner');
  if (!b) return;
  var seen = false; try { seen = sessionStorage.getItem('dismiss_tg') === '1'; } catch (_) {}
  if (!seen) b.hidden = false;
  document.addEventListener('click', function (e) {
    var x = e.target.closest('[data-dismiss-banner]');
    if (!x) return;
    try { sessionStorage.setItem('dismiss_' + x.dataset.dismissBanner, '1'); } catch (_) {}
    var bn = x.closest('.tg-banner'); if (bn) bn.hidden = true;
  });
})();

// ── Flash dismiss ──────────────────────────────────────────────────────────
document.querySelectorAll('.flash, .flash-inline').forEach(f => {
  const close = f.querySelector('.flash-close');
  const hide = () => { f.style.opacity = '0'; setTimeout(() => f.remove(), 300); };
  if (close) close.addEventListener('click', hide);
  setTimeout(hide, 5000);
});

// ── Smooth scroll ─────────────────────────────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', function(e) {
    const t = document.querySelector(this.getAttribute('href'));
    if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth' }); }
  });
});

// ── Counter animation ──────────────────────────────────────────────────────
const counters = document.querySelectorAll('[data-target]');
if (counters.length) {
  const countObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { animateCount(e.target); countObs.unobserve(e.target); } });
  }, { threshold: 0.5 });
  counters.forEach(el => countObs.observe(el));
}
function animateCount(el) {
  const target = parseInt(el.dataset.target) || 0;
  if (!target) return;
  let cur = 0;
  const step = Math.max(1, Math.ceil(target / 60));
  const t = setInterval(() => {
    cur = Math.min(cur + step, target);
    el.textContent = cur.toLocaleString('ar');
    if (cur >= target) clearInterval(t);
  }, 16);
}

// ── Active mobile nav ──────────────────────────────────────────────────────
const curPath = location.pathname;
document.querySelectorAll('.mnav-item, .admin-mnav-item').forEach(item => {
  const href = item.getAttribute('href');
  if (!href) return;
  const isActive = curPath === href ||
    (href !== '/' && href !== '/admin' && curPath.startsWith(href)) ||
    (href === '/admin' && curPath === '/admin');
  if (isActive) item.classList.add('active');
});

})();

// ── Toast ─────────────────────────────────────────────
window.toast = function(msg, duration) {
  var wrap = document.querySelector('.toast-wrap');
  if (!wrap) { wrap = document.createElement('div'); wrap.className = 'toast-wrap'; document.body.appendChild(wrap); }
  var el = document.createElement('div');
  el.className = 'toast';
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(function() { el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(function(){el.remove();},320); }, duration||2800);
};

// Flash close buttons
document.querySelectorAll('.flash-close').forEach(function(b) {
  b.addEventListener('click', function() {
    var f = b.closest('.flash, .flash-inline');
    if (f) { f.style.transition='opacity .3s'; f.style.opacity='0'; setTimeout(function(){f.remove();},320); }
  });
});

// ── Content Protection ────────────────────────────────────────
(function() {
  // Only activate on .protect-content and .cert-protected elements
  function isProtected(el) {
    return el && (el.closest('.protect-content') || el.closest('.cert-protected'));
  }

  // Block right-click on protected areas
  document.addEventListener('contextmenu', function(e) {
    if (isProtected(e.target)) e.preventDefault();
  }, false);

  // Block drag of protected images
  document.addEventListener('dragstart', function(e) {
    if (isProtected(e.target)) e.preventDefault();
  }, false);

  // Block copy on protected areas (still allow form inputs)
  document.addEventListener('copy', function(e) {
    var sel = window.getSelection();
    if (!sel || !sel.rangeCount) return;
    var range = sel.getRangeAt(0);
    if (range.commonAncestorContainer && isProtected(range.commonAncestorContainer)) {
      e.preventDefault();
    }
  }, false);

  // Intercept common DevTools shortcuts on cert pages only (not globally — preserves UX)
  document.addEventListener('keydown', function(e) {
    if (!document.querySelector('.cert-protected')) return;
    // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
    if (e.key === 'F12' ||
        (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key)) ||
        (e.ctrlKey && e.key === 'u')) {
      e.preventDefault();
    }
  }, false);
})();

// ── Notification polling (every 60s when logged in) ──────────
(function() {
  var bellBadge = document.querySelector('.bell-badge');
  var drawerBadge = document.querySelector('.drawer-badge');
  if (!bellBadge && !drawerBadge) return; // Not logged in

  function updateBadge(count) {
    [bellBadge, drawerBadge].forEach(function(el) {
      if (!el) return;
      if (count > 0) {
        el.textContent = count > 99 ? '99+' : count;
        el.style.display = 'flex';
      } else {
        el.style.display = 'none';
      }
    });
  }

  function pollNotifications() {
    fetch(window.__BASE_URL__ + '/account/notifications/count', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.ok ? r.json() : null; })
      .then(function(d) { if (d && typeof d.unread === 'number') updateBadge(d.unread); })
      .catch(function() {}); // Silent fail
  }

  // Poll every 60 seconds
  setInterval(pollNotifications, 60000);
})();

// ── CSRF Token Auto-Refresh ───────────────────────────────────────────────
// After a session_regenerate_id() on login/2FA, the CSRF token in the
// current page DOM is stale. We fetch a fresh one on DOMContentLoaded so
// AJAX forms always send the correct token without a page reload.
(function() {
  // Only refresh if page has AJAX forms (apply form, etc.)
  if (!document.querySelector('input[name="_csrf"]')) return;

  function updateCsrfFields(token) {
    document.querySelectorAll('input[name="_csrf"]').forEach(function(el) {
      el.value = token;
    });
    // Also expose globally for dynamic forms
    window.__CSRF_TOKEN__ = token;
  }

  // Refresh immediately on load (handles post-login stale token)
  fetch((window.__BASE_URL__ || '') + '/auth/csrf-refresh', {
    method: 'GET',
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(function(r) { return r.ok ? r.json() : null; })
  .then(function(d) { if (d && d.token) updateCsrfFields(d.token); })
  .catch(function() {}); // Silent fail — old token still works if session didn't rotate
})();

// ── Auto-submit forms on change ──────────────────────────────────────────
// Some forms (avatar upload, news category filter) submit themselves as soon
// as the user changes a value/file. The original handler lived in admin.js,
// which is NOT loaded on the public account/home pages — that's why the
// avatar upload silently did nothing. Adding it here makes the behavior
// available everywhere; admin.js still binds the same handler, but the
// duplicate is harmless (form.requestSubmit() is idempotent).
(function() {
  if (window.__autoSubmitBound) return; window.__autoSubmitBound = true;

  function maybeAutoSubmit(el) {
    var auto = el && el.closest && el.closest('[data-auto-submit], .auto-submit');
    if (!auto) return;
    var form = auto.closest('form');
    if (!form || form.__autoSubmitting) return;
    form.__autoSubmitting = true;
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
    setTimeout(function(){ form.__autoSubmitting = false; }, 1500);
  }

  // 'change' covers <select>, <input type=file>, etc.
  document.addEventListener('change', function(e){ maybeAutoSubmit(e.target); });
  // 'input' covers <input type=text|email|...> for instant-search style fields
  document.addEventListener('input', function(e){
    var el = e.target;
    if (el && el.matches && el.matches('[data-auto-submit-on-input]')) maybeAutoSubmit(el);
  });
})();

/* password eye (works under CSP: no inline handlers) */
document.addEventListener('click', function (e) {
  var b = e.target.closest('.input-eye');
  if (!b) return;
  e.preventDefault();
  var w = b.closest('.input-wrap'), i = w && w.querySelector('input');
  if (!i) return;
  var show = i.type === 'password';
  i.type = show ? 'text' : 'password';
  b.classList.toggle('is-on', show);
  b.setAttribute('aria-pressed', show ? 'true' : 'false');
  b.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
});
