<style>
/* ── Install page styles ── */
.install-hero { text-align:center; padding: 56px 20px 32px; }
.install-hero h1 { font-family:var(--fd); font-size:clamp(28px,5vw,48px); color:var(--td); margin-bottom:12px; }
.install-hero p  { color:var(--tm); font-size:16px; max-width:520px; margin:0 auto 36px; line-height:1.7; }

/* Device tabs */
.device-tabs { display:flex; justify-content:center; gap:12px; flex-wrap:wrap; margin-bottom:48px; }
.dtab { display:flex; flex-direction:column; align-items:center; gap:8px; padding:18px 24px; border-radius:var(--r-lg);
  border:2px solid var(--border); background:var(--card-bg,#fff); cursor:pointer; transition:all .25s var(--ease);
  font-weight:600; font-size:14px; color:var(--tm); min-width:110px; }
.dtab:hover { border-color:var(--sage-dark); color:var(--sage-dark); transform:translateY(-2px); box-shadow:var(--sh-md); }
.dtab.active { border-color:var(--sage-dark); background:var(--sage-faint); color:var(--sage-deep); box-shadow:var(--sh-sm); }
.dtab-icon { font-size:36px; }
[data-theme="dark"] .dtab { background:var(--card-bg); }
[data-theme="dark"] .dtab.active { background:var(--sage-faint); }

/* Panels */
.device-panel { display:none; animation:fadeIn .35s var(--ease); }
.device-panel.active { display:block; }
@keyframes fadeIn { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

/* Steps */
.steps-wrap { max-width:760px; margin:0 auto; display:flex; flex-direction:column; gap:32px; }
.step-row { display:grid; grid-template-columns:1fr 1fr; gap:28px; align-items:center; }
.step-row:nth-child(even) { direction:ltr; }
.step-row:nth-child(even) .step-text { direction:rtl; }
@media(max-width:640px) {
  .step-row, .step-row:nth-child(even) { grid-template-columns:1fr; direction:rtl; }
}
.step-text { display:flex; flex-direction:column; gap:10px; }
.step-num { display:inline-flex; align-items:center; justify-content:center;
  width:34px; height:34px; border-radius:50%; background:var(--sage-dark); color:#fff;
  font-weight:800; font-size:15px; flex-shrink:0; }
.step-title { font-family:var(--fd); font-size:18px; color:var(--td); display:flex; align-items:center; gap:10px; }
.step-desc { color:var(--tm); line-height:1.75; font-size:15px; }
.step-tip { background:var(--gold-faint); border:1px solid var(--gold-pale); border-radius:var(--r-sm);
  padding:10px 14px; font-size:13px; color:var(--tb); display:flex; gap:8px; align-items:flex-start; }
[data-theme="dark"] .step-tip { background:var(--gold-faint); border-color:var(--gold-pale); }

/* Phone mockups (pure CSS) */
.phone-mock { display:flex; justify-content:center; }
.pmock { width:160px; height:280px; border-radius:28px; background:var(--td); position:relative;
  box-shadow:0 16px 48px rgba(0,0,0,.22); overflow:hidden; border:3px solid #333; }
.pmock-screen { position:absolute; inset:6px; border-radius:22px; background:#f8f8f8; overflow:hidden;
  display:flex; flex-direction:column; }
[data-theme="dark"] .pmock-screen { background:#2f2219; }
.pmock-status { height:22px; background:inherit; display:flex; align-items:center; justify-content:space-between;
  padding:0 10px; font-size:7px; font-weight:700; color:#444; }
.pmock-content { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding:8px; gap:6px; }
.pmock-url  { width:90%; height:18px; background:var(--cream-dk,#edded1); border-radius:6px;
  display:flex; align-items:center; padding:0 6px; font-size:6px; color:#666; }
.pmock-web  { width:90%; flex:1; background:linear-gradient(135deg,var(--sage-faint),var(--gold-faint));
  border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:9px; color:var(--sage-dark);
  font-weight:700; text-align:center; line-height:1.3; }
.pmock-toolbar { height:28px; background:rgba(0,0,0,.04); border-top:1px solid rgba(0,0,0,.08);
  display:flex; align-items:center; justify-content:space-around; padding:0 8px; }
.pmock-btn { font-size:11px; color:#666; }
.pmock-share-hl { background:var(--sage); color:#fff; border-radius:4px; padding:2px 4px; font-size:9px; }

/* Share sheet mockup */
.share-sheet { position:absolute; bottom:0; left:0; right:0; height:65%;
  background:#fff; border-radius:16px 16px 0 0; padding:10px;
  box-shadow:0 -4px 20px rgba(0,0,0,.15); display:flex; flex-direction:column; gap:5px; }
[data-theme="dark"] .share-sheet { background:#2e241e; }
.ss-handle { width:28px; height:3px; background:#ccc; border-radius:2px; margin:0 auto 6px; }
.ss-title { font-size:7px; font-weight:700; color:#333; text-align:center; }
.ss-options { display:flex; flex-direction:column; gap:3px; }
.ss-option { display:flex; align-items:center; gap:5px; padding:4px 6px; border-radius:6px; font-size:7px; }
.ss-option.hl { background:var(--sage-faint); color:var(--sage-deep); font-weight:700; }
.ss-opt-icon { font-size:10px; }

/* Android mockup */
.android-mock .pmock { border:3px solid #2d2d2d; border-radius:24px; }
.android-home { display:grid; grid-template-columns:repeat(4,1fr); gap:4px; padding:6px; }
.app-icon { display:flex; flex-direction:column; align-items:center; gap:2px; }
.app-icon-img { width:22px; height:22px; border-radius:8px; }
.app-icon span { font-size:4.5px; color:#333; }
.app-icon.new .app-icon-img { background:linear-gradient(135deg,var(--sage),var(--gold)); border:1.5px solid var(--gold); }
.app-icon.new span { color:var(--sage-dark); font-weight:700; }

/* Windows mockup */
.win-mock .pmock { width:220px; height:160px; border-radius:12px; border:2px solid #444; }
.win-mock .pmock-screen { border-radius:8px; }
.browser-bar { height:26px; background:#f4f2f1; display:flex; align-items:center; gap:5px;
  padding:0 8px; border-bottom:1px solid #ddd; }
.browser-dot { width:8px; height:8px; border-radius:50%; }
.browser-url { flex:1; height:14px; background:#fff; border-radius:4px; border:1px solid #ddd;
  font-size:6px; display:flex; align-items:center; padding:0 4px; color:#666; }
.browser-content { flex:1; padding:8px; display:flex; align-items:center; justify-content:center;
  font-size:8px; color:var(--sage-dark); font-weight:700; text-align:center; line-height:1.5; }

/* CTA banner */
.install-cta { background:linear-gradient(135deg,var(--sage-dark),var(--sage-deep)); color:#fff;
  border-radius:var(--r-xl); padding:40px 32px; text-align:center; margin:48px auto 0; max-width:640px; }
.install-cta h3 { font-family:var(--fd); font-size:24px; margin-bottom:10px; }
.install-cta p { opacity:.85; margin-bottom:22px; }
</style>

<!-- Hero -->
<section class="install-hero container">
  <h1>استخدم <?= e(brand_name()) ?> كتطبيق</h1>
  <p>يمكنك الوصول إلى <?= e(brand_name()) ?> مباشرة من شاشتك الرئيسية — بدون متجر تطبيقات، بضغطات بسيطة.</p>

  <!-- Device tabs — JS-detected by default, manual override -->
  <div class="device-tabs" id="deviceTabs">
    <button class="dtab" data-device="ios"     onclick="switchDevice('ios')">
      <span class="dtab-icon"><?= icon('phone') ?></span> iPhone / iPad
    </button>
    <button class="dtab" data-device="android" onclick="switchDevice('android')">
      <span class="dtab-icon"><?= icon('bot') ?></span> Android
    </button>
    <button class="dtab" data-device="windows" onclick="switchDevice('windows')">
      <span class="dtab-icon"><?= icon('monitor') ?></span> Windows / Mac
    </button>
  </div>
</section>

<div class="section container">

  <!-- ══ iOS Panel ══════════════════════════════════════════ -->
  <div class="device-panel" id="panel-ios">
    <div class="steps-wrap">

      <div class="step-row">
        <div class="step-text">
          <div class="step-title"><span class="step-num">١</span> افتح Safari وادخل على الموقع</div>
          <p class="step-desc">تأكد أنك تستخدم متصفح <strong>Safari</strong> المدمج في آيفون — لن تجد خيار الإضافة في Chrome أو Firefox.</p>
          <div class="step-tip"><?= icon('bulb') ?> المتصفح الافتراضي في آيفون هو Safari — ابحث عن الأيقونة الزرقاء مع البوصلة</div>
        </div>
        <div class="phone-mock">
          <div class="pmock">
            <div class="pmock-screen">
              <div class="pmock-status"><span>9:41</span><span>▐▌▌ </span></div>
              <div class="pmock-content" style="padding:4px 8px;gap:4px">
                <div class="pmock-url"><?= icon('lock') ?> <?= e($_SERVER['HTTP_HOST'] ?? '') ?></div>
                <div class="pmock-web" style="font-size:8px">
                  <div>
                    <div style="font-size:18px;margin-bottom:4px"></div>
                    <div><?= e(brand_name()) ?></div>
                    <div style="font-size:6px;opacity:.7;margin-top:2px">انضم إلينا</div>
                  </div>
                </div>
              </div>
              <div class="pmock-toolbar">
                <span class="pmock-btn">←</span>
                <span class="pmock-btn">→</span>
                <span class="pmock-share-hl"><?= icon('upload') ?> مشاركة</span>
                <span class="pmock-btn"><?= icon('file-text') ?></span>
                <span class="pmock-btn"><?= icon('home') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-row">
        <div class="phone-mock">
          <div class="pmock">
            <div class="pmock-screen">
              <div class="pmock-status"><span>9:41</span><span>▐▌▌ </span></div>
              <div class="pmock-content" style="padding:0">
                <div class="pmock-web" style="height:40%"><?= e(brand_name()) ?></div>
                <div class="share-sheet">
                  <div class="ss-handle"></div>
                  <div class="ss-title">مشاركة</div>
                  <div style="display:flex;gap:8px;justify-content:center;margin:4px 0">
                    <div style="text-align:center"><div style="font-size:16px"><?= icon('message') ?></div><div style="font-size:5px">رسائل</div></div>
                    <div style="text-align:center"><div style="font-size:16px"><?= icon('mail') ?></div><div style="font-size:5px">بريد</div></div>
                    <div style="text-align:center"><div style="font-size:16px"><?= icon('clipboard') ?></div><div style="font-size:5px">نسخ</div></div>
                  </div>
                  <div class="ss-options">
                    <div class="ss-option hl"><span class="ss-opt-icon"><?= icon('plus') ?></span> إضافة إلى الشاشة الرئيسية</div>
                    <div class="ss-option"><span class="ss-opt-icon"><?= icon('bookmark') ?></span> إضافة إشارة مرجعية</div>
                    <div class="ss-option"><span class="ss-opt-icon"><?= icon('send') ?></span> مزيد من الخيارات</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="step-text">
          <div class="step-title"><span class="step-num">٢</span> اضغط زر المشاركة <?= icon('upload') ?></div>
          <p class="step-desc">في شريط أدوات Safari السفلي، اضغط على أيقونة <strong>المشاركة</strong> (المربع مع السهم للأعلى)، ثم اختر <strong>"إضافة إلى الشاشة الرئيسية"</strong></p>
          <div class="step-tip"><?= icon('pin') ?> قد تحتاج للتمرير لأسفل في قائمة المشاركة لتجد الخيار</div>
        </div>
      </div>

      <div class="step-row">
        <div class="step-text">
          <div class="step-title"><span class="step-num">٣</span> اضغط "إضافة" <?= icon('check') ?></div>
          <p class="step-desc">سيظهر لك اسم التطبيق. يمكنك تعديله إن أردت، ثم اضغط <strong>"إضافة"</strong> في الزاوية اليمنى العليا.</p>
          <div class="step-tip"><?= icon('sparkles') ?> سيظهر أيقونة <?= e(brand_name()) ?> على شاشتك الرئيسية تماما كأي تطبيق!</div>
        </div>
        <div class="phone-mock">
          <div class="pmock">
            <div class="pmock-screen">
              <div class="pmock-status"><span>9:41</span><span>▐▌▌ </span></div>
              <div style="background:#f1f1f1;padding:4px 8px;display:flex;justify-content:space-between;font-size:7px;color:#ff6600;font-weight:700">
                <span>إلغاء</span><span style="color:#000;font-weight:800;font-size:7px">إضافة إلى الشاشة الرئيسية</span><span>إضافة</span>
              </div>
              <div class="pmock-content" style="justify-content:flex-start;padding:12px 8px;gap:12px">
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                  <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#C2410C,#cba233);display:flex;align-items:center;justify-content:center;font-size:20px"></div>
                  <div style="font-size:8px;font-weight:700"><?= e(brand_name()) ?></div>
                </div>
                <div style="width:100%;height:14px;background:#e8e8e8;border-radius:6px;display:flex;align-items:center;padding:0 6px;font-size:7px;color:#666"><?= e($_SERVER['HTTP_HOST'] ?? '') ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /ios -->

  <!-- ══ Android Panel ══════════════════════════════════════ -->
  <div class="device-panel" id="panel-android">
    <div class="steps-wrap">

      <div class="step-row">
        <div class="step-text">
          <div class="step-title"><span class="step-num">١</span> افتح المتصفح وادخل على الموقع</div>
          <p class="step-desc">افتح متصفح Chrome أو أي متصفح آخر على هاتف Android، وادخل على موقع <?= e(brand_name()) ?>.</p>
        </div>
        <div class="phone-mock android-mock">
          <div class="pmock">
            <div class="pmock-screen">
              <div class="pmock-status" style="background:#4285f4;color:#fff"><span style="color:#fff">9:41</span><span style="color:#fff">▐▌▌</span></div>
              <div class="pmock-content" style="padding:4px 8px;gap:4px">
                <div class="pmock-url" style="background:#fff;border:1px solid #ddd">
                  <span style="color:#4285f4;font-size:7px">G</span>&nbsp;<?= icon('lock') ?> <?= e($_SERVER['HTTP_HOST'] ?? '') ?>
                </div>
                <div class="pmock-web"><?= e(brand_name()) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-row">
        <div class="phone-mock android-mock">
          <div class="pmock">
            <div class="pmock-screen">
              <div class="pmock-status" style="background:#333;color:#ddd"><span>9:41</span><span>▐▌▌</span></div>
              <div class="pmock-content" style="padding:0">
                <div class="pmock-web" style="height:50%"><?= e(brand_name()) ?></div>
                <div class="share-sheet" style="height:55%">
                  <div class="ss-handle"></div>
                  <div class="ss-options">
                    <div class="ss-option"><span class="ss-opt-icon"><?= icon('star') ?></span> إضافة إشارة مرجعية</div>
                    <div class="ss-option hl"><span class="ss-opt-icon"><?= icon('phone') ?></span> إضافة إلى الشاشة الرئيسية</div>
                    <div class="ss-option"><span class="ss-opt-icon"><?= icon('printer') ?></span> طباعة</div>
                    <div class="ss-option"><span class="ss-opt-icon"><?= icon('search') ?></span> بحث في الصفحة</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="step-text">
          <div class="step-title"><span class="step-num">٢</span> القائمة ⋮ ← إضافة إلى الشاشة الرئيسية</div>
          <p class="step-desc">اضغط على النقاط الثلاث <strong>⋮</strong> في الزاوية، ثم اختر <strong>"إضافة إلى الشاشة الرئيسية"</strong> أو <strong>"تثبيت التطبيق"</strong>.</p>
          <div class="step-tip"><?= icon('pin') ?> في بعض الأجهزة قد يظهر خيار "تثبيت التطبيق" مباشرة في شريط العنوان</div>
        </div>
      </div>

      <div class="step-row">
        <div class="step-text">
          <div class="step-title"><span class="step-num">٣</span> اضغط "إضافة" وستجده على شاشتك</div>
          <p class="step-desc">سيظهر أيقونة <?= e(brand_name()) ?> على شاشتك الرئيسية مع باقي تطبيقاتك.</p>
          <div class="step-tip"><?= icon('sparkles') ?> لا تحتاج لتحميل APK — التطبيق يعمل مباشرة من المتصفح!</div>
        </div>
        <div class="phone-mock android-mock">
          <div class="pmock">
            <div class="pmock-screen">
              <div class="pmock-status" style="background:#333;color:#ddd"><span>9:41</span><span>▐▌▌</span></div>
              <div style="flex:1;padding:8px">
                <div class="android-home">
                  <?php $appIcons = ['camera','music','phone','settings','map','mail','message','camera','gamepad','bag','tv','dot']; ?>
                  <?php foreach($appIcons as $i => $icon): ?>
                  <div class="app-icon <?= $i===4?'new':'' ?>">
                    <div class="app-icon-img" style="background:<?= ['#ff6b6b','#4ecdc4','#45b7d1','#fed668','','#a8e6cf','#ff8b94','#a29bfe','#fd79a8','#6c5ce7','#00b894','#0984e3'][$i] ?>;display:flex;align-items:center;justify-content:center;font-size:12px"><?= $icon ?></div>
                    <span><?= $i===4?e(brand_name()):'' ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /android -->

  <!-- ══ Windows Panel ════════════════════════════════════ -->
  <div class="device-panel" id="panel-windows">
    <div class="steps-wrap" style="text-align:center">

      <div style="max-width:480px;margin:0 auto">
        <div style="font-size:64px;margin-bottom:20px"><?= icon('monitor') ?></div>
        <h2 style="font-family:var(--fd);font-size:24px;color:var(--td);margin-bottom:14px">على الكمبيوتر — لا تحتاج تثبيت</h2>
        <p style="color:var(--tm);line-height:1.8;margin-bottom:28px">
          <?= e(brand_name()) ?> يعمل مباشرة عبر المتصفح. فقط افتح الرابط وابدأ. يمكنك حفظه كاختصار على سطح المكتب.
        </p>
      </div>

      <div class="win-mock" style="display:flex;justify-content:center;margin:24px 0 32px">
        <div class="pmock" style="width:280px;height:180px;border-radius:10px">
          <div class="pmock-screen" style="border-radius:6px">
            <div class="browser-bar">
              <div class="browser-dot" style="background:#ff5f56"></div>
              <div class="browser-dot" style="background:#ffc72e"></div>
              <div class="browser-dot" style="background:#27c93f"></div>
              <div class="browser-url"><?= icon('lock') ?> <?= e($_SERVER['HTTP_HOST'] ?? '') ?></div>
              <span style="font-size:10px;color:#666"><?= icon('star') ?> ⋮</span>
            </div>
            <div class="browser-content">
              <div>
                <div style="font-size:28px;margin-bottom:6px"></div>
                <div><?= e(brand_name()) ?></div>
                <div style="font-size:6px;opacity:.6;margin-top:3px">انضم إلينا اليوم</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="steps-wrap" style="max-width:560px;margin:0 auto">
        <div class="step-row" style="grid-template-columns:1fr">
          <div class="step-text" style="text-align:right">
            <div class="step-title"><span class="step-num">١</span> افتح أي متصفح</div>
            <p class="step-desc">Chrome, Firefox, Edge, Safari — كلها تعمل. الموقع يعمل على أي نظام تشغيل.</p>
          </div>
        </div>
        <div class="step-row" style="grid-template-columns:1fr">
          <div class="step-text" style="text-align:right">
            <div class="step-title"><span class="step-num">٢</span> احفظه في المفضلة</div>
            <p class="step-desc">اضغط <kbd style="background:var(--cream-dk);padding:2px 8px;border-radius:4px;font-size:13px">Ctrl+D</kbd> أو اضغط على النجمة <?= icon('star') ?> في شريط العنوان لحفظ الموقع في مفضلتك.</p>
          </div>
        </div>
        <div class="step-row" style="grid-template-columns:1fr">
          <div class="step-text" style="text-align:right">
            <div class="step-title"><span class="step-num">٣</span> ابدأ مباشرة</div>
            <p class="step-desc">لا تحتاج تثبيت أي شيء. سجل دخولك وانضم للمجتمع.</p>
            <div style="margin-top:10px"><a href="<?= BASE_URL ?>/auth/register" class="btn btn--gold">إنشاء حساب ←</a></div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /windows -->

  <!-- CTA -->
  <div class="install-cta reveal">
    <h3>جاهز للانطلاق؟</h3>
    <p>سجل حسابك الآن وابدأ رحلتك مع <?= e(brand_name()) ?></p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/auth/register" class="btn btn--light btn--lg">إنشاء حساب مجاني ←</a>
      <a href="<?= BASE_URL ?>/challenges"    class="btn btn--outline-light btn--lg">ابدأ تحديا</a>
    </div>
  </div>

</div>

<script nonce="<?= csp_nonce() ?>">
function switchDevice(d) {
  document.querySelectorAll('.device-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.dtab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + d)?.classList.add('active');
  document.querySelector('[data-device="' + d + '"]')?.classList.add('active');
}

// Auto-detect device on load
(function(){
  var ua = navigator.userAgent || '';
  var d = 'windows';
  if (/iPhone|iPad|iPod/i.test(ua)) d = 'ios';
  else if (/Android/i.test(ua)) d = 'android';
  switchDevice(d);
})();
</script>
