<div class="page-wrap" style="max-width:480px">
  <h1 class="page-title"><?= e($pageTitle ?? 'التحقق برمز OTP') ?></h1>

  <?php if ($e=flash('error')): ?>
  <div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div>
  <?php endif; ?>
  <?php if ($s=flash('success')): ?>
  <div class="alert alert--success" style="margin-bottom:16px"><?= e($s) ?></div>
  <?php endif; ?>

  <div class="account-card" style="<?= !empty($danger) ? 'border:2px solid #e53e3e' : '' ?>">
    <?php if (!empty($danger)): ?>
    <div style="background:#fff5f5;border-radius:10px;padding:14px 16px;margin-bottom:20px;border:1px solid #fed7d7;color:#c53030;font-size:13px">
      <?= icon('alert') ?> <strong>تحذير:</strong> هذا الإجراء <strong>لا يمكن التراجع عنه</strong>. تأكد أنك تريد المتابعة.
    </div>
    <?php endif; ?>

    <p style="color:var(--tm);font-size:14px;margin-bottom:24px">
      تم إرسال رمز التحقق المكون من 6 أرقام إلى بريدك
      <strong style="color:var(--td)"><?= e($maskedEmail ?? '') ?></strong>.
      الرمز صالح لمدة 10 دقائق.
    </p>

    <form action="<?= BASE_URL . e($_SERVER['REQUEST_URI']) ?>" method="POST" class="form-stack" id="otpForm">
      <?= csrf_field() ?>
      <input type="hidden" name="purpose" value="<?= e($purpose ?? '') ?>">
      <div class="form-group">
        <label class="form-label" style="text-align:center;display:block">رمز OTP</label>
        <input type="text" name="otp" id="otpInput" class="form-input"
               style="font-size:28px;letter-spacing:10px;text-align:center;font-family:monospace"
               maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code"
               placeholder="000000" required autofocus>
      </div>
      <button type="submit" class="btn <?= !empty($danger) ? 'btn--danger' : 'btn--gold' ?> btn--full">
        <?= !empty($danger) ? (icon('trash') . ' تأكيد الحذف النهائي') : (icon('circle-check') . ' تأكيد') ?>
      </button>
    </form>

    <div style="margin-top:16px;text-align:center">
      <form action="<?= BASE_URL ?>/account/otp/resend" method="POST" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="purpose" value="<?= e($purpose ?? '') ?>">
        <button type="submit" class="btn btn--ghost btn--sm" style="font-size:13px">
          <?= icon('refresh') ?> إعادة إرسال الرمز
        </button>
      </form>
      <span style="color:var(--border);margin:0 8px">|</span>
      <a href="<?= BASE_URL . e($backUrl ?? '/account') ?>" style="font-size:13px;color:var(--tm)">← إلغاء</a>
    </div>
  </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function(){
  var inp = document.getElementById('otpInput');
  if (!inp) return;
  inp.addEventListener('input', function(){
    this.value = this.value.replace(/\D/g,'').slice(0,6);
    if (this.value.length === 6) document.getElementById('otpForm').submit();
  });
})();
</script>
