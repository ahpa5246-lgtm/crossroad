/* المجلة: like toggle + delete confirm (no inline handlers: CSP-safe) */
(function () {
  'use strict';
  var b = document.getElementById('likeBtn');
  if (b) b.addEventListener('click', function () {
    if (b.disabled) return; b.disabled = true;
    var fd = new FormData(); fd.append('id', b.dataset.id); fd.append('_csrf', b.dataset.csrf);
    fetch(b.dataset.url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.ok) { b.classList.toggle('is-on', !!d.liked); document.getElementById('likeCount').textContent = d.count; } })
      .catch(function () {})
      .then(function () { b.disabled = false; });
  });
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('form[data-confirm]');
    if (f && !window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  });
})();
