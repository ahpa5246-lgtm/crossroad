<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<meta name="theme-color" content="#391f0e">
<title><?= e($pageTitle ?? setting('site_name')) ?></title>
<!-- Favicons (generated from the admin logo) -->
<link rel="icon" type="image/png" sizes="32x32" href="<?= brand_icon_url(32) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= brand_icon_url(16) ?>">
<link rel="icon" type="image/png" sizes="96x96" href="<?= brand_icon_url(96) ?>">
<!-- PWA Manifest -->
<link rel="manifest" href="<?= BASE_URL ?>/brand/manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<!-- Apple PWA -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(brand_name()) ?>">
<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" sizes="180x180" href="<?= brand_icon_url(180) ?>">
<link rel="apple-touch-icon" sizes="152x152" href="<?= brand_icon_url(152) ?>">
<meta name="description" content="<?= e(setting('hero_subtitle')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= @filemtime(ROOT_PATH . '/public/css/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script nonce="<?= csp_nonce() ?>">window.__BASE_URL__ = '<?= BASE_URL ?>';</script>
<style>
/* ── Content Protection ── */
.protect-content { -webkit-user-select:none;-moz-user-select:none;user-select:none;-webkit-touch-callout:none; }
.protect-content img { pointer-events:none;-webkit-user-drag:none;user-drag:none; }
.cert-protected { -webkit-user-select:none;-moz-user-select:none;user-select:none;-webkit-touch-callout:none; }
.cert-protected img { pointer-events:none;-webkit-user-drag:none; }
</style>
<script nonce="<?= csp_nonce() ?>">
(function(){
  try{
    var t='dark';
    document.documentElement.setAttribute('data-theme',t);
    // Sync logos immediately — before paint — to prevent any flash
    var style=document.createElement('style');
    if(t==='dark'){
      style.textContent='.logo-light{display:none!important}.logo-dark{display:block!important}';
    }else{
      style.textContent='.logo-light{display:block!important}.logo-dark{display:none!important}';
    }
    document.head.appendChild(style);
  }catch(e){}
})();
</script>
<style>
/* ── Nav bell ── */
.nav-bell{position:relative;display:inline-flex;align-items:center;padding:6px 8px;border-radius:4px;text-decoration:none;transition:background .15s;color:var(--text-dark)}
.nav-bell:hover{background:var(--navy-faint)}
.bell-badge{position:absolute;top:2px;right:2px;background:#e74c3c;color:#fff;border-radius:10px;min-width:16px;height:16px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;pointer-events:none}
/* ── Hamburger button ── */
.nav-hamburger{display:none;flex-direction:column;justify-content:center;gap:5px;width:38px;height:38px;padding:7px;background:none;border:none;border-radius:6px;cursor:pointer;transition:background .2s;flex-shrink:0}
.nav-hamburger:hover{background:var(--navy-faint)}
.hbar{display:block;width:100%;height:2px;background:var(--text-dark);border-radius:1px;transition:all .25s}
/* ── Drawer overlay ── */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:900;backdrop-filter:blur(3px);opacity:0;transition:opacity .3s;pointer-events:none}
.drawer-overlay.active{opacity:1;pointer-events:all}
/* ── Mobile drawer ── */
.nav-drawer{position:fixed;top:0;right:-320px;bottom:0;width:300px;max-width:88vw;background:var(--card,#fffdf9);z-index:950;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.2);transition:right .3s cubic-bezier(.4,0,.2,1)}
.nav-drawer.open{right:0}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--border-l)}
.drawer-brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:14px;color:var(--td);font-family:var(--fa)}
.drawer-brand img{height:32px;width:auto}
.drawer-close{background:none;border:none;font-size:22px;color:var(--tm);cursor:pointer;width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:4px}
.drawer-close:hover{background:var(--navy-faint);color:var(--td)}
.drawer-nav{flex:1;overflow-y:auto;padding:10px 8px;display:flex;flex-direction:column;gap:3px}
.drawer-item{display:flex;align-items:center;gap:11px;padding:12px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;color:var(--td);transition:all .2s}
.drawer-item:hover{background:var(--navy-faint);color:var(--navy)}
.drawer-item.active{background:var(--navy-faint);color:var(--navy)}
.drawer-item--danger{color:#c0392b}
.drawer-item--danger:hover{background:#fdf3f3;color:#c0392b}
.drawer-item--btn{background:linear-gradient(135deg,var(--gold),var(--gold-l));color:var(--td)!important;margin-top:4px}
.drawer-item--btn:hover{opacity:.9}
.drawer-sep{height:1px;background:var(--border-l);margin:6px 8px}
.drawer-icon{font-size:17px;width:24px;text-align:center;flex-shrink:0}
.drawer-badge{background:#e74c3c;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:9px;margin-right:auto}
.drawer-foot{padding:12px 8px;border-top:1px solid var(--border-l)}
</style>
</head>
<body>

<!-- ═══ Splash Screen ═══ -->
<div id="splash" style="display:none">
  <div class="sp-inner">
    <?= brand_logo_html('sp-logo', 0, true) ?>
    <div class="sp-name"><?= e(brand_name()) ?></div>
    <button class="sp-skip" id="spSkip" type="button">دخول <?= icon('arrow-left') ?></button>
  </div>
</div>
<style>
#splash{position:fixed;inset:0;z-index:99999;background:#3A1606;display:flex;align-items:center;justify-content:center;transition:opacity .55s ease,visibility .55s ease}
#splash.sp-hide{opacity:0;visibility:hidden;pointer-events:none}
.sp-inner{display:flex;flex-direction:column;align-items:center;gap:20px;animation:sp-up .55s cubic-bezier(.22,1,.36,1) both}
@keyframes sp-up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.sp-logo{max-width:150px;max-height:110px;width:auto;height:auto;object-fit:contain;animation:sp-pop .65s .08s cubic-bezier(.34,1.56,.64,1) both}
@keyframes sp-pop{from{transform:scale(.6);opacity:0}to{transform:scale(1);opacity:1}}
.sp-name{color:#FFC933;font-size:30px;font-weight:900;opacity:0;animation:sp-fade .45s .28s ease forwards;font-family:var(--fa)}
@keyframes sp-fade{to{opacity:1}}
.sp-skip{margin-top:4px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.85);font-size:14px;padding:9px 24px;border-radius:999px;cursor:pointer;opacity:0;animation:sp-fade .4s .9s ease forwards;transition:all .2s;touch-action:manipulation;font-family:var(--fa);display:inline-flex;align-items:center;gap:8px}
.sp-skip:hover{background:rgba(255,255,255,.2);color:#fff}
</style>
<script nonce="<?= csp_nonce() ?>">
function hideSplash(){
  var el=document.getElementById('splash');
  if(!el)return;
  el.classList.add('sp-hide');
  setTimeout(function(){if(el.parentNode)el.parentNode.removeChild(el);},580);
}
(function(){
  var sk=document.getElementById('spSkip'); if(sk) sk.addEventListener('click',hideSplash);
  // Splash only on the home page, once per visit — deep links (account, apply, magazine...) go straight to content.
  var base=(window.__BASE_URL__||'').replace(/\/$/,'');
  var path=location.pathname.replace(/\/index\.php$/,'');
  if(path!==base+'/' && path!==base){ var sp=document.getElementById('splash'); if(sp&&sp.parentNode) sp.parentNode.removeChild(sp); return; }
  try{if(sessionStorage.getItem('sp_v2')){var s0=document.getElementById('splash'); if(s0&&s0.parentNode) s0.parentNode.removeChild(s0); return;}}catch(e){}
  var el=document.getElementById('splash');
  if(!el)return;
  el.style.display='flex';
  try{sessionStorage.setItem('sp_v2','1');}catch(e){}
  var loaded=false;
  var startAt=Date.now();
  function autoHide(){
    if(loaded)return; loaded=true;
    var wait=Math.max(0,1400-(Date.now()-startAt));
    setTimeout(hideSplash,wait);
  }
  if(document.readyState==='complete'){autoHide();}
  else{window.addEventListener('load',autoHide);}
  setTimeout(hideSplash,3500);
})();
</script>

<?php
$authedUser = auth();
$isAdmin    = $authedUser && is_admin();
$_unread    = 0;
if ($authedUser) {
    try {
        $_nStmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
        $_nStmt->execute([(int)$authedUser['id']]);
        $_unread = (int)$_nStmt->fetchColumn();
    } catch(Throwable $_ne) {}
}
?>

<?php if ($authedUser && empty($authedUser['telegram_username']) && !str_starts_with((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), BASE_URL . '/account')): ?>
<div class="tg-banner" id="tgBanner" hidden>
  <div class="tg-inner container">
    <span><?= icon('phone') ?></span>
    <span>أضف معرف التيليغرام لنتواصل معك عند قبول طلبك. <a href="<?= BASE_URL ?>/account?welcome=1#tg-field">أكمل حسابك ←</a></span>
    <button type="button" data-dismiss-banner="tg" aria-label="إغلاق">×</button>
  </div>
</div>
<?php endif; ?>

<!-- Drawer overlay -->
<div class="drawer-overlay" id="drawerOverlay"></div>

<!-- Mobile nav drawer -->
<div class="nav-drawer" id="navDrawer">
  <div class="drawer-head">
    <div class="drawer-brand">
      <?= brand_mark_html("", "brand-name", 32) ?>
    </div>
    <button class="drawer-close" id="drawerClose"><?= icon('x') ?></button>
  </div>

  <nav class="drawer-nav">
    <?php foreach (nav_items() as $_ni): ?>
    <a href="<?= e(nav_href($_ni['url'])) ?>" class="drawer-item"><span class="drawer-icon"><?= icon('compass') ?></span><?= e($_ni['label']) ?></a>
    <?php endforeach; ?>

    <?php if ($authedUser): ?>
      <div class="drawer-sep"></div>
      <?php if ($isAdmin): ?>
      <a href="<?= BASE_URL ?>/admin" class="drawer-item"><span class="drawer-icon"><?= icon('settings') ?></span>لوحة الإدارة</a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/account/notifications" class="drawer-item">
        <span class="drawer-icon"><?= icon('bell') ?></span>الإشعارات
        <?php if ($_unread > 0): ?><span class="drawer-badge"><?= min($_unread,99) ?></span><?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>/account"              class="drawer-item"><span class="drawer-icon"><?= icon('user') ?></span>حسابي</a>
      <a href="<?= BASE_URL ?>/account/certificates" class="drawer-item"><span class="drawer-icon"><?= icon('award') ?></span>شهاداتي</a>
      <div class="drawer-sep"></div>
      <a href="<?= BASE_URL ?>/auth/logout" class="drawer-item drawer-item--danger"><span class="drawer-icon"><?= icon('log-out') ?></span>تسجيل الخروج</a>
    <?php else: ?>
      <div class="drawer-sep"></div>
      <a href="<?= BASE_URL ?>/auth/login"    class="drawer-item"><span class="drawer-icon"><?= icon('key') ?></span>تسجيل الدخول</a>
      <a href="<?= BASE_URL ?>/auth/register" class="drawer-item drawer-item--btn"><span class="drawer-icon"><?= icon('sparkles') ?></span>انضم إلينا</a>
    <?php endif; ?>
  </nav>
</div>

<!-- Top Navbar -->
<nav class="nav" id="navbar">
  <div class="nav-wrap">
    <div class="nav-inner">
      <a href="<?= BASE_URL ?>/" class="nav-compass" aria-label="<?= e(brand_name()) ?>"><?= icon('compass', 28) ?></a>
      <a href="<?= BASE_URL ?>/" class="nav-mbrand" aria-label="<?= e(brand_name()) ?>"><?= brand_has_logo() ? brand_logo_html('nav-logo') : '' ?></a>

      <div class="nav-links" id="navLinks">
        <?php foreach (nav_items() as $_ni): ?>
          <a href="<?= e(nav_href($_ni['url'])) ?>" class="nav-link<?= nav_is_active($_ni['url']) ? ' is-active' : '' ?>"><?= e($_ni['label']) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="nav-tools">
        <?php if ($authedUser): ?>
          <?php if ($isAdmin): ?><a href="<?= BASE_URL ?>/admin" class="nav-tool" title="لوحة التحكم"><?= icon('settings') ?></a><?php endif; ?>
          <a href="<?= BASE_URL ?>/account/notifications" class="nav-tool nav-bell" title="الإشعارات"><?= icon('bell') ?><?php if($_unread>0): ?><span class="bell-badge"><?= min($_unread,99) ?></span><?php endif; ?></a>
          <a href="<?= BASE_URL ?>/account" class="nav-tool" title="حسابي"><?= icon('user') ?></a>
          <a href="<?= BASE_URL ?>/auth/logout" class="nav-tool" title="خروج"><?= icon('log-out') ?></a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/auth/login" class="nav-tool" title="دخول"><?= icon('key') ?></a>
        <?php endif; ?>
        <button class="nav-hamburger" id="navHamburger" aria-label="القائمة"><span class="hbar"></span><span class="hbar"></span><span class="hbar"></span></button>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/" class="nav-emblem" aria-label="<?= e(brand_name()) ?>">
      <?php if (brand_has_logo()): ?><?= brand_logo_html('emblem-img') ?><?php else: ?><?= brand_emblem_svg(96) ?><?php endif; ?>
    </a>
  </div>
</nav>

<?php if ($msg=flash('success')): ?>
<div class="flash flash--success" role="alert"><span><?= icon('check') ?></span> <?= e($msg) ?> <button class="flash-close">×</button></div>
<?php endif; ?>
<?php if ($msg=flash('error')): ?>
<div class="flash flash--error" role="alert"><span><?= icon('x') ?></span> <?= e($msg) ?> <button class="flash-close">×</button></div>
<?php endif; ?>

<main><?= $content ?></main>

<!-- Dock (tablet + phone) -->
<nav class="dock" aria-label="تنقل سريع">
  <?php foreach (dock_items() as $_di): ?>
    <a href="<?= e(nav_href($_di['url'])) ?>" class="dock-item<?= nav_is_active($_di['url']) ? ' is-active' : '' ?>">
      <span class="dock-ic"><?= icon(nav_icon($_di['url'])) ?></span><span class="dock-lb"><?= e($_di['label']) ?></span>
    </a>
  <?php endforeach; ?>
  <button class="dock-item" id="mnavMore" type="button"><span class="dock-ic"><?= icon('menu') ?></span><span class="dock-lb">المزيد</span></button>
</nav>

<footer class="footer" id="contact">
  <div class="container footer-bar">
    <div class="footer-copy"><?= e(setting('footer_text')) ?> · <a href="<?= BASE_URL ?>/certificates/verify">التحقق من الشهادات</a> · <a href="<?= BASE_URL ?>/install">تثبيت التطبيق</a><?php if ($ce = setting('contact_email')): ?> · <a href="mailto:<?= e($ce) ?>"><?= e($ce) ?></a><?php endif; ?></div>
    <div class="footer-social">
      <?php if ($ig=setting('social_instagram')): ?><a href="<?= e($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
      <?php if ($li=setting('social_linkedin')): ?><a href="<?= e($li) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><?php endif; ?>
      <?php if ($tg=contact_links()['telegram']): ?><a href="<?= e($tg) ?>" target="_blank" rel="noopener" aria-label="Telegram"><i class="fab fa-telegram"></i></a><?php endif; ?>
    </div>
  </div>
</footer>

<script nonce="<?= csp_nonce() ?>" src="<?= BASE_URL ?>/public/js/main.js?v=<?= @filemtime(ROOT_PATH . '/public/js/main.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.logo-light').forEach(function(el) { el.style.setProperty('display', 'none', 'important'); });
  document.querySelectorAll('.logo-dark').forEach(function(el) { el.style.setProperty('display', 'block', 'important'); });
});

var drawer  = document.getElementById('navDrawer');
var overlay = document.getElementById('drawerOverlay');
function openDrawer() { drawer.classList.add('open'); overlay.classList.add('active'); }
function closeDrawer() { drawer.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

var mnavMore = document.getElementById('mnavMore');
if (mnavMore) mnavMore.addEventListener('click', openDrawer);
var btnHam   = document.getElementById('navHamburger');
var btnClose = document.getElementById('drawerClose');
if (btnHam)   btnHam.addEventListener('click', openDrawer);
if (btnClose) btnClose.addEventListener('click', closeDrawer);
if (overlay)  overlay.addEventListener('click', closeDrawer);
document.querySelectorAll('.drawer-item[href]').forEach(function(a) { a.addEventListener('click', closeDrawer); });

var cur = location.pathname;
document.querySelectorAll('.drawer-item[href], .mnav-item[href]').forEach(function(a) {
  var p = a.getAttribute('href').replace(/^https?:\/\/[^/]+/, '');
  if (cur === p || (p.length > 2 && cur.startsWith(p))) a.classList.add('active');
});

function markRead() {
  fetch('<?= BASE_URL ?>/account/notifications/read', { method: 'POST', headers: { 'X-CSRF-Token': '<?= csrf_token() ?>' } });
}
</script>

<div id="pageLoader" style="position:fixed;inset:0;background:var(--bg,#fff9f0);display:flex;align-items:center;justify-content:center;z-index:9999;transition:opacity .4s;opacity:0;pointer-events:none">
  <div style="text-align:center">
    <div style="width:44px;height:44px;border:3px solid var(--border,#f1d9bc);border-top-color:var(--navy,#EA580C);border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 12px"></div>
    <div style="font-size:11px;color:var(--tm,#926648);font-family:var(--fa)">جاري التحميل...</div>
  </div>
</div>
<style>
@keyframes spin{to{transform:rotate(360deg)}}
#pageLoader.visible{opacity:1;pointer-events:all}
</style>
<script nonce="<?= csp_nonce() ?>">
document.querySelectorAll('a[href]').forEach(function(a) {
  var href = a.getAttribute('href');
  if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || a.target === '_blank') return;
  a.addEventListener('click', function(e) {
    if (e.metaKey || e.ctrlKey || e.shiftKey) return;
    document.getElementById('pageLoader').classList.add('visible');
  });
});
window.addEventListener('pageshow', function() { document.getElementById('pageLoader').classList.remove('visible'); });
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('<?= BASE_URL ?>/public/sw.js', { scope: '/' }).catch(function(e) { console.warn('[SW]', e); });
  });
}
</script>
</body>
</html>
