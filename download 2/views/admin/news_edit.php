<?php $isNew=!$item; ?>
<div class="toolbar"><a href="<?= BASE_URL ?>/admin/news" class="btn btn--outline btn--sm">← كل الفعاليات</a></div>
<div class="acard" style="max-width:820px">
  <div class="acard-header"><h3><?= $isNew?'فعالية جديدة':'تعديل الفعالية' ?></h3></div>
  <form action="<?= BASE_URL ?>/admin/news/save" method="POST" enctype="multipart/form-data" class="acard-body">
    <?= csrf_field() ?>
    <input type="hidden" name="news_id" value="<?= $item['id']??0 ?>">
    <div class="form-stack">
      <div class="form-group"><label class="form-label">العنوان <span class="required-star">*</span></label><input type="text" name="title" class="form-input" required value="<?= e($item['title']??'') ?>"></div>
      <div class="form-group"><label class="form-label">التصنيف</label><input type="text" name="category" class="form-input" value="<?= e($item['category']??'') ?>" placeholder="مثال: فعاليات، إعلانات، ورش..."></div>
      <div class="form-group"><label class="form-label">المحتوى <span class="required-star">*</span></label><textarea name="description" class="form-input form-textarea" rows="10" required><?= e($item['description']??'') ?></textarea></div>
      <div class="form-group">
        <label class="form-label">صورة الغلاف</label>
        <?php if(!empty($item['cover_image'])): ?><img src="<?= BASE_URL . e($item['cover_image']) ?>" style="max-width:200px;border-radius:8px;margin-bottom:8px"><?php endif; ?>
        <input type="file" name="cover_image" class="form-input" accept="image/*" style="padding:6px">
      </div>
      <label class="check-label">
        <input type="checkbox" name="publish" id="pubCheck" <?= ($item['is_published']??0)?'checked':'' ?>>
        <div><strong>نشر فورا</strong><p style="font-size:12px;color:var(--tm);margin:0">سيظهر على الموقع فور الحفظ.</p></div>
      </label>
      <div class="form-actions">
        <button type="submit" class="btn btn--gold btn--lg"><?= icon('save') ?> حفظ الفعالية</button>
        <a href="<?= BASE_URL ?>/admin/news" class="btn btn--outline">إلغاء</a>
      </div>
    </div>
  </form>
</div>
