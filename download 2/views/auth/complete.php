<div class="auth-simple"><div class="auth-card">
  <div class="auth-logo"><?php if (brand_has_logo()): ?><?= brand_logo_html('auth-logo-img') ?><?php else: ?><span class="nav-compass"><?= icon('compass', 28) ?></span><?php endif; ?></div>
  <h1>إكمال الحساب</h1>
  <p class="auth-sub">تم التحقق من بريدك عبر Google: <strong dir="ltr"><?= e($email) ?></strong><br>أكمل بياناتك لإنشاء الحساب.</p>
  <?php if ($e=flash('error')): ?><div class="alert alert--error"><?= $e ?></div><?php endif; ?>
  <?php $old = flash('old') ?: []; ?>
  <form action="<?= BASE_URL ?>/auth/complete" method="POST" class="auth-form">
    <?= csrf_field() ?>
    <input type="text" name="_website" style="display:none" tabindex="-1" autocomplete="off">
    <div class="form-group"><label class="form-label">الاسم الكامل <span class="required-star">*</span></label><input type="text" name="display_name" class="form-input" required minlength="2" maxlength="100" autofocus value="<?= e($old['display_name']??'') ?>"></div>
    <div class="form-group"><label class="form-label">اسم المستخدم <span class="required-star">*</span></label><input type="text" name="username" class="form-input" required pattern="[a-zA-Z0-9_]{3,30}" placeholder="username" style="direction:ltr;text-align:left" value="<?= e($old['username']??'') ?>"></div>
    <div class="form-group"><label class="form-label">معرف التيليغرام <span class="required-star">*</span> <span class="form-hint">— للتواصل عند القبول</span></label><input type="text" name="telegram_username" class="form-input" required placeholder="@username" style="direction:ltr;text-align:left" value="<?= e($old['telegram_username']??'') ?>"></div>
    <div class="form-checks">
      <label class="check-label"><input type="checkbox" name="agree_terms" required><span>أوافق على <a href="<?= BASE_URL ?>/terms" target="_blank">الشروط والأحكام</a></span></label>
      <label class="check-label"><input type="checkbox" name="email_notify" checked><span>أرغب في تلقي إشعارات البرامج</span></label>
    </div>
    <button type="submit" class="btn btn--gold btn--full btn--lg">إنشاء الحساب</button>
  </form>
</div></div>
