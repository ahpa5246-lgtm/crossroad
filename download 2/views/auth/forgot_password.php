<div class="auth-simple"><div class="auth-card">
  <div class="auth-logo"><?php if (brand_has_logo()): ?><?= brand_logo_html('auth-logo-img') ?><?php else: ?><span class="nav-compass"><?= icon('compass', 28) ?></span><?php endif; ?></div>
  <h1>نسيت كلمة المرور؟</h1>
  <p class="auth-sub">أدخل بريدك وسنرسل لك رمز تحقق لإعادة تعيين كلمة المرور.</p>
  <?php if ($e=flash('error')): ?><div class="alert alert--error"><?= e($e) ?></div><?php endif; ?>
  <?php if ($s=flash('success')): ?><div class="alert alert--success"><?= e($s) ?></div><?php endif; ?>
  <form action="<?= BASE_URL ?>/auth/forgot-password" method="POST" class="auth-form">
    <?= csrf_field() ?>
    <input type="text" name="_website" style="display:none" tabindex="-1" autocomplete="off">
    <div class="form-group"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-input" placeholder="example@gmail.com" required autofocus autocomplete="email" style="direction:ltr;text-align:left" value="<?= e($_SESSION['flash']['old']['email'] ?? '') ?>"></div>
    <button type="submit" class="btn btn--gold btn--full btn--lg">إرسال رمز التحقق</button>
  </form>
  <p class="auth-sub" style="margin-top:16px"><a href="<?= BASE_URL ?>/auth/login">← العودة لتسجيل الدخول</a></p>
</div></div>
