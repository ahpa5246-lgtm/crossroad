<!-- Full-screen challenge UI, no footer scroll -->
<style>
  body { padding-bottom: 0 !important; }
  .mobile-nav, .footer { display: none !important; }

  /* ── تحسين إضافي للـ modal ليكون في منتصف الشاشة بوضوح ── */
  .modal-bg {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(6px);
    padding: 20px;
    box-sizing: border-box;
  }

  .modal-bg.is-open {
    display: flex !important;
    animation: modal-fade .25s ease;
  }

  .modal-box {
    background: var(--card, #fff);
    border-radius: 16px;
    max-width: 480px;
    width: 100%;
    padding: 32px 28px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4);
    animation: modal-pop .3s cubic-bezier(.16,1,.3,1);
    margin: auto;
    text-align: center;
  }

  .modal-box h3 {
    margin-top: 0;
    color: var(--td, #391d0b);
    font-family:var(--fa);
  }

  .modal-box p {
    color: var(--text-muted, #926648);
    font-size: 15px;
    line-height: 1.6;
  }

  .modal-box .btn {
    min-width: 120px;
  }

  @keyframes modal-fade {
    from { opacity: 0; }
  }
  @keyframes modal-pop {
    from { opacity: 0; transform: translateY(30px) scale(.96); }
  }
</style>

<div class="challenge-shell" id="challengeShell">
  <!-- Top bar -->
  <div class="ch-topbar">
    <div class="ch-topbar-inner">
      <div class="ch-topbar-left">
        <a href="<?= BASE_URL ?>/challenges/view?id=<?= $ch['id'] ?>" class="ch-back-btn" onclick="return confirm('هل تريد الخروج؟ سيتم حفظ تقدمك.')">← خروج</a>
      </div>
      <div class="ch-title-wrap">
        <span class="ch-title"><?= e($ch['title']) ?></span>
      </div>
      <div class="ch-topbar-right">
        <?php if ($ch['time_limit']): ?>
        <div class="ch-timer" id="timerDisplay">
          <span class="timer-icon"><?= icon('clock') ?></span>
          <span id="timerText"><?= $ch['time_limit'] ?>:00</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <!-- Progress bar -->
    <div class="ch-top-progress">
      <div class="ch-top-progress-fill" id="topProgressFill" style="width:0%"></div>
    </div>
  </div>

  <!-- Content -->
  <div class="challenge-body">
    <div class="ch-sidebar">
      <div class="ch-nav-label">الأسئلة</div>
      <div class="ch-q-nav" id="qNav">
        <?php foreach ($questions as $i => $q): ?>
        <button class="ch-q-btn <?= isset($savedAnswers[$q['id']]) ? 'answered' : '' ?>"
                data-idx="<?= $i ?>" onclick="goTo(<?= $i ?>)"><?= $i + 1 ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ch-main">
      <?php foreach ($questions as $qi => $q):
        $opts = $optsByQ[$q['id']] ?? [];
        $savedOpts = $savedAnswers[$q['id']] ?? [];
        $correctCount = count(array_filter($opts, fn($o) => $o['is_correct']));
      ?>
      <div class="ch-question-card <?= $qi === (int)$attempt['current_q'] ? 'active' : '' ?>"
           id="qcard<?= $qi ?>" data-idx="<?= $qi ?>" data-qid="<?= $q['id'] ?>">
        <div class="ch-q-header">
          <span class="ch-q-num">السؤال <?= $qi + 1 ?> / <?= count($questions) ?></span>
          <span class="ch-q-points"><?= $q['points'] ?> نقطة<?= $correctCount > 1 ? ' · اختر '.$correctCount : '' ?></span>
        </div>
        <!-- Markdown-rendered question text -->
        <div class="ch-q-text" id="qt<?= $qi ?>"></div>
        <div class="ch-q-raw" style="display:none"><?= e($q['question']) ?></div>

        <div class="ch-options" id="opts<?= $qi ?>">
          <?php foreach ($opts as $oi => $opt): ?>
          <label class="ch-option <?= in_array($opt['id'], array_map('intval',(array)$savedOpts)) ? 'selected' : '' ?>">
            <input type="<?= $correctCount > 1 ? 'checkbox' : 'radio' ?>"
                   name="opt_<?= $q['id'] ?>"
                   value="<?= $opt['id'] ?>"
                   <?= in_array($opt['id'], array_map('intval',(array)$savedOpts)) ? 'checked' : '' ?>>
            <span class="ch-option-text"><?= e($opt['option_text']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Navigation -->
      <div class="ch-nav-btns">
        <button class="btn btn--outline" id="prevBtn" onclick="navigate(-1)" disabled>← السابق</button>
        <button class="btn btn--gold" id="nextBtn" onclick="navigate(1)">التالي →</button>
        <button class="btn btn--green" id="submitBtn" style="display:none" type="button">إرسال التحدي <?= icon('check') ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Submit confirmation modal -->
<div class="modal-bg" id="submitModal">
  <div class="modal-box">
    <div style="font-size:56px;margin-bottom:12px"><?= icon('target') ?></div>
    <h3>تأكيد إرسال التحدي</h3>
    <p id="submitSummary" style="margin:16px 0 24px;"></p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <button class="btn btn--gold btn--lg" id="confirmSubmit">إرسال الآن</button>
      <button class="btn btn--outline" type="button" id="cancelReviewBtn">مراجعة</button>
    </div>
  </div>
</div>

<script nonce="<?= csp_nonce() ?>">
const CHALLENGE_ID  = <?= $ch['id'] ?>;
const CSRF          = '<?= csrf_token() ?>';
const BASE_URL      = '<?= BASE_URL ?>';
const TOTAL_Q       = <?= count($questions) ?>;
const TIME_LIMIT    = <?= $ch['time_limit'] ? $ch['time_limit'] * 60 : 'null' ?>;
const INITIAL_Q     = <?= max(0, min((int)$attempt['current_q'], count($questions)-1)) ?>;

let currentIdx = INITIAL_Q;
let answers    = <?= json_encode((object)($savedAnswers ?: new stdClass())) ?>;
let saveTimer  = null;
let timeLeft   = TIME_LIMIT;
let timerInterval = null;

// ── Markdown renderer (lightweight) ──
function renderMarkdown(raw) {
  let s = (raw + '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
  return s
    .replace(/```([\s\S]*?)```/g, '<pre class="ch-code"><code>$1</code></pre>')
    .replace(/`([^`]+)`/g, '<code class="ch-inline-code">$1</code>')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    .replace(/\n/g, '<br>');
}

// Render all question texts
document.querySelectorAll('.ch-q-raw').forEach((el, i) => {
  document.getElementById('qt' + i).innerHTML = renderMarkdown(el.textContent);
});

// ── Navigation ─────────────────────────────────────────────────────────────
function goTo(idx) {
  if (idx < 0 || idx >= TOTAL_Q) return;
  collectCurrentAnswers();
  saveProgress(false);
  document.querySelectorAll('.ch-question-card').forEach(c => c.classList.remove('active'));
  document.getElementById('qcard' + idx)?.classList.add('active');
  currentIdx = idx;
  updateUI();
}

function navigate(dir) { goTo(currentIdx + dir); }

function updateUI() {
  document.getElementById('prevBtn').disabled = currentIdx === 0;
  document.getElementById('nextBtn').style.display   = currentIdx < TOTAL_Q - 1 ? '' : 'none';
  document.getElementById('submitBtn').style.display = currentIdx === TOTAL_Q - 1 ? '' : 'none';

  const answered = Object.values(answers).filter(v => Array.isArray(v) && v.length > 0).length;
  const pct = Math.round((answered / TOTAL_Q) * 100);
  document.getElementById('topProgressFill').style.width = pct + '%';

  document.querySelectorAll('.ch-q-btn').forEach(btn => {
    btn.classList.toggle('current', parseInt(btn.dataset.idx) === currentIdx);
  });
  document.getElementById('qcard' + currentIdx)?.scrollIntoView({behavior:'smooth', block:'nearest'});
}

// ── Answer collection ──────────────────────────────────────────────────────
function collectCurrentAnswers() {
  const card = document.getElementById('qcard' + currentIdx);
  if (!card) return;
  const qid     = card.dataset.qid;
  const inputs  = card.querySelectorAll('input[type=checkbox]:checked, input[type=radio]:checked');
  const vals    = [...inputs].map(i => parseInt(i.value));
  answers[qid]  = vals;

  document.querySelector(`.ch-q-btn[data-idx="${currentIdx}"]`)
    ?.classList.toggle('answered', vals.length > 0);

  card.querySelectorAll('.ch-option').forEach(opt => {
    const inp = opt.querySelector('input');
    opt.classList.toggle('selected', inp.checked);
  });
}

document.querySelectorAll('.ch-option input').forEach(inp => {
  inp.addEventListener('change', () => { collectCurrentAnswers(); autoSave(); });
});

// ── Save progress ──────────────────────────────────────────────────────────
function autoSave() {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => saveProgress(false), 1500);
}

async function saveProgress(force = true) {
  try {
    const fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('challenge_id', CHALLENGE_ID);
    fd.append('current_q', currentIdx);
    fd.append('answers', JSON.stringify(answers));
    await fetch(BASE_URL + '/challenges/save-progress', { method: 'POST', body: fd });
  } catch(e) { /* silent */ }
}

// ── Submit ─────────────────────────────────────────────────────────────────
function submitChallenge() {
  collectCurrentAnswers();
  const answered = Object.values(answers).filter(v => Array.isArray(v) && v.length > 0).length;
  const unanswered = TOTAL_Q - answered;
  document.getElementById('submitSummary').textContent =
    `أجبت على ${answered} من ${TOTAL_Q} سؤال. ` +
    (unanswered > 0 ? `${unanswered} سؤال بدون إجابة.` : 'أجبت على الكل!');

  // فتح الـ popup بإضافة class "is-open"
  document.getElementById('submitModal').classList.add('is-open');
}

function closeSubmitModal() {
  document.getElementById('submitModal').classList.remove('is-open');
}

document.getElementById('confirmSubmit').addEventListener('click', async () => {
  document.getElementById('confirmSubmit').disabled = true;
  document.getElementById('confirmSubmit').textContent = 'جار الإرسال…';
  await saveProgress(true);
  const fd = new FormData();
  fd.append('_csrf', CSRF);
  fd.append('challenge_id', CHALLENGE_ID);
  fd.append('answers', JSON.stringify(answers));
  try {
    const res  = await fetch(BASE_URL + '/challenges/submit', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.redirect) window.location.href = data.redirect;
    else alert(data.error || 'حدث خطأ. حاول مجددا.');
  } catch { alert('خطأ في الشبكة. حاول مجددا.'); }
});

document.getElementById('cancelReviewBtn').addEventListener('click', closeSubmitModal);

// ── Timer ──────────────────────────────────────────────────────────────────
if (TIME_LIMIT !== null) {
  function updateTimer() {
    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      alert('انتهى الوقت! سيتم إرسال إجاباتك تلقائيا.');
      document.getElementById('confirmSubmit').click();
      return;
    }
    const m = Math.floor(timeLeft / 60), s = timeLeft % 60;
    document.getElementById('timerText').textContent =
      String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    if (timeLeft <= 60) document.getElementById('timerDisplay').classList.add('timer--warn');
    timeLeft--;
  }
  timerInterval = setInterval(updateTimer, 1000);
  updateTimer();
}

// ── Save on page leave ─────────────────────────────────────────────────────
window.addEventListener('beforeunload', () => { collectCurrentAnswers(); saveProgress(true); });

// Init
updateUI();

document.getElementById('submitBtn')?.addEventListener('click', submitChallenge);

// إغلاق الـ modal بالنقر خارج المحتوى
document.getElementById('submitModal').addEventListener('click', function(e) {
  if (e.target === this) closeSubmitModal();
});
</script>