<div class="auth-simple"><div class="auth-card">
  <div class="auth-logo"><?php if (brand_has_logo()): ?><?= brand_logo_html('auth-logo-img') ?><?php else: ?><span class="nav-compass"><?= icon('compass', 28) ?></span><?php endif; ?></div>
  <h1><?= $purpose === 'register' ? 'تفعيل الحساب' : 'التحقق من الرمز' ?></h1>
  <p class="auth-sub">أرسلنا رمزا من 6 أرقام إلى <strong dir="ltr"><?= e($maskedEmail) ?></strong></p>
  <?php if ($e=flash('error')): ?><div class="alert alert--error"><?= $e ?></div><?php endif; ?>
  <?php if ($s=flash('success')): ?><div class="alert alert--success"><?= e($s) ?></div><?php endif; ?>
  <form action="<?= BASE_URL ?>/auth/<?= $purpose === 'register' ? 'register-verify' : 'reset-otp' ?>" method="POST" class="auth-form">
    <?= csrf_field() ?>
    <div class="form-group">
      <label class="form-label">رمز التحقق</label>
      <input type="text" name="otp" id="otpInput" class="form-input otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" autocomplete="one-time-code" required autofocus>
      <div class="otp-digits" id="otpDots" aria-hidden="true"><?php for($i=0;$i<6;$i++): ?><span class="otp-dot"></span><?php endfor; ?></div>
    </div>
    <div style="text-align:center;margin-bottom:14px"><span style="font-size:13px;color:var(--tm)">ينتهي الرمز خلال: </span><span id="countdown" style="font-size:13px;font-weight:700;font-family:monospace">10:00</span></div>
    <button type="submit" class="btn btn--gold btn--full btn--lg" id="verifyBtn">تحقق</button>
  </form>
  <form action="<?= BASE_URL ?>/auth/resend-otp" method="POST" style="margin-top:14px;text-align:center">
    <?= csrf_field() ?><input type="hidden" name="purpose" value="<?= e($purpose) ?>">
    <button type="submit" id="resendBtn" class="btn btn--outline btn--sm" disabled>إعادة إرسال الرمز (<span id="resendTimer">60</span>s)</button>
  </form>
  <p class="auth-sub" style="margin-top:14px"><a href="<?= BASE_URL ?>/auth/<?= $purpose === 'register' ? 'register' : 'forgot-password' ?>">← رجوع</a></p>
</div></div>
<style>
.otp-input {
  text-align: center;
  font-size: 32px;
  font-family: monospace;
  font-weight: 800;
  letter-spacing: 16px;
  padding: 16px 20px;
  color: var(--sage-dark);
}
.otp-input::placeholder { color: var(--border); letter-spacing: 12px; }
.otp-dots { display: flex; justify-content: center; gap: 10px; margin-top: 8px; pointer-events: none; }
.otp-dot {
  width: 10px; height: 10px; border-radius: 50%;
  border: 2px solid var(--border);
  background: transparent;
  transition: all .2s;
}
.otp-dot.filled { background: var(--sage-dark); border-color: var(--sage-dark); }
.otp-dot.current { border-color: var(--gold); transform: scale(1.2); }
</style>

<script nonce="<?= csp_nonce() ?>">
var input = document.getElementById('otpInput');
var dots  = document.querySelectorAll('.otp-dot');

function updateDots(val) {
  dots.forEach(function(d, i) {
    d.classList.toggle('filled', i < val.length);
    d.classList.toggle('current', i === val.length && i < 6);
  });
}

input.addEventListener('input', function() {
  var v = this.value.replace(/\D/g, '').slice(0, 6);
  this.value = v;
  updateDots(v);
  if (v.length === 6) {
    document.getElementById('verifyBtn').focus();
  }
});

updateDots('');

// Countdown (10 min)
var secs = 600;
var ctEl = document.getElementById('countdown');
var ci = setInterval(function() {
  secs--;
  if (secs <= 0) { clearInterval(ci); ctEl.textContent = 'انتهى'; ctEl.style.color = '#c0392b'; return; }
  var m = Math.floor(secs/60), s = secs%60;
  ctEl.textContent = m + ':' + (s<10?'0':'') + s;
  if (secs <= 60) ctEl.style.color = '#c0392b';
}, 1000);

// Resend timer (60s)
var resendSecs = 60;
var resendBtn  = document.getElementById('resendBtn');
var resendEl   = document.getElementById('resendTimer');
var ri = setInterval(function() {
  resendSecs--;
  resendEl.textContent = resendSecs;
  if (resendSecs <= 0) {
    clearInterval(ri);
    resendBtn.disabled = false;
    resendBtn.textContent = 'إعادة إرسال الرمز';
  }
}, 1000);
</script>
