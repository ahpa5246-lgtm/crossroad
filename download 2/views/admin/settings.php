<div class="two-col">
  <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Dual Logo Upload -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('image') ?> شعار الموقع — Light &amp; Dark</h3></div>
      <div class="acard-body">
        <?php
        $logoLight = setting('site_logo','');
        $logoDark  = setting('site_logo_dark','');
        ?>
        <!-- Current logos preview -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">

          <!-- Light logo -->
          <div style="border-radius:var(--r-sm);border:2px dashed var(--border);overflow:hidden">
            <div style="background:#fff9f0;padding:14px;text-align:center;min-height:80px;display:flex;align-items:center;justify-content:center;position:relative">
              <?php if ($logoLight): ?>
                <img src="<?= e($logoLight) ?>" alt="Light Logo" style="max-height:56px;max-width:100%;object-fit:contain" id="previewLight">
                <button type="button" data-logo-remove="light" title="حذف" style="position:absolute;top:4px;left:4px;background:#e74c3c;color:#fff;border:none;border-radius:3px;width:20px;height:20px;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center"><?= icon('x') ?></button>
              <?php else: ?>
                <span style="font-size:11px;color:#888" id="previewLight">شعار الوضع الفاتح</span>
              <?php endif; ?>
            </div>
            <div style="padding:10px;background:var(--bg-2)">
              <div style="font-size:10px;font-weight:700;color:var(--td);margin-bottom:6px;font-family:var(--fa)"><?= icon('sun') ?> LIGHT MODE</div>
              <input type="file" id="logoFileLight" accept="image/png,image/jpeg,image/webp" style="font-size:11px;width:100%">
              <button type="button" data-logo-upload="light" class="btn btn--gold btn--sm" id="btnLight" style="width:100%;margin-top:8px;justify-content:center"><?= icon('send') ?> رفع</button>
            </div>
          </div>

          <!-- Dark logo -->
          <div style="border-radius:var(--r-sm);border:2px dashed var(--border);overflow:hidden">
            <div style="background:#291609;padding:14px;text-align:center;min-height:80px;display:flex;align-items:center;justify-content:center;position:relative">
              <?php if ($logoDark): ?>
                <img src="<?= e($logoDark) ?>" alt="Dark Logo" style="max-height:56px;max-width:100%;object-fit:contain" id="previewDark">
                <button type="button" data-logo-remove="dark" title="حذف" style="position:absolute;top:4px;left:4px;background:#e74c3c;color:#fff;border:none;border-radius:3px;width:20px;height:20px;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center"><?= icon('x') ?></button>
              <?php else: ?>
                <span style="font-size:11px;color:#aaa" id="previewDark">شعار الوضع الداكن</span>
              <?php endif; ?>
            </div>
            <div style="padding:10px;background:var(--bg-2)">
              <div style="font-size:10px;font-weight:700;color:var(--td);margin-bottom:6px;font-family:var(--fa)"><?= icon('moon') ?> DARK MODE</div>
              <input type="file" id="logoFileDark" accept="image/png,image/jpeg,image/webp" style="font-size:11px;width:100%">
              <button type="button" data-logo-upload="dark" class="btn btn--green btn--sm" id="btnDark" style="width:100%;margin-top:8px;justify-content:center"><?= icon('send') ?> رفع</button>
            </div>
          </div>
        </div>

        <div id="logoStatus" style="display:none;padding:10px 14px;border-radius:var(--r-sm);font-size:13px;margin-top:8px"></div>
        <p style="font-size:11px;color:var(--tm);margin:8px 0 0"><?= icon('bulb') ?> PNG شفاف منصح. يظهر في النافبار، الفوتر، شاشة الترحيب، والمصادقة. لا يعرض نص الاسم جانب الشعار.</p>
      </div>
    </div>

    <!-- .env notice -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('key') ?> المفاتيح والإعدادات الحساسة</h3></div>
      <div class="acard-body">
        <div style="background:rgba(110,74,42,.07);border:1px solid rgba(110,74,42,.2);border-radius:var(--r-sm);padding:14px 16px;font-size:13px;line-height:1.8">
          <p style="margin:0 0 10px;font-weight:700;color:var(--sage-dark)"><?= icon('alert') ?> جميع المفاتيح الحساسة تدار الآن عبر ملف <code style="background:rgba(0,0,0,.06);padding:2px 6px;border-radius:4px">.env</code> فقط.</p>
          <p style="margin:0 0 8px">المتغيرات المتاحة في <code>.env</code>:</p>
          <div class="code-block" style="margin-bottom:10px"><pre style="margin:0;font-size:12px">SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your@gmail.com
