<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>الموقع تحت الصيانة</title>
<style>
:root{--navy:#C2410C;--gold:#bf9627}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:linear-gradient(135deg,#391b07,#391f0e);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{background:#fff;border-radius:24px;padding:52px 44px;max-width:520px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.gear{font-size:80px;display:block;animation:spin 4s linear infinite;margin-bottom:24px}
@keyframes spin{to{transform:rotate(360deg)}}
h1{font-size:28px;font-weight:800;color:var(--green);margin-bottom:14px}
p{font-size:15px;color:#555;line-height:1.7;margin-bottom:28px}
.progress{height:4px;background:#eee;border-radius:99px;overflow:hidden;margin-bottom:24px}
.bar{height:100%;width:40%;background:linear-gradient(90deg,var(--green),var(--gold));border-radius:99px;animation:slide 2s ease-in-out infinite}
@keyframes slide{0%{transform:translateX(-100%)}100%{transform:translateX(350%)}}
.footer{font-size:12px;color:#999;margin-top:12px}
</style>
</head>
<body>
<div class="card">
  <span class="gear"><?= icon('settings') ?></span>
  <h1>الموقع تحت الصيانة</h1>
  <p><?= e(setting('maintenance_message','نعمل على تحسين الموقع وسنعود قريبا. شكرا لصبرك.')) ?></p>
  <div class="progress"><div class="bar"></div></div>
  <div class="footer">إذا كنت مشرفا <a href="<?= BASE_URL ?>/auth/login" style="color:var(--green)">سجل دخولك هنا</a></div>
</div>
</body>
</html>
