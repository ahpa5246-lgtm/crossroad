<?php $_blank = ['id' => 0, 'title' => '', 'body' => '', 'when_text' => '', 'images' => [], 'sort_order' => count($items) + 1, 'is_visible' => 1]; ?>
<div class="acard" style="margin-bottom:18px">
  <div class="acard-header"><h3>صفحة الإنجازات</h3></div>
  <div class="acard-body">
    <p style="font-size:13px;color:var(--tm);margin:0 0 12px">اكتب إنجازات الفريق وارفع صورها. تظهر بصفحة <a href="<?= BASE_URL ?>/achievements" target="_blank">/achievements</a>، وصور آخر الإنجازات تظهر أيضا بالشريط المتحرك بأعلى الصفحة الرئيسية وبمقتطف الإنجازات.</p>
    <form action="<?= BASE_URL ?>/admin/achievements/save" method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <div class="form-group" style="flex:1;min-width:220px;margin:0"><label class="form-label">عنوان الصفحة</label>
        <input type="text" name="page_title" class="form-input" value="<?= e($settings['achievements_title'] ?? 'الإنجازات') ?>"></div>
      <div class="form-group" style="flex:1;min-width:220px;margin:0"><label class="form-label">عنوان الشريط المتحرك بالرئيسية</label>
        <input type="text" name="carousel_title" class="form-input" value="<?= e($settings['carousel_title'] ?? 'آخر الإنجازات') ?>"></div>
      <button class="btn btn--sm btn--gold" type="submit"><?= icon('save') ?> حفظ</button>
    </form>
  </div>
</div>

<?php foreach (array_merge($items, [$_blank]) as $m): $isNew = (int)$m['id'] === 0; ?>
<div class="acard" style="margin-bottom:14px">
  <div class="acard-header"><h3><?= $isNew ? 'إضافة إنجاز' : e($m['title']) ?></h3></div>
  <div class="acard-body">
    <form action="<?= BASE_URL ?>/admin/achievements/save" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <div style="display:flex;flex-direction:column;gap:10px">
        <div style="display:grid;grid-template-columns:1fr 160px 90px;gap:10px">
          <div class="form-group" style="margin:0"><label class="form-label">عنوان الإنجاز</label><input type="text" name="title" class="form-input" value="<?= e($m['title']) ?>"></div>
          <div class="form-group" style="margin:0"><label class="form-label">التاريخ (نص حر)</label><input type="text" name="when_text" class="form-input" value="<?= e($m['when_text']) ?>" placeholder="مثال: 2025"></div>
          <div class="form-group" style="margin:0"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="<?= (int)$m['sort_order'] ?>"></div>
        </div>
        <div class="form-group" style="margin:0"><label class="form-label">الوصف (اكتب بحرية — **عريض**، "- " قائمة)</label><textarea name="body" class="form-input form-textarea" rows="5"><?= e($m['body']) ?></textarea></div>
        <?php if ($m['images']): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <?php foreach ($m['images'] as $im): ?>
          <label style="width:110px;font-size:12px;text-align:center">
            <img src="<?= BASE_URL . e($im) ?>" alt="" style="width:110px;height:76px;object-fit:cover;border:2px solid var(--border);display:block">
            <input type="checkbox" name="remove_img[]" value="<?= e($im) ?>"> حذف
          </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="form-group" style="margin:0"><label class="form-label">رفع صور (يمكن اختيار أكثر من صورة)</label><input type="file" name="images[]" class="form-input" accept="image/*" multiple style="padding:6px"></div>
        <label style="font-size:14px"><input type="checkbox" name="is_visible" value="1" <?= $m['is_visible'] ? 'checked' : '' ?>> ظاهر بالموقع</label>
        <div><button class="btn btn--sm btn--gold" type="submit"><?= icon($isNew ? 'plus' : 'save') ?> <?= $isNew ? 'إضافة' : 'حفظ' ?></button></div>
      </div>
    </form>
    <?php if (!$isNew): ?>
    <form action="<?= BASE_URL ?>/admin/achievements/delete" method="POST" style="margin-top:8px" data-confirm="حذف هذا الإنجاز؟">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <button class="btn btn--xs btn--danger" type="submit"><?= icon('trash') ?> حذف</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
