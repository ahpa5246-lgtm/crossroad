<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<title><?= e($pageTitle??'لوحة التحكم') ?> — <?= e(brand_name()) ?></title>
<link rel="icon" type="image/png" href="<?= brand_icon_url(96) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= @filemtime(ROOT_PATH . '/public/css/style.css') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css?v=<?= @filemtime(ROOT_PATH . '/public/css/admin.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script nonce="<?= csp_nonce() ?>">
(function(){
  var t = 'dark';
  document.documentElement.setAttribute('data-theme', t);
  var s = document.createElement('style');
  s.textContent = (t === 'dark')
    ? '.logo-light{display:none!important}.logo-dark{display:block!important}'
    : '.logo-light{display:block!important}.logo-dark{display:none!important}';
  document.head.appendChild(s);
})();
</script>
</head>
<body class="admin-layout">

<?php
$cur     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$me      = auth();
$isSA    = $me && $me['role'] === 'super_admin';
$pending = (int)Database::getInstance()->query("SELECT COUNT(*) FROM form_submissions WHERE status='pending'")->fetchColumn();

$nav = [
  ['icon'=>'home',  'label'=>'الرئيسية',           'href'=>'/admin',                  'exact'=>true,  'perm'=>null],
  ['icon'=>'pencil',  'label'=>'المحتوى',             'href'=>'/admin/content',          'exact'=>false, 'perm'=>'manage_content'],
  ['icon'=>'layout',  'label'=>'النماذج',             'href'=>'/admin/forms',            'exact'=>false, 'perm'=>'manage_forms'],
  ['icon'=>'clipboard', 'label'=>'الطلبات',             'href'=>'/admin/submissions',      'exact'=>false, 'perm'=>'manage_forms', 'badge'=>$pending],
  ['icon'=>'circle-check', 'label'=>'المقبولون',           'href'=>'/admin/accepted',         'exact'=>false, 'perm'=>'manage_forms'],
  ['icon'=>'target', 'label'=>'التحديات',            'href'=>'/admin/challenges',       'exact'=>false, 'perm'=>'manage_challenges'],
  ['icon'=>'award', 'label'=>'الشهادات',            'href'=>'/admin/certificates',     'exact'=>false, 'perm'=>'manage_certificates'],
  ['icon'=>'award', 'label'=>'شهادات جماعية',      'href'=>'/admin/certs/bulk-form', 'exact'=>false, 'perm'=>'manage_certificates'],
  ['icon'=>'trophy', 'label'=>'الإنجازات', 'href'=>'/admin/achievements', 'exact'=>false, 'perm'=>'manage_content'],
  ['icon'=>'newspaper', 'label'=>'المقالات', 'href'=>'/admin/articles', 'exact'=>false, 'perm'=>'manage_content'],
  ['icon'=>'users', 'label'=>'الإدارة العليا (الفريق)', 'href'=>'/admin/team',            'exact'=>false, 'perm'=>'manage_content'],
  ['icon'=>'users', 'label'=>'المستخدمون',          'href'=>'/admin/users',            'exact'=>false, 'perm'=>'manage_users'],
  ['icon'=>'users', 'label'=>'مستخدمين جماعي',     'href'=>'/admin/users/bulk-form', 'exact'=>false, 'perm'=>'manage_users'],
  ['icon'=>'plus', 'label'=>'مشرف جديد',          'href'=>'/admin/admins/new',       'exact'=>false, 'perm'=>'super_admin_only'],
  ['icon'=>'megaphone', 'label'=>'التبليغات',           'href'=>'/admin/announcements',    'exact'=>false, 'perm'=>null],
  ['icon'=>'sliders', 'label'=>'عناصر التحكم',        'href'=>'/admin/features',         'exact'=>false, 'perm'=>'super_admin_only'],
  ['icon'=>'file-text', 'label'=>'السجلات',             'href'=>'/admin/logs',             'exact'=>false, 'perm'=>null],
  ['icon'=>'settings',  'label'=>'الإعدادات',          'href'=>'/admin/settings',         'exact'=>false, 'perm'=>'manage_content'],
  ['icon'=>'database',  'label'=>'SQL',                'href'=>'/admin/sql',              'exact'=>false, 'perm'=>'super_admin_only'],
];
?>

