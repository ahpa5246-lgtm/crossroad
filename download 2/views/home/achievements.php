<section class="page-pix">
  <div class="container pp-wrap">
    <h2 class="plate"><span><?= e(setting('achievements_title', 'الإنجازات')) ?></span></h2>
    <?php if (!$items): ?>
      <div class="panel pp-empty">لم تتم إضافة إنجازات بعد.</div>
    <?php endif; ?>
    <div class="ach-list">
      <?php foreach ($items as $a): ?>
      <article class="ach">
        <?php if ($a['images']): ?>
        <div class="ach-imgs">
          <?php foreach ($a['images'] as $im): ?>
          <a href="<?= BASE_URL . e($im) ?>" target="_blank" rel="noopener" class="ach-img"><img src="<?= BASE_URL . e($im) ?>" alt="<?= e($a['title']) ?>" loading="lazy"></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="ach-body">
          <div class="ach-head"><h3><?= e($a['title']) ?></h3><?php if ($a['when_text'] !== ''): ?><span class="ach-when"><?= e($a['when_text']) ?></span><?php endif; ?></div>
          <?php if ($a['body'] !== ''): ?><div class="story-text"><?= render_rich_text($a['body']) ?></div><?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
