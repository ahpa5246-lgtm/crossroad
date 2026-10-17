<?php
$_fields = ['facebook' => 'فيسبوك', 'twitter' => 'تويتر / X', 'instagram' => 'إنستغرام', 'discord' => 'ديسكورد', 'telegram' => 'تليجرام'];
$_blank = ['id' => 0, 'name' => '', 'role' => '', 'bio' => '', 'avatar' => '', 'facebook' => '', 'twitter' => '', 'instagram' => '', 'discord' => '', 'telegram' => '', 'sort_order' => count($members) + 1, 'is_visible' => 1];
?>
<div class="acard" style="margin-bottom:18px">
  <div class="acard-header"><h3>عنوان القسم</h3></div>
  <div class="acard-body">
    <form action="<?= BASE_URL ?>/admin/team/save" method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <div class="form-group" style="flex:1;min-width:220px;margin:0"><label class="form-label">العنوان الظاهر بالصفحة الرئيسية</label>
        <input type="text" name="team_title" class="form-input" value="<?= e($settings['team_title'] ?? 'الإدارة العليا') ?>"></div>
      <button class="btn btn--sm btn--gold" type="submit"><?= icon('save') ?> حفظ</button>
    </form>
  </div>
</div>

<?php foreach (array_merge($members, [$_blank]) as $m): $isNew = (int)$m['id'] === 0; ?>
<div class="acard" style="margin-bottom:14px">
  <div class="acard-header"><h3><?= $isNew ? 'إضافة عضو جديد' : e($m['name'] ?: $m['role']) ?></h3></div>
  <div class="acard-body">
    <form action="<?= BASE_URL ?>/admin/team/save" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <div style="display:grid;grid-template-columns:96px 1fr;gap:16px;align-items:start">
        <div style="text-align:center">
          <div style="width:96px;border:2px solid var(--border);overflow:hidden;background:var(--bg-2)">
            <?php if ($m['avatar']): ?><img src="<?= BASE_URL . e($m['avatar']) ?>" alt="" style="width:100%;display:block;image-rendering:auto">
            <?php else: ?><?= pixel_avatar_svg($m['name'] ?: 'new', 96) ?><?php endif; ?>
          </div>
          <?php if ($m['avatar']): ?><label style="font-size:12px;display:block;margin-top:6px"><input type="checkbox" name="remove_avatar" value="1"> حذف الصورة</label><?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <div style="display:grid;grid-template-columns:1fr 1fr 90px;gap:10px">
            <div class="form-group" style="margin:0"><label class="form-label">الاسم</label><input type="text" name="name" class="form-input" value="<?= e($m['name']) ?>"></div>
            <div class="form-group" style="margin:0"><label class="form-label">المنصب</label><input type="text" name="role" class="form-input" value="<?= e($m['role']) ?>" placeholder="مثال: المؤسس"></div>
            <div class="form-group" style="margin:0"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="<?= (int)$m['sort_order'] ?>"></div>
          </div>
          <div class="form-group" style="margin:0"><label class="form-label">وصف بسيط عنه</label><textarea name="bio" class="form-input form-textarea" rows="2" maxlength="400"><?= e($m['bio'] ?? '') ?></textarea></div>
          <div class="form-group" style="margin:0"><label class="form-label">الصورة (اتركها فارغة لتظهر صورة بكسل تلقائية)</label><input type="file" name="avatar" class="form-input" accept="image/*" style="padding:6px"></div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px">
            <?php foreach ($_fields as $k => $l): ?>
            <div class="form-group" style="margin:0"><label class="form-label"><?= $l ?></label><input type="url" name="<?= $k ?>" class="form-input" value="<?= e($m[$k]) ?>" placeholder="https://"></div>
            <?php endforeach; ?>
          </div>
          <label style="font-size:14px"><input type="checkbox" name="is_visible" value="1" <?= $m['is_visible'] ? 'checked' : '' ?>> ظاهر بالموقع</label>
          <div style="display:flex;gap:8px">
            <button class="btn btn--sm btn--gold" type="submit"><?= icon($isNew ? 'plus' : 'save') ?> <?= $isNew ? 'إضافة' : 'حفظ' ?></button>
          </div>
        </div>
      </div>
    </form>
    <?php if (!$isNew): ?>
    <form action="<?= BASE_URL ?>/admin/team/delete" method="POST" style="margin-top:8px" data-confirm="حذف هذا العضو؟">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <button class="btn btn--xs btn--danger" type="submit"><?= icon('trash') ?> حذف</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
