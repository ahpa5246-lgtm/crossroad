/* admin.js - Admin Panel */
(function () {
  'use strict';

  var activeDialog = null;

  function setBusy(btn, isBusy, busyText) {
    if (!btn) return;
    if (isBusy) {
      btn.dataset.originalText = btn.textContent;
      btn.disabled = true;
      btn.classList.add('loading');
      if (busyText) btn.textContent = busyText;
    } else {
      btn.disabled = false;
      btn.classList.remove('loading');
      if (btn.dataset.originalText) btn.textContent = btn.dataset.originalText;
      delete btn.dataset.originalText;
    }
  }

  function closeDialog(value) {
    if (!activeDialog) return;
    var dialog = activeDialog;
    activeDialog = null;
    dialog.node.classList.remove('is-open');
    document.body.classList.remove('modal-open');
    setTimeout(function () {
      if (dialog.node && dialog.node.parentNode) dialog.node.remove();
    }, 180);
    dialog.resolve(value);
  }

  function openDialog(opts) {
    opts = opts || {};
    if (activeDialog) closeDialog(false);
    return new Promise(function (resolve) {
      var node = document.createElement('div');
      node.className = 'modal-bg admin-dialog is-open';
      node.setAttribute('role', 'dialog');
      node.setAttribute('aria-modal', 'true');
      node.innerHTML =
        '<div class="modal-box admin-dialog__box">' +
          '<div class="modal-head">' +
            '<h3>' + escapeHtml(opts.title || 'تأكيد') + '</h3>' +
            '<button type="button" class="modal-close" data-dialog-result="0" aria-label="إغلاق">×</button>' +
          '</div>' +
          '<div class="modal-body">' +
            '<p class="admin-dialog__message">' + escapeHtml(opts.message || 'هل تريد المتابعة؟') + '</p>' +
            '<div class="form-actions admin-dialog__actions">' +
              '<button type="button" class="btn ' + (opts.danger ? 'btn--danger' : 'btn--gold') + '" data-dialog-result="1">' + escapeHtml(opts.confirmText || 'تأكيد') + '</button>' +
              '<button type="button" class="btn btn--outline" data-dialog-result="0">' + escapeHtml(opts.cancelText || 'إلغاء') + '</button>' +
            '</div>' +
          '</div>' +
        '</div>';
      activeDialog = { node: node, resolve: resolve };
      document.body.appendChild(node);
      document.body.classList.add('modal-open');
      requestAnimationFrame(function () {
        var focusTarget = node.querySelector('[data-dialog-result="1"]');
        if (focusTarget) focusTarget.focus();
      });
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function findSubmitter(form) {
    return form.querySelector('button[type="submit"], .btn[type="submit"], button:not([type])');
  }

  window.adminConfirm = function (message, opts) {
    opts = opts || {};
    opts.message = message || opts.message;
    return openDialog(opts);
  };

  window.adminAlert = function (message, opts) {
    opts = opts || {};
    opts.message = message || opts.message;
    opts.confirmText = opts.confirmText || 'حسنا';
    opts.cancelText = opts.cancelText || '';
    return new Promise(function (resolve) {
      openDialog(opts).then(function () { resolve(true); });
      requestAnimationFrame(function () {
        var cancel = document.querySelector('.admin-dialog [data-dialog-result="0"]:not(.modal-close)');
        if (cancel) cancel.style.display = 'none';
      });
    });
  };

  window.adminCloseModal = function (modal) {
    var el = typeof modal === 'string' ? document.querySelector(modal) : modal;
    if (!el) return;
    el.classList.remove('is-open');
    el.style.display = 'none';
    document.body.classList.remove('modal-open');
  };

  window.adminOpenModal = function (modal) {
    var el = typeof modal === 'string' ? document.querySelector(modal) : modal;
    if (!el) return;
    el.style.display = 'flex';
    el.classList.add('is-open');
    document.body.classList.add('modal-open');
  };

  // Auto-dismiss flash messages after 4s
  setTimeout(function () {
    document.querySelectorAll('.flash-inline, .flash-top').forEach(function (f) {
      f.style.transition = 'opacity .3s';
      f.style.opacity = '0';
      setTimeout(function () { f.remove(); }, 320);
    });
  }, 4000);

  // Image preview on file input
  document.querySelectorAll('input[type=file][accept*=image]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var p = this.parentElement.querySelector('.file-preview');
      if (!p) { p = document.createElement('div'); p.className = 'file-preview'; this.after(p); }
      p.innerHTML = '';
      if (this.files[0]) {
        var img = new Image();
        img.src = URL.createObjectURL(this.files[0]);
        img.style.cssText = 'max-width:200px;border-radius:8px;margin-top:8px;display:block';
        p.appendChild(img);
      }
    });
  });

  // Flash close buttons
  document.querySelectorAll('.flash-close').forEach(function (b) {
    b.addEventListener('click', function () {
      var f = b.closest('.flash-inline, .flash');
      if (f) { f.style.opacity = '0'; setTimeout(function () { f.remove(); }, 300); }
    });
  });

  document.querySelectorAll('img[data-hide-on-error]').forEach(function (img) {
    img.addEventListener('error', function () { img.style.display = 'none'; });
  });

  document.addEventListener('click', function (e) {
    var dialogBtn = e.target.closest('[data-dialog-result]');
    if (dialogBtn && activeDialog) {
      e.preventDefault();
      closeDialog(dialogBtn.dataset.dialogResult === '1');
      return;
    }

    if (activeDialog && e.target === activeDialog.node) {
      closeDialog(false);
      return;
    }

    var themeBtn = e.target.closest('[data-theme-toggle]');
    if (themeBtn) {
      e.preventDefault();
      if (typeof window.applyTheme === 'function') {
        window.applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
      }
      return;
    }

    var closer = e.target.closest('[data-close-parent]');
    if (closer) {
      e.preventDefault();
      var selector = closer.dataset.closeParent || '.cert-issued-toast, .flash-inline, .flash, .alert';
      var target = closer.closest(selector);
      if (target) target.remove();
      return;
    }

    var modalCloser = e.target.closest('[data-modal-close]');
    if (modalCloser) {
      e.preventDefault();
      var modalSelector = modalCloser.dataset.modalClose;
      var modal = modalSelector ? document.querySelector(modalSelector) : modalCloser.closest('.modal-bg');
      window.adminCloseModal(modal);
      return;
    }

    if (e.target.classList && e.target.classList.contains('modal-bg')) {
      window.adminCloseModal(e.target);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (activeDialog) {
      closeDialog(false);
      return;
    }
    var openModal = document.querySelector('.modal-bg.is-open, .modal-bg[style*="display: flex"], .modal-bg[style*="display:flex"]');
    if (openModal) window.adminCloseModal(openModal);
  });

  document.addEventListener('change', function (e) {
    var auto = e.target.closest('[data-auto-submit], .auto-submit');
    if (!auto) return;
    var form = auto.closest('form');
    if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.matches('form')) return;

    if (form.dataset.confirm && form.dataset.confirmed !== '1') {
      e.preventDefault();
      window.adminConfirm(form.dataset.confirm, {
        title: form.dataset.confirmTitle || 'تأكيد العملية',
        confirmText: form.dataset.confirmOk || 'تأكيد',
        cancelText: form.dataset.confirmCancel || 'إلغاء',
        danger: form.dataset.confirmDanger === '1'
      }).then(function (ok) {
        if (!ok) return;
        form.dataset.confirmed = '1';
        if (form.requestSubmit) form.requestSubmit(e.submitter || findSubmitter(form));
        else form.submit();
        setTimeout(function () { delete form.dataset.confirmed; }, 0);
      });
      return;
    }

    if (!form.dataset.ajaxToast) {
      var submitter = e.submitter || findSubmitter(form);
      setBusy(submitter, true);
      form.classList.add('submitting');
    }
  });

})();
