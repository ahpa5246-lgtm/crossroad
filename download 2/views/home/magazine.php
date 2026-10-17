<section class="page-pix">
  <div class="container pp-wrap">
    <div class="blk-head">
      <h2 class="plate"><span>المجلة</span></h2>
      <?php if (is_volunteer()): ?><a href="<?= BASE_URL ?>/magazine/write" class="btn btn--sm btn--gold"><?= icon('pencil') ?> كتابة مقال</a><?php endif; ?>
    </div>
    <?php if ($e = flash('error')): ?><div class="alert alert--error"><?= e($e) ?></div><?php endif; ?>
    <?php if ($s = flash('success')): ?><div class="alert alert--success"><?= e($s) ?></div><?php endif; ?>
    <?php if (!$articles): ?><div class="panel pp-empty">لا توجد مقالات بعد.</div><?php endif; ?>
    <div class="mag-list">
      <?php foreach ($articles as $a): ?>
      <a class="mag-item" href="<?= BASE_URL ?>/magazine/view?id=<?= (int)$a['id'] ?>">
        <div class="mag-cover"><?php if ($a['cover_image']): ?><img src="<?= BASE_URL . e($a['cover_image']) ?>" alt="" loading="lazy"><?php else: ?><span><?= icon('newspaper', 44) ?></span><?php endif; ?></div>
        <div class="mag-body">
          <h3><?= e($a['title']) ?></h3>
          <p><?= e($a['description']) ?></p>
          <div class="au-row">
            <?= user_avatar_html($a, 34) ?>
            <span class="au-name"><?= e($a['display_name']) ?></span>
            <span class="au-sep">·</span><span class="au-date"><?= date('d / m / Y', strtotime($a['created_at'])) ?></span>
            <span class="au-likes"><?= icon('heart') ?> <?= (int)$a['likes'] ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
