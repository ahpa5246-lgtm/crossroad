<div class="page-hero"><div class="container"><h1><?= icon('award') ?> شهاداتي</h1></div></div>
<div class="section container" style="max-width:860px">
  <?php if (empty($certificates)): ?>
  <div class="empty-card">
    <div style="font-size:48px;margin-bottom:12px"><?= icon('award') ?></div>
    <h3>لا توجد شهادات بعد</h3>
    <p>أكمل التحديات للحصول على شهاداتك.</p>
    <a href="<?= BASE_URL ?>/challenges" class="btn btn--gold" style="margin-top:16px">ابدأ تحديا ←</a>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($certificates as $c): ?>
    <div class="cert-card protect-content" style="display:flex;align-items:center;gap:16px;padding:20px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--r-lg);flex-wrap:wrap">
      <!-- Icon -->
      <div style="font-size:42px;flex-shrink:0"><?= $c['is_valid'] ? (icon('award')) : (icon('circle-x')) ?></div>

      <!-- Info -->
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:15px;color:var(--text-dark)"><?= e($c['title']) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;display:flex;flex-wrap:wrap;gap:8px">
          <?php if ($c['challenge_title']): ?>
          <span><?= icon('target') ?> <?= e($c['challenge_title']) ?></span>
          <?php endif; ?>
          <?php if ($c['form_title']): ?>
          <span><?= icon('clipboard') ?> <?= e($c['form_title']) ?></span>
          <?php endif; ?>
          <span><?= icon('calendar') ?> <?= date('d/m/Y', strtotime($c['issued_at'])) ?></span>
          <?php if ($c['expires_at']): ?>
          <span><?= icon('hourglass') ?> تنتهي: <?= date('d/m/Y', strtotime($c['expires_at'])) ?></span>
          <?php endif; ?>
        </div>
        <?php if (!$c['is_valid']): ?>
        <div style="margin-top:6px">
          <span class="badge badge--red">ملغاة</span>
        </div>
        <?php endif; ?>
        <!-- Token preview -->
        <div style="font-family:monospace;font-size:10px;color:var(--text-muted);margin-top:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:340px">
          رمز: <?= e(substr($c['token'], 0, 20)) ?>...
        </div>
      </div>

      <!-- Actions -->
      <?php if ($c['is_valid']): ?>
      <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/certificates/view?token=<?= e($c['token']) ?>"
           class="btn btn--sm btn--gold" target="_blank" rel="noopener">
          <?= icon('award') ?> عرض الشهادة
        </a>
        <a href="<?= BASE_URL ?>/certificates/verify?token=<?= e($c['token']) ?>"
           class="btn btn--sm btn--outline" target="_blank" rel="noopener">
          <?= icon('search') ?> تحقق
        </a>
        <a href="<?= BASE_URL ?>/account/certificates/download?token=<?= e($c['token']) ?>"
           class="btn btn--sm btn--outline" title="تحميل PDF">
          <?= icon('download') ?> تحميل PDF
        </a>
      </div>
      <?php else: ?>
      <span style="font-size:12px;color:var(--err-fg);font-weight:600">ملغاة</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
