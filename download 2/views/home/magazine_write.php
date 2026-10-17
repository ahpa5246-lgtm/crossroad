<?php $old = flash('old') ?: []; $v = fn($k) => e($old[$k] ?? ($a[$k] ?? '')); ?>
<section class="page-pix">
  <div class="container pp-wrap pp-narrow2">
    <h2 class="plate"><span><?= $a ? 'تعديل المقال' : 'مقال جديد' ?></span></h2>
    <?php if ($e = flash('error')): ?><div class="alert alert--error"><?= e($e) ?></div><?php endif; ?>
    <form action="<?= BASE_URL ?>/magazine/save" method="POST" enctype="multipart/form-data" class="panel auth-form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($a['id'] ?? 0) ?>">
      <div class="form-group"><label class="form-label">العنوان <span class="required-star">*</span></label><input type="text" name="title" class="form-input" maxlength="160" required value="<?= $v('title') ?>"></div>
      <div class="form-group"><label class="form-label">وصف قصير (يظهر بالقائمة)</label><textarea name="description" class="form-input form-textarea" rows="2" maxlength="240"><?= $v('description') ?></textarea></div>
      <div class="form-group"><label class="form-label">صورة الغلاف</label>
        <?php if (!empty($a['cover_image'])): ?><img src="<?= BASE_URL . e($a['cover_image']) ?>" alt="" style="max-width:220px;display:block;margin-bottom:8px;border:2px solid var(--border)"><label style="font-size:13px"><input type="checkbox" name="remove_cover" value="1"> حذف الغلاف</label><?php endif; ?>
        <input type="file" name="cover" class="form-input" accept="image/*" style="padding:6px"></div>
      <div class="form-group"><label class="form-label">المقال <span class="required-star">*</span></label><textarea name="body" class="form-input form-textarea" rows="16" required><?= $v('body') ?></textarea>
        <small style="color:var(--tm);font-size:12px">**نص عريض** — سطر يبدأ بـ "- " قائمة — "1. " قائمة مرقمة — سطر فارغ يفصل الفقرات — [نص](https://رابط)</small></div>
      <div style="display:flex;gap:10px"><button type="submit" class="btn btn--gold btn--lg"><?= $a ? 'حفظ التعديل' : 'نشر المقال' ?></button><a href="<?= BASE_URL ?>/magazine" class="btn btn--outline btn--lg">إلغاء</a></div>
    </form>
  </div>
</section>
