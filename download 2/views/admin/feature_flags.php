<?php
$flags = [
  ['key'=>'maintenance_mode',    'icon'=>'wrench', 'label'=>'وضع الصيانة',               'desc'=>'يوقف الموقع ويظهر صفحة صيانة لكل الزوار غير الأدمن', 'danger'=>true],
  ['key'=>'feature_login',       'icon'=>'key', 'label'=>'تسجيل الدخول',              'desc'=>'السماح للمستخدمين بتسجيل الدخول'],
  ['key'=>'feature_register',    'icon'=>'file-text', 'label'=>'التسجيل',                   'desc'=>'السماح بإنشاء حسابات جديدة'],
  ['key'=>'feature_apply',       'icon'=>'clipboard', 'label'=>'التقديم على الفورمات',      'desc'=>'السماح بتقديم طلبات على الفورمات'],
  ['key'=>'feature_challenges',  'icon'=>'target', 'label'=>'التحديات',                  'desc'=>'إتاحة صفحة التحديات'],
  ['key'=>'feature_news',        'icon'=>'newspaper', 'label'=>'الفعاليات',                   'desc'=>'إتاحة صفحة الفعاليات'],
  ['key'=>'feature_ai_review',   'icon'=>'bot', 'label'=>'مراجعة AI',                 'desc'=>'تفعيل مراجعة الطلبات بالذكاء الاصطناعي'],
];
?>

