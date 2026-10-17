<?php
// Look up whether a certificate was already issued for this attempt
$_certStmt = Database::getInstance()->prepare(
    "SELECT token FROM certificates WHERE user_id=? AND challenge_id=? AND is_valid=1 LIMIT 1"
);
$_certStmt->execute([(int)(auth()['id'] ?? 0), (int)$ch['id']]);
$_certToken = $_certStmt->fetchColumn() ?: null;
?>
<div class="page-hero" style="background:<?= $attempt['passed'] ? 'linear-gradient(135deg,#2e5e30,#3a7d44)' : 'linear-gradient(135deg,#5e2e2e,#7d3a3a)' ?>">
  <div class="container" style="text-align:center">
    <div style="font-size:64px;margin-bottom:16px"><?= $attempt['passed'] ? (icon('sparkles')) : (icon('frown')) ?></div>
    <h1 style="color:#fff"><?= $attempt['passed'] ? 'أحسنت! لقد نجحت' : 'لم تحقق نسبة النجاح' ?></h1>
    <div style="font-size:56px;font-weight:900;color:<?= $attempt['passed'] ? '#eac765' : '#fc8181' ?>;margin:16px 0">
      <?= round($attempt['percentage'], 1) ?>%
    </div>
    <p style="color:rgba(255,255,255,.8)"><?= $attempt['score'] ?> / <?= $attempt['max_score'] ?> نقطة · النجاح يتطلب <?= $ch['passing_score'] ?>%</p>
  </div>
</div>

<div class="section container" style="max-width:800px">
  <!-- Action buttons -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px">
    <!-- Reset button uses POST form to avoid CSRF issues -->
    <form method="POST" action="<?= BASE_URL ?>/challenges/reset" style="display:inline" data-confirm="هل تريد إعادة التحدي من البداية؟">
      <?= csrf_field() ?>
      <input type="hidden" name="challenge_id" value="<?= $ch['id'] ?>">
      <button type="submit" class="btn btn--gold btn--lg"><?= icon('refresh') ?> إعادة التحدي</button>
    </form>
    <a href="<?= BASE_URL ?>/challenges" class="btn btn--outline">← كل التحديات</a>
    <?php if ($attempt['passed'] && auth()): ?>
    <a href="<?= BASE_URL ?>/account/certificates" class="btn btn--green"><?= icon('award') ?> شهاداتي</a>
    <?php endif; ?>
  </div>

  <?php if ($attempt['passed']): ?>
  <div style="background:#eff8f1;border:2px solid #b2dfc0;border-radius:var(--r-lg);padding:24px;margin-bottom:32px;text-align:center">
    <?php if ($_certToken): ?>
      <p style="font-weight:700;color:#1e6b3a;font-size:16px"><?= icon('award') ?> شهادتك جاهزة!</p>
      <div style="display:flex;gap:10px;justify-content:center;margin-top:14px;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/certificates/verify?token=<?= e($_certToken) ?>" class="btn btn--outline btn--sm" target="_blank"><?= icon('search') ?> عرض الشهادة</a>
        <a href="<?= BASE_URL ?>/certificates/download-pdf?token=<?= e($_certToken) ?>" class="btn btn--gold btn--sm"><?= icon('file-text') ?> تحميل PDF</a>
      </div>
    <?php else: ?>
      <p style="font-weight:700;color:#1e6b3a;font-size:16px">تهانينا! أنت مؤهل للحصول على شهادة.</p>
      <p style="font-size:13px;color:var(--text-muted);margin-top:8px">تحقق من <a href="<?= BASE_URL ?>/account/certificates">صفحة شهاداتك</a>.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Answer review -->
  <h2 style="font-family:var(--font-display);color:var(--text-dark);margin-bottom:20px">مراجعة الإجابات</h2>
  <?php foreach ($questions as $qi => $q):
    $opts      = $optsByQ[$q['id']] ?? [];
    $myAnswers = array_map('intval', (array)($savedAnswers[$q['id']] ?? []));
    $corrects  = array_map('intval', array_column(array_filter($opts, fn($o) => $o['is_correct']), 'id'));
    sort($myAnswers); sort($corrects);
    $isCorrect = $myAnswers === $corrects && !empty($corrects);
  ?>
  <div class="result-question <?= $isCorrect ? 'result-question--correct' : 'result-question--wrong' ?>">
    <div class="result-q-header">
      <span class="result-q-num">السؤال <?= $qi+1 ?></span>
      <span class="result-q-status"><?= $isCorrect ? (icon('circle-check') . ' صحيح') : (icon('circle-x') . ' خطأ') ?></span>
      <span style="font-size:12px;color:var(--text-muted)"><?= $q['points'] ?> نقطة</span>
    </div>
    <!-- Render question markdown -->
    <div class="result-q-text" data-raw="<?= e($q['question']) ?>"></div>
    <div class="result-options">
      <?php foreach ($opts as $opt):
        $myChose  = in_array((int)$opt['id'], $myAnswers);
        $correct  = (bool)$opt['is_correct'];
      ?>
      <div class="result-opt <?= $correct ? 'result-opt--correct' : '' ?> <?= $myChose && !$correct ? 'result-opt--wrong' : '' ?>">
        <span><?= $correct ? (icon('circle-check')) : ($myChose ? (icon('circle-x')) : (icon('circle'))) ?></span>
        <span><?= e($opt['option_text']) ?></span>
        <?php if ($myChose): ?><span style="font-size:11px;color:var(--text-muted)">(اخترتها)</span><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script nonce="<?= csp_nonce() ?>">
// Lightweight markdown for review
function renderMd(raw) {
  raw = (raw + '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  return raw
    .replace(/```([\s\S]*?)```/g,'<pre class="ch-code"><code>$1</code></pre>')
    .replace(/`([^`]+)`/g,'<code class="ch-inline-code">$1</code>')
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
    .replace(/\*(.+?)\*/g,'<em>$1</em>')
    .replace(/\n/g,'<br>');
}
document.querySelectorAll('.result-q-text[data-raw]').forEach(el => {
  el.innerHTML = renderMd(el.dataset.raw);
});
</script>
