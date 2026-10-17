<style>
.guide-hero { text-align:center; padding:56px 20px 32px; }
.guide-hero h1 { font-family:var(--fd); font-size:clamp(26px,4vw,44px); color:var(--td); margin-bottom:12px; }
.guide-hero p  { color:var(--tm); font-size:16px; max-width:540px; margin:0 auto; line-height:1.7; }

.guide-steps { max-width:680px; margin:0 auto; display:flex; flex-direction:column; gap:40px; }

.guide-step { display:grid; grid-template-columns:52px 1fr; gap:20px; align-items:start; }
.step-bubble { width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,var(--sage-dark),var(--sage-deep)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; font-family:var(--fd); flex-shrink:0; box-shadow:var(--sh-md); }
.step-body { display:flex; flex-direction:column; gap:10px; }
.step-title { font-family:var(--fd); font-size:20px; color:var(--td); }
.step-desc { color:var(--tm); font-size:15px; line-height:1.75; }

/* Phone mockup */
.phone-demo { background:var(--card); border:1px solid var(--border); border-radius:var(--r-lg); overflow:hidden; margin-top:6px; }
.phone-screen { padding:16px; }
.app-row { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:var(--r-md); margin-bottom:6px; }
.app-icon-circle { width:44px; height:44px; border-radius:14px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:22px; }
.app-name { font-size:14px; font-weight:600; color:var(--td); }
.app-sub  { font-size:11px; color:var(--tm); }

.otp-demo { background:linear-gradient(135deg,var(--sage-faint),var(--gold-faint)); border-radius:var(--r-lg); padding:20px; text-align:center; border:1px solid var(--border-l); margin-top:6px; }
.otp-label { font-size:12px; color:var(--tm); margin-bottom:6px; }
.otp-code  { font-size:36px; font-weight:900; letter-spacing:8px; color:var(--sage-dark); font-family:'Courier New',monospace; }
.otp-timer { margin-top:8px; font-size:12px; color:var(--tm); }
.otp-bar   { height:4px; background:var(--border); border-radius:2px; margin-top:8px; overflow:hidden; }
.otp-fill  { height:100%; width:60%; background:var(--sage-dark); border-radius:2px; animation:shrink 30s linear infinite; }
@keyframes shrink { from{width:100%} to{width:0} }