SMTP_PASS=xxxx-xxxx-xxxx-xxxx
SMTP_FROM_NAME="مفترق الطرق"

RECAPTCHA_SITE_KEY=6Le...
RECAPTCHA_SECRET_KEY=6Le...

OLLAMA_API_KEY=your_ollama_cloud_key
OLLAMA_MODEL=llama3.2
OLLAMA_ENDPOINT=https://api.ollama.com</pre></div>
          <p style="margin:0;color:var(--tm)">لا حاجة للحفظ — القراءة تتم مباشرة من الملف عند كل طلب.</p>
        </div>

        <!-- Runtime status of .env keys -->
        <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
          <?php
          $smtpOk     = !empty(Config::get('SMTP_USER')) && !empty(Config::get('SMTP_PASS'));
          $captchaOk  = !empty(Config::get('RECAPTCHA_SITE_KEY'));
          $ollamaKey  = !empty(Config::get('OLLAMA_API_KEY'));
          $ollamaEp   = Config::get('OLLAMA_ENDPOINT') ?: 'غير محدد';
          $ollamaModel= Config::get('OLLAMA_MODEL') ?: setting('ollama_model','llama3.2');
          ?>
          <?php foreach ([
            ['SMTP',       $smtpOk,    Config::get('SMTP_USER','—')],
            ['reCAPTCHA',  $captchaOk, $captchaOk ? '...'.substr(Config::get('RECAPTCHA_SITE_KEY'),- 6) : 'غير مضبوط'],
            ['Ollama Key', $ollamaKey, $ollamaKey ? '...'.substr(Config::get('OLLAMA_API_KEY'),-8) : 'غير مضبوط (سيستخدم المحلي)'],
          ] as [$label, $ok, $hint]): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;background:var(--bg-2);border-radius:var(--r-sm);font-size:13px">
            <span><?= $ok ? (icon('circle-check')) : (icon('circle')) ?> <strong><?= $label ?></strong></span>
            <span style="font-family:monospace;font-size:11px;color:var(--tm)"><?= e($hint) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- General Settings -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('settings') ?> إعدادات عامة</h3></div>
      <form action="<?= BASE_URL ?>/admin/settings/save" method="POST" class="acard-body">
        <?= csrf_field() ?>
        <div class="form-stack">
          <div class="form-group"><label class="form-label">اسم الموقع</label><input type="text" name="site_name" class="form-input" value="<?= e($settings['site_name']??'') ?>"></div>
          <div class="form-group"><label class="form-label">البريد التواصلي</label><input type="email" name="contact_email" class="form-input" value="<?= e($settings['contact_email']??'') ?>"></div>
          <div class="form-group"><label class="form-label">نص الفوتر</label><input type="text" name="footer_text" class="form-input" value="<?= e($settings['footer_text']??'') ?>"></div>
          <button class="btn btn--gold"><?= icon('save') ?> حفظ</button>
        </div>
      </form>
    </div>

    <!-- Email OTP toggles -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('mail') ?> إعدادات OTP البريد</h3></div>
      <form action="<?= BASE_URL ?>/admin/settings/save" method="POST" class="acard-body">
        <?= csrf_field() ?>
        <div class="form-stack">
          <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            <label class="check-label">
              <input type="checkbox" name="email_otp_register" value="1" <?= setting('email_otp_register','1')==='1'?'checked':'' ?>>
              <span>OTP عند التسجيل</span>
            </label>
            <label class="check-label">
              <input type="checkbox" name="email_otp_reset" value="1" <?= setting('email_otp_reset','1')==='1'?'checked':'' ?>>
              <span>OTP لاستعادة كلمة المرور</span>
            </label>
          </div>
          <p style="font-size:12px;color:var(--tm);margin:0">
            ملاحظة: OTP لتغيير كلمة المرور والبريد وحذف الحساب والـ 2FA الإيميل — مفعل دائما ولا يمكن تعطيله.
          </p>
          <button class="btn btn--gold btn--sm"><?= icon('save') ?> حفظ</button>
        </div>
      </form>
    </div>

    <!-- Test SMTP -->
    <div class="acard">
      <div class="acard-header">
        <h3><?= icon('flask') ?> اختبار إرسال البريد</h3>
        <a href="<?= BASE_URL ?>/admin/settings/test-smtp" class="btn btn--outline btn--sm" data-test-smtp><?= icon('flask') ?> اختبار الإرسال</a>
      </div>
      <div class="acard-body">
        <p style="font-size:13px;color:var(--tm)">سيرسل بريد اختباري إلى <strong><?= e(Config::get('SMTP_USER', setting('smtp_user','—'))) ?></strong>.</p>
        <div id="smtpTestResult" style="display:none;padding:10px;border-radius:var(--r-sm);font-size:13px;margin-top:10px"></div>
      </div>
    </div>

  </div>

  <!-- Right column -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Status cards -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('chart') ?> حالة الخدمات</h3></div>
      <div class="acard-body" style="display:flex;flex-direction:column;gap:10px">

        <?php
        $ep    = Config::get('OLLAMA_ENDPOINT') ?: setting('ollama_endpoint','http://localhost:11434');
        $model = Config::get('OLLAMA_MODEL')    ?: setting('ollama_model','llama3.2');
        $rk    = Config::get('RECAPTCHA_SITE_KEY') ?: ($settings['recaptcha_site_key'] ?? '');
        $apiKey= Config::get('OLLAMA_API_KEY') ?: '';
        ?>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--bg-2);border-radius:var(--r-sm);font-size:13px">
          <div style="display:flex;align-items:center;gap:8px">
            <span><?= icon('bot') ?></span>
            <div>
              <div style="font-weight:700">Ollama AI <?= $apiKey ? (icon('cloud') . ' Cloud') : (icon('home') . ' Local') ?></div>
              <div style="font-size:11px;color:var(--tm);font-family:monospace"><?= e($ep) ?> · <?= e($model) ?></div>
            </div>
          </div>
          <span id="ollamaStatus" style="font-size:11px;color:var(--tm)">—</span>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--bg-2);border-radius:var(--r-sm);font-size:13px">
          <div style="display:flex;align-items:center;gap:8px">
            <span><?= $rk ? (icon('circle-check')) : (icon('circle-x')) ?></span>
            <div>
              <div style="font-weight:700">reCAPTCHA</div>
              <?php if($rk): ?><div style="font-size:11px;color:var(--tm);font-family:monospace">...<?= substr($rk,-6) ?></div><?php endif; ?>
            </div>
          </div>
          <span style="font-size:11px;color:<?= $rk ? 'var(--sage-dark)' : '#c0392b' ?>"><?= $rk ? 'مفعل' : 'غير مفعل' ?></span>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--bg-2);border-radius:var(--r-sm);font-size:13px">
          <div style="display:flex;align-items:center;gap:8px">
            <span><?= ($smtpOk??false) ? (icon('circle-check')) : (icon('circle-x')) ?></span>
            <div>
              <div style="font-weight:700">SMTP</div>
              <div style="font-size:11px;color:var(--tm);font-family:monospace"><?= e(Config::get('SMTP_HOST',setting('smtp_host','—'))) ?></div>
            </div>
          </div>
          <span style="font-size:11px;color:<?= ($smtpOk??false) ? 'var(--sage-dark)' : '#c0392b' ?>"><?= ($smtpOk??false) ? 'مضبوط' : 'غير مضبوط' ?></span>
        </div>

      </div>
    </div>

    <!-- DB info -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('database') ?> قاعدة البيانات</h3></div>
      <div class="info-block">
        <?php $dbPath=ROOT_PATH.'/'.Config::get('DB_PATH','database/muntalaq.sqlite'); $dbSize=is_file($dbPath)?round(filesize($dbPath)/1024,1).'KB':'غير موجود'; ?>
        <div class="status-ok"><?= icon('circle-check') ?> SQLite · الحجم: <strong><?= $dbSize ?></strong></div>
        <p style="font-size:12px;color:var(--tm);margin-top:8px">المسار: <code style="font-size:11px"><?= e($dbPath) ?></code></p>
      </div>
    </div>

    <!-- Storage -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('image') ?> التخزين</h3></div>
      <div class="info-block">
        <?php
        $uploadDir=ROOT_PATH.'/public/uploads/'; $totalSize=0;
        if(is_dir($uploadDir)){$iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));foreach($iter as $f){if($f->isFile())$totalSize+=$f->getSize();}}
        $totalMB=round($totalSize/1024/1024,2);
        ?>
        <div class="status-ok"><?= icon('circle-check') ?> محلي · الحجم: <strong><?= $totalMB ?> MB</strong></div>
        <p style="font-size:12px;color:var(--tm);margin-top:8px">الحد الأقصى: <strong><?= Config::get('UPLOAD_MAX_MB','3') ?> MB</strong></p>
      </div>
    </div>

    <!-- Ollama test -->
    <div class="acard">
      <div class="acard-header">
        <h3><?= icon('plug') ?> اختبار Ollama</h3>
        <button type="button" class="btn btn--outline btn--sm" data-test-ollama>اختبار الاتصال</button>
      </div>
      <div class="acard-body">
        <div id="ollamaTestResult" style="display:none"></div>
        <p style="font-size:12px;color:var(--tm);margin:0">
          <?= $apiKey ? (icon('cloud') . ' وضع السحابة — يستخدم OLLAMA_API_KEY من .env') : (icon('home') . ' وضع محلي — الخادم يعمل على ') . e($ep) ?>
        </p>
      </div>
    </div>

  </div>
