<?php
// Standalone error page template
// Usage: require with $code, $title, $msg defined
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $code ?> — <?= e(brand_name()) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--green:#C2410C;--gold:#cfab46;--bg:#fff0dc}
@media(prefers-color-scheme:dark){:root{--bg:#1a2010;--green:#bc814d;--gold:#d8c286}body{color:#DDD}.w{background:#332417}}
body{background:var(--bg);font-family:'Segoe UI',Tahoma,Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:24px;direction:rtl}
.w{background:#fff;max-width:480px;width:100%;padding:52px 40px;border-radius:24px;box-shadow:0 8px 40px rgba(0,0,0,.1)}
.code{font-size:88px;font-weight:900;color:var(--green);line-height:1;margin-bottom:8px;font-family:monospace}
.icon{font-size:52px;margin-bottom:16px}
h1{font-size:22px;font-weight:700;color:#342314;margin-bottom:10px}
p{font-size:15px;color:#8f7258;margin-bottom:28px;line-height:1.6}
.btn{display:inline-block;padding:13px 32px;background:linear-gradient(135deg,var(--green),#582b0d);color:#fff;border-radius:50px;font-weight:700;text-decoration:none;font-size:15px;transition:opacity .2s}
.btn:hover{opacity:.88}
.alt{margin-top:14px;font-size:13px;color:#999}
.alt a{color:var(--green)}
@media(max-width:480px){.w{padding:36px 24px}.code{font-size:64px}}
</style>
</head>
<body>
<div class="w">
  <div class="icon"><?= $icon ?></div>
  <div class="code"><?= $code ?></div>
  <h1><?= $title ?></h1>
  <p><?= $msg ?></p>
  <a href="<?= $base ?>/" class="btn">← العودة للرئيسية</a>
  <div class="alt"><a href="javascript:history.back()">العودة للصفحة السابقة</a></div>
</div>
</body>
</html>
