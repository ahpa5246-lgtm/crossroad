<div class="page-wrap" style="max-width:520px">
  <h1 class="page-title"><?= icon('phone') ?> إعداد Google Authenticator</h1>

  <?php if($e=flash('error')): ?>
  <div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div>
  <?php endif; ?>

  <div class="account-card" style="margin-bottom:16px">
    <h3 style="margin-bottom:14px">1. امسح رمز QR بتطبيق المصادقة</h3>
    <div style="text-align:center;margin-bottom:16px">
      <img src="<?= e($qrUrl) ?>" alt="QR Code" style="width:180px;height:180px;border-radius:12px;border:4px solid var(--sage-faint)">
    </div>
    <p style="font-size:13px;color:var(--tm);margin-bottom:10px">أو أدخل المفتاح يدويا في التطبيق:</p>
    <div style="background:var(--bg-2);border-radius:var(--r-sm);padding:12px;text-align:center;font-family:monospace;font-size:15px;font-weight:700;letter-spacing:3px;color:var(--td)">
      <?= e($secret) ?>
    </div>
    <p style="font-size:12px;color:var(--tm);margin-top:8px">التطبيقات المدعومة: Google Authenticator · Authy · Microsoft Authenticator</p>
  </div>

  <div class="account-card">
    <h3 style="margin-bottom:14px">2. أدخل الرمز لتأكيد الإعداد</h3>
    <form action="<?= BASE_URL ?>/account/2fa/enable" method="POST" class="form-stack">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">رمز التحقق (6 أرقام)</label>
        <input type="text" name="code" class="form-input" maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code" required placeholder="000000" style="font-size:24px;letter-spacing:6px;text-align:center">
      </div>
      <button class="btn btn--gold"><?= icon('circle-check') ?> تفعيل التحقق بخطوتين</button>
    </form>
  </div>
</div>
