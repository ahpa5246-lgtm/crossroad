<div class="acard" style="max-width:680px">
  <div class="acard-header"><h3><?= icon('megaphone') ?> تبليغ جديد</h3></div>
  <form action="<?= BASE_URL ?>/admin/announcements/save" method="POST" class="acard-body">
    <?= csrf_field() ?>
    <div class="form-stack">
      <div class="form-group">
        <label class="form-label">العنوان</label>
        <input type="text" name="title" class="form-input" required maxlength="200" autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">المحتوى</label>
        <textarea name="body" class="form-input" rows="5" required style="resize:vertical"></textarea>
      </div>
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">النوع</label>
          <select name="type" class="form-input">
            <option value="info">معلومة</option>
            <option value="warning">تحذير</option>
            <option value="success">نجاح</option>
            <option value="danger">طارئ</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">الجمهور</label>
          <select name="target" class="form-input">
            <option value="all_admins">كل المشرفين</option>
            <option value="admins">المشرفون فقط</option>
            <option value="super_admin">المشرف الأعلى فقط</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="is_pinned" value="1" style="width:16px;height:16px">
          <span class="form-label" style="margin:0"><?= icon('pin') ?> تثبيت هذا التبليغ في الأعلى</span>
        </label>
      </div>
      <div style="display:flex;gap:10px">
        <button class="btn btn--gold"><?= icon('send') ?> نشر التبليغ</button>
        <a href="<?= BASE_URL ?>/admin/announcements" class="btn btn--outline">إلغاء</a>
      </div>
    </div>
  </form>
</div>
