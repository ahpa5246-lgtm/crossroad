<div class="acard" style="max-width:620px">
  <h2 style="font-family:var(--font-display);margin-bottom:24px"><?= icon('plus') ?> إضافة مشرف جديد</h2>
  <?php if($e=flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>
  <form method="POST" action="<?= BASE_URL ?>/admin/admins/create" class="form-stack">
    <?= csrf_field() ?>
    <div class="form-row-2">
      <div class="form-group"><label class="form-label">الاسم الكامل <span class="required-star">*</span></label><input type="text" name="display_name" class="form-input" required minlength="2"></div>
      <div class="form-group"><label class="form-label">اسم المستخدم <span class="required-star">*</span></label><input type="text" name="username" id="adminUname" class="form-input" required pattern="[a-zA-Z0-9_]{3,30}"><div style="font-size:11px;color:var(--text-muted);margin-top:3px">أحرف إنجليزية + أرقام + _ فقط</div></div>
    </div>
    <div class="form-row-2">
      <div class="form-group"><label class="form-label">البريد <span class="required-star">*</span></label><input type="email" name="email" class="form-input" required></div>
      <div class="form-group"><label class="form-label">معرف التيليغرام</label><input type="text" name="telegram_username" class="form-input" placeholder="@username"></div>
    </div>
    <div class="form-row-2">
      <div class="form-group"><label class="form-label">كلمة المرور <span class="required-star">*</span></label><input type="password" name="password" class="form-input" required minlength="8"></div>
      <div class="form-group"><label class="form-label">تأكيد كلمة المرور</label><input type="password" name="confirm_password" class="form-input" required></div>
    </div>
    <div class="form-group" style="margin-top:4px">
      <label class="form-label" style="display:block;margin-bottom:10px">الصلاحيات <span style="font-size:12px;font-weight:400;color:var(--text-muted)">— بدون تحديد = سجلات فقط</span></label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <?php foreach(['manage_users'=>['users','إدارة المستخدمين'],'manage_content'=>['file-text','إدارة المحتوى'],'manage_forms'=>['clipboard','إدارة النماذج'],'manage_challenges'=>['target','إدارة التحديات'],'manage_certificates'=>['award','إدارة الشهادات'],'run_sql'=>['settings','SQL']] as $k=>[$ic,$lb]): ?>
        <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid var(--border);border-radius:var(--r-md);cursor:pointer">
          <input type="checkbox" name="perm_<?= $k ?>" value="1" style="accent-color:var(--sage-dark)">
          <span><?= icon($ic) ?> <?= $lb ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:8px">
      <button type="submit" class="btn btn--gold btn--lg">إنشاء المشرف ←</button>
      <a href="<?= BASE_URL ?>/admin/users" class="btn btn--outline">إلغاء</a>
    </div>
  </form>
</div>
<script nonce="<?= csp_nonce() ?>">
document.getElementById('adminUname').addEventListener('input',function(){const c=this.value.replace(/[^a-zA-Z0-9_]/g,'');if(c!==this.value){const p=this.selectionStart;this.value=c;this.setSelectionRange(p,p);}});
</script>