.store-btns { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
.store-btn  { display:flex; align-items:center; gap:8px; padding:10px 18px; border-radius:var(--r-md); border:1.5px solid var(--border); background:var(--card); color:var(--td); font-size:13px; font-weight:600; text-decoration:none; transition:all .2s; }
.store-btn:hover { border-color:var(--sage-dark); background:var(--sage-faint); color:var(--sage-dark); }
.store-icon { font-size:22px; }

.tip-box { background:var(--gold-faint); border:1px solid var(--gold-pale); border-radius:var(--r-md); padding:14px 18px; font-size:14px; color:var(--tb); display:flex; gap:10px; align-items:flex-start; margin-top:6px; }
.tip-icon { font-size:18px; flex-shrink:0; }

.divider { height:1px; background:var(--border-l); margin:8px 0; }

.qr-demo { width:140px; height:140px; background:linear-gradient(135deg,var(--bg-2),var(--bg-3)); border-radius:var(--r-md); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:12px; color:var(--tm); text-align:center; padding:10px; margin:10px auto; }

.action-banner { background:linear-gradient(135deg,var(--sage-deep),var(--sage-dark)); border-radius:var(--r-xl); padding:36px; text-align:center; margin:48px auto 0; max-width:680px; }
.action-banner h3 { font-family:var(--fd); font-size:24px; color:#fff; margin-bottom:10px; }
.action-banner p  { color:rgba(255,255,255,.8); margin-bottom:20px; }
</style>

<!-- Hero -->
<section class="guide-hero container">
  <h1><?= icon('lock') ?> كيف تفعل التحقق بخطوتين؟</h1>
  <p>التحقق بخطوتين (2FA) يضيف طبقة حماية إضافية لحسابك — حتى لو سرقت كلمة مرورك، لا يستطيع أحد الدخول بدون هاتفك.</p>
</section>

<div class="section container">
<div class="guide-steps">

  <!-- Step 1 -->
  <div class="guide-step reveal">
    <div class="step-bubble">١</div>
    <div class="step-body">
      <div class="step-title">حمل تطبيق المصادقة</div>
      <p class="step-desc">ستحتاج لتطبيق يولد رموز الدخول المؤقتة (TOTP). الموصى به هو <strong>Google Authenticator</strong> — مجاني وسهل.</p>
      <div class="store-btns">
        <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" rel="noopener" class="store-btn">
          <span class="store-icon"><?= icon('phone') ?></span>
          <div><div style="font-size:10px;color:var(--tm)">App Store</div><div>iOS / iPhone</div></div>
        </a>
        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" rel="noopener" class="store-btn">
          <span class="store-icon"><?= icon('bot') ?></span>
          <div><div style="font-size:10px;color:var(--tm)">Google Play</div><div>Android</div></div>
        </a>
      </div>
      <div class="phone-demo">
        <div class="phone-screen">
          <div style="font-size:12px;font-weight:700;color:var(--tm);margin-bottom:8px;padding:0 2px">متاح أيضا:</div>
          <div class="app-row" style="background:var(--bg-2)">
            <div class="app-icon-circle" style="background:#ed6b15"><?= icon('key') ?></div>
            <div><div class="app-name">Google Authenticator</div><div class="app-sub">الأكثر شيوعا · مجاني</div></div>
          </div>
          <div class="app-row">
            <div class="app-icon-circle" style="background:#d45500"><?= icon('lock') ?></div>
            <div><div class="app-name">Microsoft Authenticator</div><div class="app-sub">بديل ممتاز · مجاني</div></div>
          </div>
          <div class="app-row">
            <div class="app-icon-circle" style="background:#ff6d00"><?= icon('shield') ?></div>
            <div><div class="app-name">Authy</div><div class="app-sub">يدعم النسخ الاحتياطي · مجاني</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Step 2 -->
  <div class="guide-step reveal reveal--d1">
    <div class="step-bubble">٢</div>
    <div class="step-body">
      <div class="step-title">افتح إعداد 2FA في حسابك</div>
      <p class="step-desc">اذهب إلى <strong>حسابي ← الأمان ← تفعيل 2FA الآن</strong>. ستظهر لك شاشة مع رمز QR مربعات سوداء وبيضاء.</p>
      <div class="phone-demo">
        <div class="phone-screen" style="text-align:center;padding:20px">
          <div style="font-size:13px;font-weight:700;color:var(--td);margin-bottom:4px">إعداد التحقق بخطوتين</div>
          <div style="font-size:12px;color:var(--tm);margin-bottom:14px">امسح رمز QR بالتطبيق</div>
          <!-- QR visual mockup -->
          <div style="width:110px;height:110px;margin:0 auto;background:linear-gradient(135deg,var(--td) 0%,var(--td) 100%);border-radius:8px;padding:8px;display:flex;align-items:center;justify-content:center">
            <div style="width:94px;height:94px;display:grid;grid-template-columns:repeat(7,1fr);gap:1px">
              <?php
              $pattern=[1,1,1,1,1,1,1,1,0,0,0,0,0,1,1,0,1,1,1,0,1,1,0,1,0,1,0,1,1,0,1,1,1,0,1,1,0,0,0,0,0,1,1,1,1,1,1,1,1,1];
              foreach($pattern as $p): ?>
              <div style="background:<?= $p?'#fff':'#453323' ?>;border-radius:1px"></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div style="font-size:11px;color:var(--tm);margin-top:10px">أو أدخل الرمز يدويا</div>
          <div style="font-size:12px;font-weight:700;letter-spacing:3px;color:var(--sage-dark);background:var(--sage-faint);padding:6px 10px;border-radius:6px;margin:6px auto;display:inline-block">ABCD EFGH IJKL</div>
        </div>
      </div>
      <div class="tip-box">
        <span class="tip-icon"><?= icon('bulb') ?></span>
        <span>إذا كنت على الكمبيوتر، امسح الـ QR بكاميرا هاتفك من داخل التطبيق. إذا كنت على الجوال، اضغط على "إضافة حساب" في التطبيق ثم اختر "مسح الرمز".</span>
      </div>
    </div>
  </div>

  <!-- Step 3 -->
  <div class="guide-step reveal reveal--d2">
    <div class="step-bubble">٣</div>
    <div class="step-body">
      <div class="step-title">امسح رمز QR بالتطبيق</div>
      <p class="step-desc">افتح Google Authenticator ← اضغط <?= icon('plus') ?> ← "امسح رمز QR" ← وجه الكاميرا نحو الرمز على الشاشة.</p>
      <div class="phone-demo">
        <div class="phone-screen" style="padding:14px">
          <div style="font-size:11px;font-weight:700;color:var(--tm);margin-bottom:10px">داخل Google Authenticator:</div>
          <!-- Add account button -->
          <div style="background:var(--sage-dark);color:#fff;border-radius:var(--r-md);padding:10px 14px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;margin-bottom:8px">
            <span><?= icon('plus') ?></span> إضافة حساب
          </div>
          <!-- Options -->
          <div style="border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden">
            <div style="padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:13px;border-bottom:1px solid var(--border-l);background:var(--sage-faint)">
              <span><?= icon('camera') ?></span> <strong>امسح رمز QR</strong> <span style="margin-right:auto;font-size:10px;color:var(--tm)">← اختر هذا</span>
            </div>
            <div style="padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:13px;color:var(--tm)">
              <span><?= icon('keyboard') ?></span> أدخل مفتاح الإعداد يدويا
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Step 4 -->
  <div class="guide-step reveal reveal--d3">
    <div class="step-bubble">٤</div>
    <div class="step-body">
      <div class="step-title">أدخل الرمز المكون من 6 أرقام</div>
      <p class="step-desc">بعد المسح، سيظهر رمز مكون من 6 أرقام في التطبيق — يتغير كل 30 ثانية. أدخله في صفحة الموقع للتأكيد.</p>
      <div class="otp-demo">
        <div class="otp-label"><?= e(brand_name()) ?> · رمز الدخول</div>
        <div class="otp-code" id="demoCode">482 917</div>
        <div class="otp-timer">يتجدد كل 30 ثانية</div>
        <div class="otp-bar"><div class="otp-fill" id="otpFill"></div></div>
      </div>
      <div class="tip-box">
        <span class="tip-icon"><?= icon('alert') ?></span>
        <span>الرمز صالح لـ 30 ثانية فقط. إذا انتهى قبل الإدخال، انتظر الرمز التالي. تأكد أن ساعة هاتفك صحيحة.</span>
      </div>
    </div>
  </div>

  <!-- Step 5 -->
  <div class="guide-step reveal reveal--d4">
    <div class="step-bubble">٥</div>
    <div class="step-body">
      <div class="step-title"><?= icon('circle-check') ?> تهانينا! تم التفعيل</div>
      <p class="step-desc">في كل مرة تسجل دخولك، سيطلب منك رمز من التطبيق بعد كلمة المرور. لا أحد يستطيع الدخول لحسابك بدون هاتفك.</p>
      <div class="phone-demo">
        <div class="phone-screen" style="text-align:center;padding:20px">
          <div style="font-size:40px;margin-bottom:12px"><?= icon('sparkles') ?></div>
          <div style="font-weight:700;color:var(--ok-fg);margin-bottom:6px">تم تفعيل 2FA بنجاح!</div>
          <div style="font-size:13px;color:var(--tm)">حسابك محمي الآن بطبقة إضافية</div>
          <div class="divider" style="margin:14px 0"></div>
          <div style="font-size:12px;color:var(--tm)">في المرة القادمة ستحتاج:</div>
          <div style="display:flex;flex-direction:column;gap:6px;margin-top:10px;text-align:right">
            <div style="display:flex;align-items:center;gap:8px;font-size:13px"><span>1️⃣</span> كلمة المرور</div>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px"><span>2️⃣</span> رمز 6 أرقام من التطبيق</div>
          </div>
        </div>
      </div>
      <div class="tip-box">
        <span class="tip-icon"><?= icon('lock') ?></span>
        <span><strong>نصيحة:</strong> لا تحذف التطبيق من هاتفك! إذا أردت تغيير الهاتف، اذهب لإعدادات حسابك ← أعد إعداد 2FA قبل تغيير الجهاز.</span>
      </div>
    </div>
  </div>

</div>

<!-- CTA -->
<div class="action-banner reveal">
  <h3>جاهز لتفعيله؟</h3>
  <p>لا يستغرق التفعيل أكثر من دقيقتين</p>
  <a href="<?= BASE_URL ?>/account/2fa/setup" class="btn btn--light btn--lg"><?= icon('lock') ?> فعل 2FA الآن ←</a>
</div>

</div>

<script nonce="<?= csp_nonce() ?>">
// Animate OTP code
const codes = ['482917','831204','572839','104756','938271','647382'];
let ci = 0;
setInterval(() => {
  ci = (ci+1) % codes.length;
  const c = codes[ci];
  document.getElementById('demoCode').textContent = c.slice(0,3)+' '+c.slice(3);
}, 3000);
</script>
