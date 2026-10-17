<div class="acard" style="max-width:620px">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <div style="width:48px;height:48px;background:var(--sage-faint);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px"><?= icon('user') ?></div>
    <div>
      <h2 style="font-family:var(--font-display);margin:0"><?= e($user['display_name']) ?></h2>
      <span style="font-size:13px;color:var(--text-muted)">@<?= e($user['username']) ?> · <?= e($user['email']) ?></span>
    </div>
  </div>

  <?php if ($e = flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>/admin/users/promote" class="form-stack">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

    <div class="form-group">
      <label class="form-label">الدور</label>
      <select name="role" class="form-input">
        <option value="user"       <?= $user['role']==='user'?'selected':''       ?>>عضو (لا صلاحيات إدارية)</option>
        <option value="moderator"  <?= $user['role']==='moderator'?'selected':''  ?>>محرر</option>
        <option value="admin"      <?= $user['role']==='admin'?'selected':''      ?>>مشرف</option>
        <option value="super_admin"<?= $user['role']==='super_admin'?'selected':''?>>مشرف أعلى (كل الصلاحيات)</option>
      </select>
    </div>

    <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--border);border-radius:var(--r-md);cursor:pointer;margin-bottom:14px">
      <input type="checkbox" name="is_volunteer" value="1" <?= !empty($user['is_volunteer']) ? 'checked' : '' ?> style="margin-top:3px;accent-color:var(--sage-dark)">
      <div><div style="font-weight:700;font-size:14px"><?= icon('pencil') ?> متطوع</div><div style="font-size:12px;color:var(--text-muted);margin-top:2px">يستطيع نشر مقالات بالمجلة (تظهر باسمه وصورته).</div></div>
    </label>

    <div class="form-group" id="permsGroup">
      <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
        <span>صلاحيات المشرف</span>
        <span style="font-size:11px;font-weight:400;color:var(--text-muted)">بدون تحديد = سجلات فقط</span>
      </label>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
        <?php
        $currentPerms = json_decode($user['permissions'] ?? '[]', true) ?: [];
        $permDefs = [
          'manage_users'        => ['users','إدارة المستخدمين',    'عرض وتعديل وحذف الحسابات'],
          'manage_content'      => ['file-text','إدارة المحتوى',       'الفعاليات والمحتوى والإعدادات'],
          'manage_forms'        => ['clipboard','إدارة النماذج',       'النماذج والطلبات والمقبولون'],
          'manage_challenges'   => ['target','إدارة التحديات',      'التحديات والأسئلة والنتائج'],
          'manage_certificates' => ['award','إدارة الشهادات',      'إصدار وإلغاء الشهادات'],
          'run_sql'             => ['settings','تنفيذ SQL',           'استعلامات قاعدة البيانات'],
        ];
        foreach ($permDefs as $key => [$icon, $label, $desc]): ?>
        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--border);border-radius:var(--r-md);cursor:pointer;transition:background .15s"
               onmouseover="this.style.background='var(--sage-faint)'" onmouseout="this.style.background=''">
          <input type="checkbox" name="perm_<?= $key ?>" value="1"
                 <?= in_array($key, $currentPerms) ? 'checked' : '' ?>
                 style="margin-top:3px;accent-color:var(--sage-dark)">
          <div>
            <div style="font-weight:600;font-size:13px"><?= icon($icon) ?> <?= $label ?></div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $desc ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:flex;gap:12px;margin-top:8px">
      <button type="submit" class="btn btn--gold btn--lg">حفظ ←</button>
      <a href="<?= BASE_URL ?>/admin/users" class="btn btn--outline">إلغاء</a>
    </div>
  </form>
</div>

<script nonce="<?= csp_nonce() ?>">
document.querySelector('select[name="role"]').addEventListener('change', function() {
  document.getElementById('permsGroup').style.display = this.value === 'admin' ? '' : 'none';
});
document.querySelector('select[name="role"]').dispatchEvent(new Event('change'));
</script>
