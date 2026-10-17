<div class="auth-page auth-page--center">
  <div class="auth-main auth-main--center"><div class="auth-box">
    <div style="text-align:center;font-size:52px;margin-bottom:16px"><?= icon('lock') ?></div>
    <h1 style="text-align:center;margin-bottom:8px">اختر طريقة التحقق</h1>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:28px;font-size:14px">
      لديك طريقتان للتحقق بخطوتين. اختر التي تفضلها.
    </p>
    <?php if ($e=flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>

    <form action="<?= BASE_URL ?>/auth/2fa/choose" method="POST" class="auth-form">
      <?= csrf_field() ?>
      <div style="display:flex;flex-direction:column;gap:12px">
        <button type="submit" name="method" value="totp"
                class="btn btn--outline btn--full"
                style="padding:18px;font-size:15px;display:flex;align-items:center;gap:12px;justify-content:center">
          <span style="font-size:28px"><?= icon('phone') ?></span>
          <div style="text-align:right">
            <div style="font-weight:700">Google Authenticator</div>
            <div style="font-size:12px;color:var(--tm);font-weight:400">الرمز من تطبيق المصادقة</div>
          </div>
        </button>
        <button type="submit" name="method" value="email"
                class="btn btn--outline btn--full"
                style="padding:18px;font-size:15px;display:flex;align-items:center;gap:12px;justify-content:center">
          <span style="font-size:28px"><?= icon('mail') ?></span>
          <div style="text-align:right">
            <div style="font-weight:700">رمز عبر البريد الإلكتروني</div>
            <div style="font-size:12px;color:var(--tm);font-weight:400">سيرسل رمز إلى بريدك المسجل</div>
          </div>
        </button>
      </div>
    </form>

    <p style="text-align:center;margin-top:20px">
      <a href="<?= BASE_URL ?>/auth/login" style="color:var(--text-muted);font-size:13px">← العودة للدخول</a>
    </p>
  </div></div>
</div>
