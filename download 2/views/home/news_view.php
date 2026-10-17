<div class="article-page">
  <div class="container" style="max-width:800px">
    <a href="<?= BASE_URL ?>/news" class="article-back" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-muted);text-decoration:none;font-size:13px;margin-bottom:24px">
      ← الفعاليات
    </a>

    <?php if (!empty($item['category'])): ?>
    <div style="margin-bottom:10px">
      <a href="<?= BASE_URL ?>/news?cat=<?= urlencode($item['category']) ?>" class="badge badge--green" style="text-decoration:none;font-size:12px"><?= e($item['category']) ?></a>
    </div>
    <?php endif; ?>

    <h1 class="article-title" style="font-family:var(--font-display);font-size:clamp(22px,4vw,34px);color:var(--text-dark);line-height:1.3;margin-bottom:16px"><?= e($item['title']) ?></h1>

    <div style="display:flex;align-items:center;gap:16px;color:var(--text-muted);font-size:13px;margin-bottom:28px;flex-wrap:wrap">
      <span><?= icon('calendar') ?> <?= date('j F Y', strtotime($item['published_at'] ?? $item['created_at'])) ?></span>
      <span><?= icon('book-open') ?> <?= mb_strlen(strip_tags($item['description'] ?? '')) > 500 ? 'فعالية' : 'فعالية' ?></span>
    </div>

    <?php if ($item['cover_image']): ?>
    <div class="article-cover" style="border-radius:var(--r-lg);overflow:hidden;margin-bottom:32px;max-height:420px">
      <img src="<?= BASE_URL . e($item['cover_image']) ?>" alt="<?= e($item['title']) ?>" style="width:100%;height:100%;object-fit:cover">
    </div>
    <?php endif; ?>

    <div class="article-body" style="font-size:16px;line-height:1.85;color:var(--text-dark)">
      <?= nl2br(e($item['description'] ?? '')) ?>
    </div>

    <div style="border-top:1px solid var(--border);margin-top:40px;padding-top:24px;display:flex;gap:12px;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/news" class="btn btn--outline">← كل الفعاليات</a>
    </div>

    <?php if (!empty($related)): ?>
    <div style="margin-top:40px">
      <h3 style="font-family:var(--font-display);font-size:18px;color:var(--text-dark);margin-bottom:20px">فعاليات أخرى</h3>
      <div class="news-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
        <?php foreach ($related as $r): ?>
        <a href="<?= BASE_URL ?>/news/view?id=<?= $r['id'] ?>" class="news-card" style="text-decoration:none;color:inherit">
          <?php if ($r['cover_image']): ?>
          <div class="news-img-wrap" style="aspect-ratio:16/9;overflow:hidden;border-radius:var(--r-md) var(--r-md) 0 0">
            <img src="<?= BASE_URL . e($r['cover_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
          </div>
          <?php else: ?>
          <div style="background:var(--sage-faint);aspect-ratio:16/9;border-radius:var(--r-md) var(--r-md) 0 0;display:flex;align-items:center;justify-content:center;font-size:28px"><?= icon('newspaper') ?></div>
          <?php endif; ?>
          <div class="news-body">
            <h3 style="font-size:14px;margin-bottom:6px"><?= e($r['title']) ?></h3>
            <div style="font-size:11px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($r['published_at'] ?? $r['created_at'])) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