</div>

<script nonce="<?= csp_nonce() ?>">
function testOllama() {
  var res = document.getElementById('ollamaTestResult');
  var sta = document.getElementById('ollamaStatus');
  res.style.display = 'block';
  res.innerHTML = '<div style="padding:10px;background:var(--bg-2);border-radius:8px;font-size:13px">جاري الاتصال...</div>';
  if (sta) sta.textContent = 'جاري...';
  fetch('<?= BASE_URL ?>/admin/ai/ping', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: '_csrf=<?= csrf_token() ?>'
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.ok) {
      res.innerHTML = '<div style="padding:10px;background:var(--ok-bg);border:1px solid var(--ok-br);border-radius:4px;font-size:13px;color:var(--ok-fg)">' + d.message + '</div>';
      if (sta) { sta.textContent = 'متصل'; sta.style.color = 'var(--sage-dark)'; }
    } else {
      res.innerHTML = '<div style="padding:10px;background:#fdf3f3;border:1px solid #f5c6cb;border-radius:8px;font-size:13px;color:#c0392b">' + (d.error||'خطأ غير معروف') + '</div>';
      if (sta) { sta.textContent = 'غير متصل'; sta.style.color = '#c0392b'; }
    }
  })
  .catch(function(e) {
    res.innerHTML = '<div style="padding:10px;background:#fdf3f3;border-radius:8px;font-size:13px;color:#c0392b">فشل الطلب: ' + e.message + '</div>';
  });
}

