<?php /* No layout needed, use main */ ?>
<div class="verify-wrap">
  <h1 style="text-align:center;font-family:var(--font-display);color:var(--text-dark);margin-bottom:24px">التحقق من الشهادات</h1>
  <form method="GET" action="<?= BASE_URL ?>/certificates/verify" style="display:flex;gap:10px;margin-bottom:32px">
    <input type="text" name="token" class="form-input" placeholder="أدخل رمز الشهادة" value="<?= e($token) ?>" style="flex:1">
    <button class="btn btn--gold">تحقق</button>
  </form>
  <?php if ($token && !$cert): ?>
  <div style="background:#fdf3f3;border:2px solid #f5bebe;border-radius:var(--r-lg);padding:40px;text-align:center">
    <div style="font-size:64px;margin-bottom:16px"><?= icon('circle-x') ?></div>
    <h2 style="color:#b22222;font-family:var(--font-display);margin-bottom:10px">الشهادة غير موجودة أو ملغاة</h2>
    <p style="color:var(--text-muted)">الرمز لا يطابق أي شهادة صادرة من <?= e(brand_name()) ?>.</p>
  </div>
  <?php elseif ($cert): ?>
  <div style="background:#eff8f1;border:2px solid #b2dfc0;border-radius:var(--r-lg);padding:40px;text-align:center" class="cert-protected">
    <div style="font-size:64px;margin-bottom:16px"><?= icon('circle-check') ?></div>
    <h2 style="color:#1e6b3a;font-family:var(--font-display);margin-bottom:8px">شهادة أصلية</h2>
    <p style="color:#1e6b3a;margin-bottom:24px">هذه الشهادة صادرة رسميا من <?= e(brand_name()) ?>.</p>
    <table style="width:100%;border-collapse:collapse;text-align:right;max-width:500px;margin:0 auto">
      <tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.1);width:40%">الاسم:</td><td style="padding:10px 14px"><?= e($cert['display_name']) ?></td></tr>
      <tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.06)">المستخدم:</td><td style="padding:10px 14px">@<?= e($cert['username']) ?> <?= $cert['telegram_username']?' · '.e($cert['telegram_username']):'' ?></td></tr>
      <tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.1)">الشهادة:</td><td style="padding:10px 14px"><?= e($cert['title']) ?></td></tr>
      <?php if ($cert['form_title']): ?><tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.06)">البرنامج:</td><td style="padding:10px 14px"><?= e($cert['form_title']) ?></td></tr><?php endif; ?>
      <?php if ($cert['challenge_title']): ?><tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.1)">التحدي:</td><td style="padding:10px 14px"><?= e($cert['challenge_title']) ?></td></tr><?php endif; ?>
      <?php if ($cert['description']): ?><tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.06)">الوصف:</td><td style="padding:10px 14px"><?= e($cert['description']) ?></td></tr><?php endif; ?>
      <tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.1)">تاريخ الإصدار:</td><td style="padding:10px 14px"><?= date('d/m/Y', strtotime($cert['issued_at'])) ?></td></tr>
      <?php if ($cert['expires_at']): ?><tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.06)">الانتهاء:</td><td style="padding:10px 14px"><?= date('d/m/Y', strtotime($cert['expires_at'])) ?></td></tr><?php endif; ?>
      <tr><td style="padding:10px 14px;font-weight:700;color:var(--sage-dark);background:rgba(191,143,101,.1)">الرمز:</td><td style="padding:10px 14px;font-family:monospace;font-size:11px"><?= e($cert['token']) ?></td></tr>
    </table>
  </div>
  <?php else: ?>
  <div style="text-align:center;color:var(--text-muted);padding:48px 0">
    <div style="font-size:64px;margin-bottom:16px"><?= icon('award') ?></div>
    <h2 style="color:var(--text-dark);font-family:var(--font-display);margin-bottom:10px">التحقق من صحة الشهادات</h2>
    <p>أدخل رمز الشهادة للتحقق من أصالتها.</p>
  </div>
  <?php endif; ?>
</div>
