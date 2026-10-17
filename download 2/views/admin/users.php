<div class="toolbar">
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>/admin/admins/new"       class="btn btn--gold"><?= icon('plus') ?> مشرف جديد</a>
    <a href="<?= BASE_URL ?>/admin/users/bulk-form"  class="btn btn--outline"><?= icon('clipboard') ?> إنشاء جماعي</a>
  </div>
  <form method="GET" style="display:flex;gap:8px">
    <input name="search" value="<?= e($_GET['search']??'') ?>" placeholder="بحث…" class="form-input" style="width:180px">
    <button class="btn btn--outline"><?= icon('search') ?></button>
  </form>
</div>

<div class="acard"><div class="table-wrap"><table class="atable">
  <thead>
    <tr>
      <th>#</th><th>الاسم</th><th>المستخدم</th><th>البريد</th>
      <th>الدور</th><th>الصلاحيات</th><th>الحالة</th><th>إجراءات</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $i => $u):
    $me   = auth();
    $isSA = $me['role'] === 'super_admin';
    $self = $u['id'] === (int)$me['id'];
    $perms = json_decode($u['permissions'] ?? '[]', true) ?: [];
    $permLabels = [
      'manage_users'=>'users','manage_content'=>'file-text','manage_forms'=>'clipboard',
      'manage_challenges'=>'target','manage_certificates'=>'award','run_sql'=>'settings',
    ];
  ?>
  <tr>
    <td style="color:var(--text-muted);font-size:12px"><?= $i+1 ?></td>
    <td><strong><?= e($u['display_name']) ?></strong><br>
        <span style="font-size:11px;color:var(--text-muted)"><?= $u['telegram_username'] ? e($u['telegram_username']) : '' ?></span>
    </td>
    <td><code style="font-size:12px">@<?= e($u['username']) ?></code></td>
    <td style="font-size:12px"><?= e($u['email']) ?></td>
    <td>
      <?php if ($isSA && !$self): ?>
      <form action="<?= BASE_URL ?>/admin/users/role" method="POST" style="display:inline" id="roleForm<?= $u['id'] ?>">
        <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
        <select name="role" class="form-select" style="font-size:12px;padding:4px 8px"
                data-user-id="<?= $u['id'] ?>" data-username="<?= e($u['username']) ?>">
          <?php foreach(['user'=>'عضو','moderator'=>'محرر','admin'=>'مشرف','super_admin'=>'أعلى'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $u['role']===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php else: ?>
        <span class="badge badge--<?= $u['role']==='super_admin'?'gold':($u['role']==='admin'?'blue':'gray') ?>">
          <?= ($u['role']==='super_admin'?'أعلى':($u['role']==='admin'?'مشرف':($u['role']==='moderator'?'محرر':'عضو'))) ?>
        </span>
      <?php endif; ?>
    </td>
    <td>
      <?php if (in_array($u['role'], ['admin','super_admin'], true)): ?>
        <?php if ($u['role'] === 'super_admin'): ?>
          <span style="font-size:11px;color:var(--text-muted)">كل الصلاحيات</span>
        <?php elseif ($isSA && !$self): ?>
          <a href="<?= BASE_URL ?>/admin/users/promote?id=<?= $u['id'] ?>" class="btn btn--xs btn--outline" title="تعديل الصلاحيات">
            <?php foreach($permLabels as $k=>$ic): if(in_array($k,$perms)) echo icon($ic); endforeach; ?>
            <?= empty($perms)?'<span style="color:var(--text-muted)">سجلات فقط</span>':'' ?> <?= icon('pencil') ?>
          </a>
        <?php else: ?>
          <?php foreach($permLabels as $k=>$ic): if(in_array($k,$perms)) echo icon($ic); endforeach; ?>
        <?php endif; ?>
      <?php else: ?>
        <span style="color:var(--text-muted);font-size:11px">—</span>
      <?php endif; ?>
    </td>
    <td><span class="badge badge--<?= $u['is_active']?'green':'red' ?>"><?= $u['is_active']?'نشط':'معطل' ?></span></td>
    <td class="tbl-actions">
      <?php if ($isSA && !$self): ?>
        <a href="<?= BASE_URL ?>/admin/users/edit?id=<?= $u['id'] ?>" class="btn btn--xs btn--outline"><?= icon('pencil') ?></a>
        <form method="POST" action="<?= BASE_URL ?>/admin/users/toggle" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button class="btn btn--xs <?= $u['is_active']?'btn--danger':'btn--green' ?>"><?= $u['is_active']?'تعطيل':'تفعيل' ?></button>
        </form>
        <?php if ($u['role'] !== 'super_admin'): ?>
        <form method="POST" action="<?= BASE_URL ?>/admin/users/delete" style="display:inline"
              data-confirm="حذف «<?= e($u['display_name']) ?>» نهائيا؟" data-confirm-title="حذف المستخدم" data-confirm-ok="حذف" data-confirm-danger="1">
          <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button class="btn btn--xs btn--danger"><?= icon('trash') ?></button>
        </form>
        <?php endif; ?>
      <?php elseif ($self): ?>
        <span style="font-size:11px;color:var(--text-muted)">أنت</span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($users)): ?><tr><td colspan="8" class="empty-row">لا توجد نتائج.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>

<script nonce="<?= csp_nonce() ?>">
var USERS_BASE = '<?= BASE_URL ?>';
async function handleRoleChange(sel, uid, uname) {
  const role = sel.value;
  if (role === 'admin') {
    // Don't auto-submit, go to permissions page instead
    const ok = await (window.adminConfirm ? window.adminConfirm('سيتم ترقية «' + uname + '» إلى مشرف. سيعيد توجيهك لاختيار الصلاحيات.', {
      title: 'ترقية المستخدم',
      confirmText: 'متابعة'
    }) : Promise.resolve(confirm('سيتم ترقية «' + uname + '» إلى مشرف. سيعيد توجيهك لاختيار الصلاحيات.')));
    if (ok) {
      window.location.href = USERS_BASE + '/admin/users/promote?id=' + uid + '&role=admin';
    } else {
      // Reset select to original
      sel.value = sel.getAttribute('data-orig') || 'user';
    }
  } else {
    sel.closest('form').submit();
  }
}
// Store original values
document.querySelectorAll('select[name="role"]').forEach(s => s.setAttribute('data-orig', s.value));
document.querySelectorAll('select[name="role"][data-user-id]').forEach(s => {
  s.addEventListener('change', function() {
    handleRoleChange(this, this.dataset.userId, this.dataset.username);
  });
});
</script>