window.addEventListener('load', function() {
  var sta = document.getElementById('ollamaStatus');
  if (!sta) return;
  fetch('<?= BASE_URL ?>/admin/ai/ping', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: '_csrf=<?= csrf_token() ?>'
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.ok) { sta.textContent = 'متصل'; sta.style.color = 'var(--sage-dark)'; }
    else      { sta.textContent = 'غير متصل'; sta.style.color = '#c0392b'; }
  })
  .catch(function() { sta.textContent = 'تعذر'; sta.style.color = '#d39b00'; });
});

document.addEventListener('click', function(e) {
  var smtpBtn = e.target.closest('[data-test-smtp]');
  if (smtpBtn) {
    testSmtp(e);
    return;
  }
  var ollamaBtn = e.target.closest('[data-test-ollama]');
  if (ollamaBtn) testOllama();
});

async function testSmtp(e) {
  e.preventDefault();
  var btn = e.target;
  var resultEl = document.getElementById('smtpTestResult');
  btn.disabled = true; btn.textContent = 'جاري الاختبار...';
  resultEl.style.display = 'none';
  var fd = new FormData();
  fd.append('_csrf', '<?= csrf_token() ?>');
  try {
    var res = await fetch('<?= BASE_URL ?>/admin/settings/test-smtp', { method:'POST', body:fd });
    var d = await res.json();
    resultEl.style.display = 'block';
    resultEl.style.background = d.success ? 'rgba(110,74,42,.1)' : 'rgba(217,64,64,.08)';
    resultEl.style.border = d.success ? '1px solid rgba(110,74,42,.3)' : '1px solid rgba(217,64,64,.3)';
    resultEl.style.color = d.success ? 'var(--sage-dark)' : '#c0392b';
    resultEl.textContent = d.message;
  } catch(err) {
    resultEl.style.display = 'block';
    resultEl.textContent = 'خطأ: ' + err.message;
  }
  btn.disabled = false; btn.textContent = 'اختبار الإرسال';
}
</script>

