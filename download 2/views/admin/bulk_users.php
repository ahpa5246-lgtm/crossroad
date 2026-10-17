<div class="acard" style="max-width:720px">
  <h2 style="font-family:var(--font-display);margin-bottom:8px"><?= icon('users') ?> إنشاء مستخدمين جماعي</h2>
  <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px">أدخل كل مستخدم في سطر: <code>username,email,الاسم,كلمة_المرور</code> (كلمة المرور اختيارية — تولد تلقائيا)</p>
  <?php if($e=flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>
  <form method="POST" action="<?= BASE_URL ?>/admin/users/bulk-create">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">البيانات (CSV)</label>
    <textarea name="users_csv" class="form-input" rows="12" style="font-family:monospace;font-size:13px;direction:ltr;text-align:left" placeholder="# username,email,display_name,password&#10;yousif,y@example.com,يوسف&#10;narjes,n@example.com,نرجس,Pass@123"></textarea></div>
    <button type="submit" class="btn btn--gold btn--lg">إنشاء المستخدمين ←</button>
    <a href="<?= BASE_URL ?>/admin/users" class="btn btn--outline">إلغاء</a>
  </form>
</div>
