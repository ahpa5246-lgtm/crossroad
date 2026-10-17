<?php $me = (int)auth()['id']; $sel = (int)($a['author_id'] ?? $me); ?>
<div class="acard">
  <div class="acard-header" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
    <h3><?= icon('newspaper') ?> <?= $a ? 'تعديل المقال' : 'مقال جديد' ?></h3>
    <a href="<?= BASE_URL ?>/admin/articles" class="btn btn--sm btn--outline">رجوع للقائمة</a>
  </div>
  <form action="<?= BASE_URL ?>/admin/articles/save" method="POST" enctype="multipart/form-data" class="acard-body">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($a['id'] ?? 0) ?>">
    <div class="form-stack" style="display:flex;flex-direction:column;gap:12px">
      <div class="form-group" style="margin:0"><label class="form-label">العنوان <span class="required-star">*</span></label>
        <input type="text" name="title" class="form-input" maxlength="160" required value="<?= e($a['title'] ?? '') ?>"></div>
      <div class="form-group" style="margin:0"><label class="form-label">الكاتب (يظهر اسمه وصورته على المقال)</label>
        <select name="author_id" class="form-input">
          <?php $found = false; foreach ($authors as $u): if ((int)$u['id'] === $sel) $found = true; ?>
          <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === $sel ? 'selected' : '' ?>><?= e($u['display_name']) ?> (@<?= e($u['username']) ?>)</option>
          <?php endforeach; if (!$found): ?><option value="<?= $sel ?>" selected>الحساب الحالي</option><?php endif; ?>
        </select></div>
      <div class="form-group" style="margin:0"><label class="form-label">وصف قصير (يظهر بقائمة المجلة)</label>
        <textarea name="description" class="form-input form-textarea" rows="2" maxlength="240"><?= e($a['description'] ?? '') ?></textarea></div>
      <div class="form-group" style="margin:0"><label class="form-label">صورة الغلاف</label>
        <?php if (!empty($a['cover_image'])): ?>
          <img src="<?= BASE_URL . e($a['cover_image']) ?>" alt="" style="max-width:240px;display:block;margin-bottom:8px;border:2px solid var(--border)">
          <label style="font-size:13px;display:block;margin-bottom:6px"><input type="checkbox" name="remove_cover" value="1"> حذف الغلاف الحالي</label>
        <?php endif; ?>
        <input type="file" name="cover" class="form-input" accept="image/*" style="padding:6px"></div>
      <div class="form-group" style="margin:0"><label class="form-label">نص المقال <span class="required-star">*</span></label>
        <textarea name="body" class="form-input form-textarea" rows="16" required><?= e($a['body'] ?? '') ?></textarea>
        <small style="color:var(--tm);font-size:12px">**نص عريض** — سطر يبدأ بـ "- " قائمة — "1. " قائمة مرقمة — سطر فارغ يفصل الفقرات — [نص](https://رابط)</small></div>
      <div><button type="submit" class="btn btn--gold"><?= icon('save') ?> <?= $a ? 'حفظ التعديل' : 'نشر المقال' ?></button></div>
    </div>
  </form>
</div>