<script nonce="<?= csp_nonce() ?>">
function uploadLogo(slot) {
  var inputId = slot === 'dark' ? 'logoFileDark' : 'logoFileLight';
  var btnId   = slot === 'dark' ? 'btnDark'      : 'btnLight';
  var input   = document.getElementById(inputId);
  var btn     = document.getElementById(btnId);
  var status  = document.getElementById('logoStatus');
  if (!input.files || !input.files[0]) {
    showLogoStatus('اختر ملفا أولا.','err'); return;
  }
  var fd = new FormData();
  fd.append('logo', input.files[0]);
  fd.append('slot', slot);
  fd.append('_csrf', '<?= csrf_token() ?>');
  btn.disabled = true; btn.textContent = 'جاري...';
  fetch('<?= BASE_URL ?>/admin/settings/logo', {
    method:'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body:fd
  })
    .then(r => r.json())
    .then(d => {
      btn.disabled = false; btn.textContent = 'رفع';
      if (d.ok) { showLogoStatus('تم رفع الشعار — جاري التحديث...','ok'); setTimeout(()=>location.reload(),1000); }
      else showLogoStatus(''+(d.error||'فشل'),'err');
    })
    .catch(()=>{ btn.disabled=false; btn.textContent='رفع'; showLogoStatus('خطأ في الشبكة','err'); });
}

async function removeLogo(slot) {
  const ok = await (window.adminConfirm ? window.adminConfirm('حذف الشعار؟', {
    title: 'حذف الشعار',
    confirmText: 'حذف',
    danger: true
  }) : Promise.resolve(confirm('حذف الشعار؟')));
  if (!ok) return;
  var fd = new FormData();
  fd.append('slot', slot);
  fd.append('_csrf', '<?= csrf_token() ?>');
  fetch('<?= BASE_URL ?>/admin/settings/logo/remove', {
    method:'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body:fd
  })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else throw new Error('x'); })
    .catch(()=> {
      if (window.adminAlert) window.adminAlert('فشل حذف الشعار.', { title: 'خطأ' });
      else alert('فشل.');
    });
}

function showLogoStatus(msg, type) {
  var s = document.getElementById('logoStatus');
  s.style.display='block';
  s.style.background = type==='ok' ? 'var(--ok-bg)' : 'var(--err-bg)';
  s.style.color      = type==='ok' ? 'var(--ok-fg)' : 'var(--err-fg)';
  s.style.border     = '1px solid ' + (type==='ok' ? 'var(--ok-br)' : 'var(--err-br)');
  s.textContent = msg;
}

document.addEventListener('click', function(e) {
  var uploadBtn = e.target.closest('[data-logo-upload]');
  if (uploadBtn) {
    uploadLogo(uploadBtn.dataset.logoUpload);
    return;
  }
  var removeBtn = e.target.closest('[data-logo-remove]');
  if (removeBtn) removeLogo(removeBtn.dataset.logoRemove);
});
</script>
