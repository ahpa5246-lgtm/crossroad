<div class="toolbar">
  <a href="<?= BASE_URL ?>/admin/users" class="btn btn--outline btn--sm">← المستخدمون</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px">

  <!-- Edit Info -->
  <div class="acard">
    <div class="acard-header"><h3><?= icon('pencil') ?> تعديل بيانات المستخدم</h3></div>
    <form action="<?= BASE_URL ?>/admin/users/edit" method="POST" class="acard-body">
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
      <div class="form-stack">
        <?php if ($e=flash('error')): ?><div class="alert alert--error"><?= e($e) ?></div><?php endif; ?>
        <?php if ($s=flash('success')): ?><div class="alert alert--success"><?= e($s) ?></div><?php endif; ?>

        <div class="form-group">
          <label class="form-label">الاسم الكامل <span class="required-star">*</span></label>
          <input type="text" name="display_name" class="form-input" value="<?= e($u['display_name']) ?>" required minlength="2">
        </div>
        <div class="form-group">
          <label class="form-label">اسم المستخدم <span class="required-star">*</span></label>
          <input type="text" name="username" class="form-input" value="<?= e($u['username']) ?>" required pattern="[a-z0-9_]{3,30}">
        </div>
        <div class="form-group">
          <label class="form-label">البريد الإلكتروني <span class="required-star">*</span></label>
          <input type="email" name="email" class="form-input" value="<?= e($u['email']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">معرف التيليغرام</label>
          <input type="text" name="telegram_username" class="form-input" value="<?= e($u['telegram_username']??'') ?>" placeholder="@username">
        </div>
        <div class="form-group">
          <label class="form-label">الدور</label>
          <select name="role" class="form-select">
            <option value="user"        <?= $u['role']==='user'?'selected':'' ?>>عضو</option>
            <option value="moderator"   <?= $u['role']==='moderator'?'selected':'' ?>>محرر</option>
            <option value="admin"       <?= $u['role']==='admin'?'selected':'' ?>>مشرف</option>
            <option value="super_admin" <?= $u['role']==='super_admin'?'selected':'' ?>>مشرف أعلى</option>
          </select>
        </div>
        <button type="submit" class="btn btn--gold"><?= icon('save') ?> حفظ التغييرات</button>
      </div>
    </form>
  </div>

  <!-- Actions column -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Change Password -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('key') ?> تغيير كلمة المرور</h3></div>
      <form action="<?= BASE_URL ?>/admin/users/password" method="POST" class="acard-body" data-confirm="تغيير كلمة مرور <?= e($u['display_name']) ?>؟ سيطرد من كل أجهزته." data-confirm-title="تغيير كلمة المرور" data-confirm-ok="تغيير">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
        <div class="form-stack">
          <div class="form-group">
            <label class="form-label">كلمة المرور الجديدة <span class="required-star">*</span></label>
            <input type="password" name="new_password" class="form-input" required minlength="8" placeholder="8+ أحرف، حرف كبير، رقم">
          </div>
          <div class="form-group">
            <label class="form-label">تأكيد كلمة المرور <span class="required-star">*</span></label>
            <input type="password" name="confirm_password" class="form-input" required>
          </div>
          <div class="alert alert--warn" style="font-size:13px"><?= icon('alert') ?> سيتم إلغاء جميع جلسات هذا المستخدم تلقائيا</div>
          <button type="submit" class="btn btn--outline">
            <?= icon('key') ?> تغيير كلمة المرور
          </button>
        </div>
      </form>
    </div>

    <!-- Delete Account -->
    <div class="acard" style="border:2px solid var(--err-br)">
      <div class="acard-header" style="background:var(--err-bg)">
        <h3 style="color:var(--err-fg)"><?= icon('trash') ?> حذف الحساب نهائيا</h3>
      </div>
      <div class="acard-body">
        <p style="font-size:13px;color:var(--tm);margin-bottom:16px;line-height:1.6">
          سيحذف الحساب بشكل دائم مع جميع جلساته. <strong>لا يمكن التراجع.</strong><br>
          التقديمات والشهادات والمحاولات ستبقى في النظام بدون ربط بمستخدم.
        </p>
        <form action="<?= BASE_URL ?>/admin/users/delete" method="POST" data-confirm="حذف حساب «<?= e($u['display_name']) ?>» نهائيا؟ لا يمكن التراجع!" data-confirm-title="حذف الحساب" data-confirm-ok="حذف" data-confirm-danger="1">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit" class="btn btn--danger btn--full">
            <?= icon('trash') ?> حذف الحساب نهائيا
          </button>
        </form>
      </div>
    </div>

    <!-- Info -->
    <div class="acard">
      <div class="acard-header"><h3><?= icon('info') ?> معلومات الحساب</h3></div>
      <div class="acard-body">
        <div style="display:flex;flex-direction:column;gap:8px;font-size:13px">
          <div style="display:flex;justify-content:space-between"><span style="color:var(--tm)">رقم الحساب</span><strong>#<?= $u['id'] ?></strong></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--tm)">تاريخ التسجيل</span><span><?= date('d/m/Y', strtotime($u['created_at'])) ?></span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--tm)">آخر دخول</span><span><?= $u['last_login_at'] ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : '—' ?></span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--tm)">الحالة</span><span class="badge badge--<?= $u['is_active']?'green':'red' ?>"><?= $u['is_active']?'نشط':'معطل' ?></span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--tm)">2FA</span><span><?= $u['two_fa_enabled']?(icon('circle-check') . ' مفعل'):'—' ?></span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--tm)">آخر IP</span><span style="font-family:monospace;font-size:11px"><?= e($u['last_login_ip']??'—') ?></span></div>
        </div>
      </div>
    </div>

  </div>
</div>
