<div class="page-hero">
  <div class="container">
    <h1><?= e($ch['title']) ?></h1>
    <?php if ($ch['description']): ?><p><?= nl2br(e($ch['description'])) ?></p><?php endif; ?>
  </div>
</div>

<div class="section container" style="max-width:700px">
  <?php if ($ch['cover_image']): ?>
  <div style="border-radius:var(--r-lg);overflow:hidden;margin-bottom:28px;max-height:280px">
    <img src="<?= BASE_URL . e($ch['cover_image']) ?>" alt="<?= e($ch['title']) ?>" style="width:100%;object-fit:cover">
  </div>
  <?php endif; ?>

  <div class="apply-form-wrap">
    <div class="ch-info-grid">
      <div class="ch-info-item"><div class="ch-info-icon"><?= icon('target') ?></div><div class="ch-info-label">عدد الأسئلة</div><div class="ch-info-val"><?= $qCount ?></div></div>
      <div class="ch-info-item"><div class="ch-info-icon"><?= icon('circle-check') ?></div><div class="ch-info-label">نسبة النجاح</div><div class="ch-info-val"><?= $ch['passing_score'] ?>%</div></div>
      <?php if ($ch['time_limit']): ?><div class="ch-info-item"><div class="ch-info-icon"><?= icon('clock') ?></div><div class="ch-info-label">الوقت</div><div class="ch-info-val"><?= $ch['time_limit'] ?> دقيقة</div></div><?php endif; ?>
      <div class="ch-info-item"><div class="ch-info-icon"><?= icon('award') ?></div><div class="ch-info-label">الشهادة</div><div class="ch-info-val">عند النجاح</div></div>
    </div>

    <?php if ($attempt && $attempt['status'] === 'in_progress'): ?>
    <div style="background:var(--gold-faint);border:1px solid var(--gold-pale);border-radius:var(--r-md);padding:20px;margin:20px 0;text-align:center">
      <p style="font-weight:700;color:var(--text-dark);margin-bottom:8px">لديك محاولة جارية</p>
      <div class="ch-progress" style="margin-bottom:8px"><div class="ch-progress-bar" style="width:<?= challenge_progress_pct($attempt) ?>%"></div></div>
      <p style="font-size:13px;color:var(--text-muted)">تقدمك الحالي: <?= challenge_progress_pct($attempt) ?>%</p>
    </div>
    <?php elseif ($attempt && $attempt['status'] === 'completed'): ?>
    <?php $resultBg = $attempt['passed'] ? 'background:var(--ok-bg);border:1px solid var(--ok-br)' : 'background:var(--err-bg);border:1px solid var(--err-br)'; ?>
    <div style="border-radius:var(--r-md);padding:20px;margin:20px 0;text-align:center;<?= $resultBg ?>">
      <p style="font-weight:700;color:<?= $attempt['passed']?'var(--ok-fg)':'var(--err-fg)' ?>;margin-bottom:4px"><?= $attempt['passed']?(icon('sparkles') . ' نجحت في هذا التحدي!'):'لم تحقق نسبة النجاح' ?></p>
      <p style="font-size:14px">نتيجتك: <strong><?= round($attempt['percentage'],1) ?>%</strong></p>
    </div>
    <?php endif; ?>

    <?php $user = auth(); ?>
    <?php if (!$user): ?>
    <div style="text-align:center;padding:20px">
      <p style="color:var(--text-muted);margin-bottom:16px">يجب تسجيل الدخول لخوض التحديات وحفظ تقدمك.</p>
      <a href="<?= BASE_URL ?>/auth/login" class="btn btn--gold btn--lg">تسجيل الدخول ←</a>
      <a href="<?= BASE_URL ?>/auth/register" class="btn btn--outline btn--lg" style="margin-right:10px">إنشاء حساب</a>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:12px;margin-top:20px">
      <a href="<?= BASE_URL ?>/challenges/take?id=<?= $ch['id'] ?>" class="btn btn--gold btn--lg" style="flex:1;justify-content:center">
        <?= $attempt && $attempt['status']==='in_progress' ? (icon('forward') . ' متابعة التحدي') : ($attempt ? (icon('refresh') . ' إعادة المحاولة') : (icon('rocket') . ' ابدأ التحدي')) ?>
      </a>
      <?php if ($attempt && $attempt['status']==='completed'): ?>
      <a href="<?= BASE_URL ?>/challenges/result?id=<?= $ch['id'] ?>" class="btn btn--outline btn--lg">عرض النتيجة</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div style="margin-top:16px;text-align:center">
    <a href="<?= BASE_URL ?>/challenges" style="color:var(--text-muted);font-size:13px">← كل التحديات</a>
  </div>
</div>
