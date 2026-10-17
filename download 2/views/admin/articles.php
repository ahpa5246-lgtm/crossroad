<div class="acard">
  <div class="acard-header" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
    <h3><?= icon('newspaper') ?> المقالات (<?= count($articles) ?>)</h3>
    <a href="<?= BASE_URL ?>/admin/articles/edit" class="btn btn--sm btn--gold"><?= icon('plus') ?> مقال جديد</a>
  </div>
  <div class="acard-body">
    <p style="font-size:13px;color:var(--tm);margin:0 0 14px">كل المقالات المنشورة بالمجلة. تقدر تضيف مقال باسم أي متطوع، تعدل أي مقال، أو تحذفه. يظهرون بصفحة <a href="<?= BASE_URL ?>/magazine" target="_blank" rel="noopener">/magazine</a>.</p>
    <?php if (!$articles): ?>
      <div style="text-align:center;color:var(--tm);padding:28px">لا توجد مقالات بعد.</div>
    <?php else: ?>
    <div class="art-rows">
      <?php foreach ($articles as $a): ?>
      <div class="art-row">
        <div class="art-row-cover"><?php if ($a['cover_image']): ?><img src="<?= BASE_URL . e($a['cover_image']) ?>" alt=""><?php else: ?><?= icon('newspaper') ?><?php endif; ?></div>
        <div class="art-row-main">
          <a href="<?= BASE_URL ?>/magazine/view?id=<?= (int)$a['id'] ?>" target="_blank" rel="noopener" class="art-row-title"><?= e($a['title']) ?></a>
          <div class="art-row-meta"><?= e($a['display_name'] ?? '—') ?> · <?= e(substr((string)$a['created_at'], 0, 10)) ?> · <?= icon('heart', 13) ?> <?= (int)$a['likes'] ?></div>
        </div>
        <div class="art-row-act">
          <a href="<?= BASE_URL ?>/admin/articles/edit?id=<?= (int)$a['id'] ?>" class="btn btn--xs btn--outline"><?= icon('pencil') ?> تعديل</a>
          <form action="<?= BASE_URL ?>/admin/articles/delete" method="POST" style="display:inline" data-confirm="حذف هذا المقال نهائيا؟" data-confirm-danger="1">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <button class="btn btn--xs btn--danger" type="submit"><?= icon('trash') ?> حذف</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
