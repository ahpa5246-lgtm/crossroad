<div class="auth-page auth-page--center">
  <div class="auth-main auth-main--center"><div class="auth-box">
    <div style="text-align:center;font-size:56px;margin-bottom:20px">
      <?= ($method ?? 'totp') === 'email' ? (icon('mail')) : (icon('phone')) ?>
    </div>
    <h1 style="text-align:center">التحقق بخطوتين</h1>

    <?php if (($method ?? 'totp') === 'email'): ?>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:8px;font-size:14px">
      تم إرسال رمز التحقق إلى بريدك
      <strong style="color:var(--td)"><?= e($maskedEmail ?? '') ?></strong>
    </p>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:24px;font-size:12px">الرمز صالح 10 دقائق</p>
    <?php else: ?>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:24px">أدخل الرمز من تطبيق Google Authenticator.</p>
    <?php endif; ?>

    <?php if ($e=flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>
    <?php if ($s=flash('success')): ?><div class="alert alert--success" style="margin-bottom:16px"><?= e($s) ?></div><?php endif; ?>

    <form action="<?= BASE_URL ?>/auth/2fa" method="POST" class="auth-form" id="twoFaForm">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" style="text-align:center;display:block">رمز التحقق</label>
        <input type="text" name="code" id="twoFaCode" class="form-input otp-input"
               placeholder="000000" maxlength="6" pattern="\d{6}" inputmode="numeric"
               autocomplete="one-time-code" required autofocus
               style="font-size:26px;letter-spacing:8px;text-align:center">
      </div>
      <button type="submit" class="btn btn--gold btn--full btn--lg">تحقق وادخل ←</button>
    </form>

    <?php if (($method ?? 'totp') === 'email'): ?>
    <div style="text-align:center;margin-top:14px">
      <form action="<?= BASE_URL ?>/auth/2fa/resend" method="POST" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--ghost btn--sm" style="font-size:13px"><?= icon('refresh') ?> إعادة إرسال الرمز</button>
      </form>
    </div>
    <?php endif; ?>

    <p style="text-align:center;margin-top:16px">
      <a href="<?= BASE_URL ?>/auth/login" style="color:var(--text-muted);font-size:13px">← العودة للدخول</a>
    </p>
  </div></div>
</div>
<script nonce="<?= csp_nonce() ?>">
document.getElementById('twoFaCode').addEventListener('input', function(){
  this.value = this.value.replace(/\D/g,'').slice(0,6);
  if (this.value.length === 6) document.getElementById('twoFaForm').submit();
});
</script>