<div class="admin-wrap">

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <?= brand_mark_html("sb-logo", "sb-name", 32, true) ?>
      <button class="sb-close" id="sidebarClose" type="button" aria-label="إغلاق"><?= icon('x') ?></button>
    </div>

    <nav class="sb-nav" role="navigation">
      <?php foreach ($nav as $item):
        if ($item['perm'] === 'super_admin_only' && !$isSA) continue;
        if ($item['perm'] && $item['perm'] !== 'super_admin_only' && !has_permission($item['perm'])) continue;
        $href    = $item['href'];
        $isExact = $item['exact'] ?? false;
        if ($isExact) {
          $active = ($cur === BASE_URL . $href || $cur === $href) ? ' active' : '';
        } else {
          $active = (str_starts_with($cur, BASE_URL . $href) || str_starts_with($cur, $href)) ? ' active' : '';
        }
      ?>
      <a href="<?= BASE_URL . $href ?>" class="sb-item<?= $active ?>">
        <span class="sb-icon"><?= icon($item['icon'], 18) ?></span>
        <span><?= $item['label'] ?></span>
        <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
          <span class="sb-badge"><?= $item['badge'] ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
      <a href="<?= BASE_URL ?>/" class="sb-item" target="_blank" rel="noopener"><span class="sb-icon"><?= icon('globe') ?></span><span>الموقع</span></a>
      <a href="<?= BASE_URL ?>/auth/logout" class="sb-item sb-item--danger"><span class="sb-icon"><?= icon('log-out') ?></span><span>خروج</span></a>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button type="button" class="sb-toggle" id="sidebarToggle" aria-label="القائمة">
          <span class="toggle-bar"></span>
          <span class="toggle-bar"></span>
          <span class="toggle-bar"></span>
        </button>
        <h1 class="page-title"><?= e($pageTitle??'') ?></h1>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <?php if($s=flash('success')): ?><div class="alert alert--success flash-top"><?= e($s) ?></div><?php endif; ?>
        <?php if($er=flash('error')):  ?><div class="alert alert--error flash-top"><?= e($er) ?></div><?php endif; ?>
        <?php if($ci=flash('cert_issued')): ?>
        <div class="cert-issued-toast" id="certIssuedToast">
          <span><?= icon('circle-check') ?> تم إصدار الشهادة</span>
          <a href="<?= htmlspecialchars($ci) ?>" target="_blank" rel="noopener" class="cert-issued-toast__btn">عرض ←</a>
          <button type="button" data-close-parent=".cert-issued-toast" class="cert-issued-toast__close"><?= icon('x') ?></button>
        </div>
        <?php endif; ?>
        <span class="topbar-user">@<?= e($me['username']??'') ?></span>
      </div>
    </header>

    <main class="admin-content">
      <?= $content ?? '' ?>
    </main>
  </div>

</div>

<div id="adminGlobalToast" style="
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);
  background:#291609;color:#fff0e0;padding:12px 22px;border-radius:4px;
  font-size:12px;font-weight:600;z-index:99999;font-family:var(--fa);
  transition:transform .3s cubic-bezier(.4,0,.2,1),opacity .3s;
  opacity:0;pointer-events:none;white-space:nowrap;
  box-shadow:0 8px 32px rgba(0,0,0,.45);border:1px solid rgba(203,123,69,.3);
" role="status" aria-live="polite"></div>

<script nonce="<?= csp_nonce() ?>" src="<?= BASE_URL ?>/public/js/admin.js"></script>
<script nonce="<?= csp_nonce() ?>">
function applyTheme(t, reload) {
  document.documentElement.setAttribute('data-theme', t);
  try { localStorage.setItem('mftaraq_theme', t); } catch(_) {}
  document.cookie = 'theme=' + t + ';path=/;max-age=31536000';
  if (reload) { location.reload(); return; }
  var isDark = (t === 'dark');
  document.querySelectorAll('.logo-light').forEach(function(el) {
    el.style.setProperty('display', isDark ? 'none'  : 'block', 'important');
  });
  document.querySelectorAll('.logo-dark').forEach(function(el) {
    el.style.setProperty('display', isDark ? 'block' : 'none',  'important');
  });
}
document.addEventListener('DOMContentLoaded', function() {
  applyTheme(document.documentElement.getAttribute('data-theme') || 'light');
});

function showAdminToast(msg, type) {
  var t = document.getElementById('adminGlobalToast');
  if (!t) return;
  t.textContent = msg;
  t.style.background = type === 'error' ? '#3a0a0a' : type === 'warn' ? '#2a1f00' : '#291609';
  t.style.opacity = '1';
  t.style.transform = 'translateX(-50%) translateY(0)';
  clearTimeout(t._tid);
  t._tid = setTimeout(function() {
    t.style.opacity = '0';
    t.style.transform = 'translateX(-50%) translateY(80px)';
  }, 3000);
}

var sidebar  = document.getElementById('sidebar');
var overlay  = document.getElementById('sidebarOverlay');
var btnOpen  = document.getElementById('sidebarToggle');
var btnClose = document.getElementById('sidebarClose');
var wrap     = document.querySelector('.admin-wrap');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }
function toggleDesktop(){ wrap.classList.toggle('sidebar-collapsed'); try { localStorage.setItem('sb_collapsed', wrap.classList.contains('sidebar-collapsed') ? '1' : '0'); } catch(_) {} }

try { if (localStorage.getItem('sb_collapsed') === '1') wrap.classList.add('sidebar-collapsed'); } catch(_) {}

if (btnOpen) {
  btnOpen.addEventListener('click', function(e) {
    e.stopPropagation();
    if (window.innerWidth <= 900) {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    } else { toggleDesktop(); }
  });
}
if (btnClose) btnClose.addEventListener('click', function(e) { e.stopPropagation(); closeSidebar(); });
if (overlay)  overlay.addEventListener('click', closeSidebar);

document.querySelectorAll('.sb-item[href]').forEach(function(a) {
  a.addEventListener('click', function() { if (window.innerWidth <= 900) closeSidebar(); });
});

document.addEventListener('submit', function(e) {
  var form = e.target;
  if (form.dataset.ajaxToast) {
    e.preventDefault();
    var fd = new FormData(form);
    fetch(form.action, { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        showAdminToast(d.message || (d.success ? 'تم الحفظ' : 'فشل'), d.success ? 'ok' : 'error');
        if (d.success && form.dataset.reload) setTimeout(function() { location.reload(); }, 1000);
      })
      .catch(function() { showAdminToast('خطأ في الشبكة', 'error'); });
  }
});
</script>
</body>
</html>
