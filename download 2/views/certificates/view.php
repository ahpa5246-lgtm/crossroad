<?php
$appUrl  = $appUrl ?? '';
$dlUrl   = $appUrl . '/certificates/download-pdf?token=' . rawurlencode($token);
$pdfUrl  = $appUrl . '/certificates/pdf-inline?token='  . rawurlencode($token);
$pageUrl = $appUrl . '/certificates/view?token='        . rawurlencode($token);
$verUrl  = $appUrl . '/certificates/verify?token='      . rawurlencode($token);
?>
<style>
.cv-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 16px 56px;
  min-height: 80vh;
  background: linear-gradient(160deg,#fff9f0 0%,#fff0dc 100%);
}
[data-theme="dark"] .cv-wrap { background: linear-gradient(160deg,#24180e 0%,#312113 100%); }

.cv-header {
  width: 100%; max-width: 880px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 14px; flex-wrap: wrap;
}
.cv-title { font-size: 17px; font-weight: 700; color: var(--text,#342314); }
.cv-title span { color: var(--navy); font-weight: 500; font-size: 14px; margin-right: 8px; }
.cv-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.cv-canvas-wrap {
  width: 100%; max-width: 880px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 16px 64px rgba(110,74,42,.18), 0 2px 8px rgba(0,0,0,.08);
  overflow: hidden;
  position: relative;
  min-height: 300px;
}
[data-theme="dark"] .cv-canvas-wrap { background: #342314; }

#cv-loading {
  position: absolute; inset: 0;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 14px; color: var(--navy); font-size: 15px;
}
.cv-spinner {
  width: 40px; height: 40px;
  border: 3px solid rgba(110,74,42,.15);
  border-top-color: var(--navy-dark);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

#cv-error {
  display: none;
  padding: 48px 24px; text-align: center; color: #8f7258;
}
#cv-error p { margin-bottom: 20px; font-size: 15px; }

/* Pages stack */
#cv-pages { display: flex; flex-direction: column; align-items: center; }
#cv-pages canvas {
  display: block;
  width: 100% !important;
  height: auto !important;
  max-width: 880px;
}
#cv-pages canvas + canvas { border-top: 1px solid rgba(110,74,42,.1); }

/* Pagination controls */
#cv-controls {
  display: none;
  align-items: center; justify-content: center;
  gap: 12px; padding: 14px 24px;
  border-top: 1px solid rgba(110,74,42,.1);
  background: #fbf8f6;
}
[data-theme="dark"] #cv-controls { background: #372515; }
#cv-controls button {
  background: none; border: 1.5px solid var(--navy-dark); border-radius: 8px;
  color: var(--navy-dark); padding: 6px 14px; cursor: pointer; font-size: 14px; font-weight: 600;
}
#cv-controls button:disabled { opacity: .35; cursor: default; }
#cv-page-info { font-size: 13px; color: var(--navy); min-width: 60px; text-align: center; }

.cv-footer {
  width: 100%; max-width: 880px; margin-top: 12px;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px;
}
.cv-token { font-family: monospace; font-size: 11px; color: #bfa289; }
.cv-verify { font-size: 13px; color: var(--navy); text-decoration: none; }
.cv-verify:hover { text-decoration: underline; }
</style>

<!-- PDF.js from cdnjs -->
<script nonce="<?= csp_nonce() ?>" src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script nonce="<?= csp_nonce() ?>">
  pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>

<div class="cv-wrap">

  <div class="cv-header">
    <div class="cv-title">
      <?= icon('award') ?> شهادة <span><?= e($cert['display_name']) ?></span>
    </div>
    <div class="cv-actions">
      <a href="<?= htmlspecialchars($dlUrl) ?>" class="btn btn--gold"><?= icon('download') ?> تحميل PDF</a>
      <button class="btn btn--outline" id="copyLinkBtn" type="button"><?= icon('link') ?> نسخ الرابط</button>
    </div>
  </div>

  <div class="cv-canvas-wrap">
    <div id="cv-loading">
      <div class="cv-spinner"></div>
      <span>جاري تحميل الشهادة…</span>
    </div>
    <div id="cv-error">
      <p>تعذر عرض الشهادة.</p>
      <a href="<?= htmlspecialchars($dlUrl) ?>" class="btn btn--gold"><?= icon('download') ?> تحميل الشهادة</a>
    </div>
    <div id="cv-pages"></div>
    <div id="cv-controls">
      <button id="cv-prev" type="button">→ السابق</button>
      <span id="cv-page-info"></span>
      <button id="cv-next" type="button">التالي ←</button>
    </div>
  </div>

  <div class="cv-footer">
    <span class="cv-token"><?= e(substr($cert['token'], 0, 16)) ?>…</span>
    <a href="<?= htmlspecialchars($verUrl) ?>" class="cv-verify" target="_blank" rel="noopener">
      <?= icon('search') ?> التحقق من الصحة ←
    </a>
  </div>

</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
  var PDF_URL  = <?= json_encode($pdfUrl) ?>;
  var PAGE_URL = <?= json_encode($pageUrl) ?>;

  var loading  = document.getElementById('cv-loading');
  var error    = document.getElementById('cv-error');
  var pages    = document.getElementById('cv-pages');
  var controls = document.getElementById('cv-controls');
  var prevBtn  = document.getElementById('cv-prev');
  var nextBtn  = document.getElementById('cv-next');
  var pageInfo = document.getElementById('cv-page-info');

  var pdfDoc   = null;
  var curPage  = 1;
  var rendering = false;

  function showError() {
    loading.style.display = 'none';
    error.style.display   = 'block';
  }

  function renderPage(num) {
    if (rendering) return;
    rendering = true;
    pdfDoc.getPage(num).then(function (page) {
      var wrap    = pages;
      var vpWidth = wrap.clientWidth || 800;
      var vp      = page.getViewport({ scale: 1 });
      var scale   = vpWidth / vp.width;
      var viewport = page.getViewport({ scale: scale });

      // Reuse or create canvas for this page slot
      var canvas = wrap.children[num - 1];
      if (!canvas) {
        canvas = document.createElement('canvas');
        wrap.appendChild(canvas);
      }
      canvas.width  = viewport.width;
      canvas.height = viewport.height;

      page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport })
          .promise.then(function () {
            rendering = false;
            updateControls();
          });
    }).catch(function () { showError(); });
  }

  function updateControls() {
    if (!pdfDoc) return;
    pageInfo.textContent = curPage + ' / ' + pdfDoc.numPages;
    prevBtn.disabled = curPage <= 1;
    nextBtn.disabled = curPage >= pdfDoc.numPages;
  }

  function changePage(delta) {
    var next = curPage + delta;
    if (!pdfDoc || next < 1 || next > pdfDoc.numPages) return;
    curPage = next;
    // Scroll to that page canvas
    var canvas = pages.children[curPage - 1];
    if (canvas) canvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
    updateControls();
  }
  window.changePage = changePage;

  // Load & render ALL pages
  pdfjsLib.getDocument(PDF_URL).promise.then(function (pdf) {
    pdfDoc = pdf;
    loading.style.display = 'none';

    var renderNext = function (pageNum) {
      if (pageNum > pdf.numPages) {
        // Done — show controls only if multi-page
        if (pdf.numPages > 1) {
          controls.style.display = 'flex';
          updateControls();
        }
        return;
      }
      pdf.getPage(pageNum).then(function (page) {
        var vpWidth  = pages.clientWidth || 800;
        var vp       = page.getViewport({ scale: 1 });
        var scale    = vpWidth / vp.width;
        var viewport = page.getViewport({ scale: scale });

        var canvas   = document.createElement('canvas');
        canvas.width  = viewport.width;
        canvas.height = viewport.height;
        pages.appendChild(canvas);

        page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport })
            .promise.then(function () { renderNext(pageNum + 1); });
      });
    };

    renderNext(1);

  }).catch(function () { showError(); });

  // Copy link
  window.copyLink = function () {
    var url = PAGE_URL;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(function () {
        var btn = document.querySelector('[id="copyLinkBtn" type="button"]');
        var orig = btn.textContent;
        btn.textContent = 'تم النسخ!';
        setTimeout(function () { btn.textContent = orig; }, 2000);
      });
    } else {
      var ta = document.createElement('textarea');
      ta.value = url; document.body.appendChild(ta);
      ta.select(); document.execCommand('copy');
      document.body.removeChild(ta);
    }
  };
})();

document.getElementById('copyLinkBtn')?.addEventListener('click', copyLink);

document.getElementById('cv-prev')?.addEventListener('click', function() { changePage(-1); });
document.getElementById('cv-next')?.addEventListener('click', function() { changePage(1); });
</script>
