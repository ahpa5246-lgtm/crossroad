<?php $_blank = ['id' => 0, 'title' => '', 'image' => '', 'link' => '', 'sort_order' => count($items) + 1, 'is_visible' => 1]; ?>
<div class="acard" style="margin-bottom:18px">
  <div class="acard-header"><h3>الحملات (صور الكاروسيل)</h3></div>
  <div class="acard-body">
    <p style="font-size:13px;color:var(--tm);margin:0 0 12px">الصور تظهر بالصفحة الرئيسية بشريط متحرك. الأفضل صورة عريضة بنسبة 16:7 (مثال 1600×700). إذا ما أضفت أي حملة تظهر آخر التحديات والفعاليات تلقائيا.</p>
    <form action="<?= BASE_URL ?>/admin/campaigns/save" method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <div class="form-group" style="flex:1;min-width:220px;margin:0"><label class="form-label">العنوان تحت الصور</label>
        <input type="text" name="carousel_title" class="form-input" value="<?= e($settings['carousel_title'] ?? 'أحدث الحملات') ?>"></div>
      <button class="btn btn--sm btn--gold" type="submit"><?= icon('save') ?> حفظ</button>
    </form>
  </div>
</div>

<?php foreach (array_merge($items, [$_blank]) as $m): $isNew = (int)$m['id'] === 0; ?>
<div class="acard" style="margin-bottom:14px">
  <div class="acard-header"><h3><?= $isNew ? 'إضافة حملة' : e($m['title'] ?: 'حملة #' . $m['id']) ?></h3></div>
  <div class="acard-body">
    <form action="<?= BASE_URL ?>/admin/campaigns/save" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <div style="display:grid;grid-template-columns:200px 1fr;gap:16px;align-items:start">
        <div style="aspect-ratio:16/7;border:2px solid var(--border);background:var(--bg-2);overflow:hidden;display:grid;place-items:center;color:var(--tm);font-size:12px">
          <?php if ($m['image']): ?><img src="<?= BASE_URL . e($m['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover"><?php else: ?>لا توجد صورة<?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <div style="display:grid;grid-template-columns:1fr 90px;gap:10px">
            <div class="form-group" style="margin:0"><label class="form-label">الاسم (للتوضيح)</label><input type="text" name="title" class="form-input" value="<?= e($m['title']) ?>"></div>
            <div class="form-group" style="margin:0"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="<?= (int)$m['sort_order'] ?>"></div>
          </div>
          <div class="form-group" style="margin:0"><label class="form-label">الصورة <?= $isNew ? '' : '(اتركها فارغة للإبقاء على الحالية)' ?></label><input type="file" name="image" class="form-input" accept="image/*" style="padding:6px"></div>
          <div class="form-group" style="margin:0"><label class="form-label">رابط عند الضغط (اختياري)</label><input type="text" name="link" class="form-input" value="<?= e($m['link']) ?>" placeholder="/news/view?id=1 أو https://" style="direction:ltr;text-align:left"></div>
          <label style="font-size:14px"><input type="checkbox" name="is_visible" value="1" <?= $m['is_visible'] ? 'checked' : '' ?>> ظاهرة</label>
          <div><button class="btn btn--sm btn--gold" type="submit"><?= icon($isNew ? 'plus' : 'save') ?> <?= $isNew ? 'إضافة' : 'حفظ' ?></button></div>
        </div>
      </div>
    </form>
    <?php if (!$isNew): ?>
    <form action="<?= BASE_URL ?>/admin/campaigns/delete" method="POST" style="margin-top:8px" data-confirm="حذف هذه الحملة؟">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <button class="btn btn--xs btn--danger" type="submit"><?= icon('trash') ?> حذف</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