<div style="display:flex;flex-direction:column;gap:16px">

  <!-- Site Mode Quick Switch -->
  <div class="acard" style="border: 2px solid var(--border)">
    <div class="acard-header"><h3><?= icon('settings') ?> وضع الموقع</h3></div>
    <div class="acard-body">
      <?php $mode = setting('site_mode','open'); ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <button type="button" data-site-mode="open"
                class="btn <?= $mode==='open' ? 'btn--green' : 'btn--outline' ?>"
                style="flex:1;min-width:120px">
          <?= icon('circle-check') ?> مفتوح
        </button>
        <button type="button" data-site-mode="maintenance"
                class="btn <?= $mode==='maintenance' ? 'btn--gold' : 'btn--outline' ?>"
                style="flex:1;min-width:120px">
          <?= icon('wrench') ?> صيانة
        </button>
        <button type="button" data-site-mode="closed"
                class="btn <?= $mode==='closed' ? 'btn--danger' : 'btn--outline' ?>"
                style="flex:1;min-width:120px">
          <?= icon('lock') ?> مغلق
        </button>
      </div>
      <?php if ($mode === 'maintenance'): ?>
      <div style="padding:10px 14px;background:rgba(207,171,70,.1);border:1px solid rgba(207,171,70,.3);border-radius:10px;font-size:13px;color:var(--td)">
        <?= icon('wrench') ?> الموقع حاليا في وضع الصيانة — الأدمن فقط يمكنه الوصول
      </div>
      <?php elseif ($mode === 'closed'): ?>
      <div style="padding:10px 14px;background:rgba(217,64,64,.08);border:1px solid rgba(217,64,64,.2);border-radius:10px;font-size:13px;color:#c0392b">
        <?= icon('lock') ?> الموقع مغلق للعموم
      </div>
      <?php else: ?>
      <div style="padding:10px 14px;background:rgba(110,74,42,.08);border:1px solid rgba(110,74,42,.2);border-radius:10px;font-size:13px;color:var(--sage-dark)">
        <?= icon('circle-check') ?> الموقع مفتوح ويعمل بشكل طبيعي
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Maintenance message -->
  <div class="acard">
    <div class="acard-header"><h3><?= icon('wrench') ?> رسالة الصيانة</h3></div>
    <form method="POST" action="<?= BASE_URL ?>/admin/settings/save" class="acard-body" data-ajax-toast="1">
      <?= csrf_field() ?>
      <div class="form-stack">
        <div class="form-group">
          <textarea name="maintenance_message" class="form-input" rows="3"><?= e(setting('maintenance_message','الموقع تحت الصيانة حاليا، سنعود قريبا.')) ?></textarea>
          <div style="font-size:11px;color:var(--tm);margin-top:4px">تظهر هذه الرسالة في صفحة الصيانة</div>
        </div>
        <button class="btn btn--outline btn--sm" type="submit"><?= icon('save') ?> حفظ الرسالة</button>
      </div>
    </form>
  </div>

  <!-- Feature toggles -->
  <div class="acard">
    <div class="acard-header"><h3><?= icon('sliders') ?> التحكم بخصائص الموقع</h3></div>
    <div style="padding:8px">
      <?php foreach ($flags as $fl):
        $val = (int)setting($fl['key'], $fl['key']==='maintenance_mode'?'0':'1');
        $on  = (bool)$val;
      ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 12px;border-bottom:1px solid var(--border-l);gap:16px">
        <div>
          <div style="font-size:14px;font-weight:700;color:<?= (!empty($fl['danger'])&&$on)?'#c0392b':'var(--td)' ?>"><?= icon($fl['icon'] ?? 'settings') ?> <?= $fl['label'] ?></div>
          <div style="font-size:12px;color:var(--tm);margin-top:2px"><?= $fl['desc'] ?></div>
        </div>
        <button type="button"
                class="btn btn--sm <?= $on ? ((!empty($fl['danger']))?'btn--danger':'btn--green') : 'btn--outline' ?>"
                data-key="<?= $fl['key'] ?>"
                data-on="<?= $on ? '1' : '0' ?>"
                data-feature-toggle
                <?= (!empty($fl['danger'])&&$on)?'style="animation:pulse-btn .8s infinite"':'' ?>>
          <?= $on ? (icon('circle-check') . ' مفعل') : (icon('circle-x') . ' معطل') ?>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Hosting info -->
  <div class="acard">
    <div class="acard-header"><h3><?= icon('globe') ?> بيئة الاستضافة</h3></div>
    <div class="acard-body" style="display:flex;flex-direction:column;gap:10px;font-size:13px">
      <?php
      $rows = [
        ['PHP Version',    PHP_VERSION],
        ['PHP SAPI',       PHP_SAPI],
        ['SQLite',         class_exists('PDO') ? (in_array('sqlite', PDO::getAvailableDrivers()) ? 'متاح' : 'غير متاح') : 'غير متاح'],
        ['GD',             extension_loaded('gd') ? 'متاح' : 'غير متاح'],
        ['cURL',           function_exists('curl_init') ? 'متاح' : 'غير متاح'],
        ['upload_max_filesize', ini_get('upload_max_filesize')],
        ['memory_limit',   ini_get('memory_limit')],
        ['HTTPS',          (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off') ? 'مفعل' : 'غير مفعل'],
        ['Server Time',    date('Y-m-d H:i:s T')],
      ];
      ?>
      <?php foreach ($rows as [$k,$v]): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 10px;background:var(--bg-2);border-radius:var(--r-sm)">
        <span style="color:var(--tm);font-weight:600"><?= $k ?></span>
        <span style="font-family:monospace;color:var(--td)"><?= htmlspecialchars((string)$v) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<style>
@keyframes pulse-btn { 0%,100%{opacity:1} 50%{opacity:.7} }
</style>
<script nonce="<?= csp_nonce() ?>">
var CSRF_FEAT = '<?= csrf_token() ?>', BASE_FEAT = '<?= BASE_URL ?>';

async function toggleFlag(btn) {
  var key = btn.dataset.key;
  var on  = btn.dataset.on === '1';
  var newVal = on ? '0' : '1';
  btn.disabled = true;
  var fd = new FormData();
  fd.append('_csrf', CSRF_FEAT);
  fd.append('key', key);
  fd.append('value', newVal);
  try {
    var res = await fetch(BASE_FEAT + '/admin/feature/toggle', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: fd
    });
    var d = await res.json();
    if (d.success) {
      // Update button state
      btn.dataset.on = newVal;
      var isDanger = key === 'maintenance_mode';
      var nowOn = newVal === '1';
      btn.textContent = nowOn ? 'مفعل' : 'معطل';
      btn.className = 'btn btn--sm ' + (nowOn ? (isDanger ? 'btn--danger' : 'btn--green') : 'btn--outline');
      btn.style.animation = (isDanger && nowOn) ? 'pulse-btn .8s infinite' : '';
      if (typeof showAdminToast === 'function') showAdminToast('تم التغيير', 'ok');
      // Reload to reflect site mode changes in sidebar
      if (key === 'maintenance_mode') setTimeout(function() { location.reload(); }, 800);
    }
  } catch(e) {
    if (typeof showAdminToast === 'function') showAdminToast('خطأ', 'error');
  }
  btn.disabled = false;
}

async function setSiteMode(mode) {
  var fd = new FormData();
  fd.append('_csrf', CSRF_FEAT);
  fd.append('site_mode', mode);
  try {
    var res = await fetch(BASE_FEAT + '/admin/settings/save', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: fd
    });
    var d = await res.json();
    if (typeof showAdminToast === 'function') showAdminToast('تم تغيير وضع الموقع', 'ok');
    setTimeout(function() { location.reload(); }, 800);
  } catch(e) {
    if (typeof showAdminToast === 'function') showAdminToast('خطأ', 'error');
  }
}

document.addEventListener('click', function(e) {
  var flagBtn = e.target.closest('[data-feature-toggle]');
  if (flagBtn) {
    toggleFlag(flagBtn);
    return;
  }
  var modeBtn = e.target.closest('[data-site-mode]');
  if (modeBtn) setSiteMode(modeBtn.dataset.siteMode);
});
</script>
