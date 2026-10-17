<div class="auth-simple"><div class="auth-card">
  <div class="auth-logo"><?php if (brand_has_logo()): ?><?= brand_logo_html('auth-logo-img') ?><?php else: ?><span class="nav-compass"><?= icon('compass', 28) ?></span><?php endif; ?></div>
  <h1>تسجيل الدخول</h1>
  <p class="auth-sub">ليس لديك حساب؟ <a href="<?= BASE_URL ?>/auth/register">أنشئ حسابا</a></p>
  <?php if ($e=flash('error')): ?><div class="alert alert--error"><?= e($e) ?></div><?php endif; ?>
  <?php if ($s=flash('success')): ?><div class="alert alert--success"><?= e($s) ?></div><?php endif; ?>
    <?php if (!empty($googleOn)): ?>
    <a href="<?= BASE_URL ?>/auth/google" class="google-btn"><svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.9 2.4 30.4 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.5 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.5-4.1 7-10.2 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-3-.8-4.7s.3-3.3.8-4.7l-7.9-6.1C.9 16.4 0 20.1 0 24s.9 7.6 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg><span>المتابعة بحساب Google</span></a>
    <div class="auth-or"><span>أو</span></div>
    <?php endif; ?>
  <form action="<?= BASE_URL ?>/auth/login" method="POST" class="auth-form" id="loginForm">
    <?= csrf_field() ?>
    <input type="text" name="_website" style="display:none" tabindex="-1" autocomplete="off">
    <input type="hidden" name="g-recaptcha-response" id="gRecaptchaResponse">
    <div class="form-group"><label class="form-label">اسم المستخدم أو البريد</label><input type="text" name="identifier" class="form-input" required autofocus autocomplete="username"></div>
    <div class="form-group">
      <div class="auth-row"><label class="form-label">كلمة المرور</label><a href="<?= BASE_URL ?>/auth/forgot-password" class="auth-link-sm">نسيت كلمة المرور؟</a></div>
      <div class="input-wrap"><input type="password" name="password" class="form-input" required autocomplete="current-password"><button type="button" class="input-eye" aria-label="إظهار كلمة المرور" aria-pressed="false"><span class="eye-show"><?= icon('eye') ?></span><span class="eye-hide"><?= icon('eye-off') ?></span></button></div>
    </div>
    <label class="check-label"><input type="checkbox" name="remember_me"><span>تذكرني لمدة 30 يوما</span></label>
      <?php $rcKey = Config::get('RECAPTCHA_SITE_KEY', ''); if ($rcKey): ?>
      <div class="recaptcha-container" style="margin:14px 0;display:flex;justify-content:center"><div class="g-recaptcha" data-sitekey="<?= e($rcKey) ?>" data-callback="onRecaptchaSuccess" data-expired-callback="onRecaptchaExpired"></div></div>
      <div id="recaptchaError" class="alert alert--error" style="display:none">يرجى إكمال التحقق (أنا لست روبوتا).</div>
      <?php endif; ?>
    <button type="submit" class="btn btn--gold btn--full btn--lg" id="loginBtn" <?= Config::get('RECAPTCHA_SITE_KEY', '') ? 'disabled' : '' ?>>تسجيل الدخول</button>
  </form>
</div></div>
<?php if (Config::get('RECAPTCHA_SITE_KEY', '')): ?>
<script nonce="<?= csp_nonce() ?>" src="https://www.google.com/recaptcha/api.js" async defer></script>
<script nonce="<?= csp_nonce() ?>">
var recaptchaVerified=false;
function onRecaptchaSuccess(t){recaptchaVerified=true;var r=document.getElementById('gRecaptchaResponse');if(r)r.value=t;var b=document.getElementById('loginBtn');if(b)b.disabled=false;var e=document.getElementById('recaptchaError');if(e)e.style.display='none';}
function onRecaptchaExpired(){recaptchaVerified=false;var r=document.getElementById('gRecaptchaResponse');if(r)r.value='';var b=document.getElementById('loginBtn');if(b)b.disabled=true;}
document.getElementById('loginForm').addEventListener('submit',function(e){if(!recaptchaVerified){e.preventDefault();var x=document.getElementById('recaptchaError');if(x)x.style.display='block';}});
</script>
<?php endif; ?>
