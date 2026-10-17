<div class="page-hero">
  <div class="container">
    <h1><?= icon('newspaper') ?> الفعاليات</h1>
    <p style="color:rgba(255,255,255,.8)">آخر الفعاليات والإعلانات</p>
  </div>
</div>

<div class="section container">
  <!-- Search + Filter Bar -->
  <form method="GET" action="<?= BASE_URL ?>/news" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;align-items:center">
    <div style="position:relative;flex:1;min-width:200px">
      <input type="text" name="q" value="<?= e($search) ?>"
             placeholder="ابحث في الفعاليات..."
             class="form-input"
             style="padding-right:38px">
      <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none"><?= icon('search') ?></span>
    </div>
    <?php if (!empty($categories)): ?>
    <select name="cat" class="form-select" style="min-width:160px" class="auto-submit">
      <option value="">كل التصنيفات</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= e($c) ?>" <?= $activecat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <button class="btn btn--gold" type="submit">بحث</button>
    <?php if ($search || $activecat): ?>
    <a href="<?= BASE_URL ?>/news" class="btn btn--outline"><?= icon('x') ?> مسح</a>
    <?php endif; ?>
  </form>

  <?php if ($search || $activecat): ?>
  <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">
    <?= $total ?> نتيجة<?= $search ? ' لـ "' . e($search) . '"' : '' ?><?= $activecat ? ' في تصنيف "' . e($activecat) . '"' : '' ?>
  </p>
  <?php endif; ?>

  <?php if (empty($news)): ?>
  <div class="empty-card">
    <div style="font-size:48px;margin-bottom:16px"><?= icon('inbox') ?></div>
    <h3><?= $search || $activecat ? 'لا توجد نتائج' : 'لا توجد فعاليات بعد' ?></h3>
    <p><?= $search ? 'جرب كلمات بحث مختلفة.' : 'تابعونا قريبا.' ?></p>
    <?php if ($search || $activecat): ?>
    <a href="<?= BASE_URL ?>/news" class="btn btn--outline" style="margin-top:16px">عرض الكل</a>
    <?php endif; ?>
  </div>
  <?php else: ?>

  <!-- Magazine Grid -->
  <div class="news-grid">
    <?php foreach ($news as $i => $item): ?>
    <article class="news-card reveal reveal--d<?= ($i % 3) + 1 ?>">
      <div class="news-img">
        <?php if ($item['cover_image']): ?>
        <img src="<?= BASE_URL . e($item['cover_image']) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
        <?php else: ?>
        <div class="news-ph"><span style="font-size:40px;opacity:.4"><?= icon('newspaper') ?></span></div>
        <?php endif; ?>
        <?php if (!empty($item['category'])): ?>
        <span style="position:absolute;top:10px;right:10px;background:var(--sage-dark);color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px"><?= e($item['category']) ?></span>
        <?php endif; ?>
        <div class="news-date"><?= date('d M Y', strtotime($item['published_at'] ?? $item['created_at'])) ?></div>
      </div>
      <div class="news-body">
        <h3><?= e($item['title']) ?></h3>
        <?php if ($item['description']): ?>
        <p><?= e(mb_substr(strip_tags($item['description']), 0, 140)) ?>…</p>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/news/view?id=<?= $item['id'] ?>" class="news-link">اقرأ المزيد ←</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:40px">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_filter(['q' => $search, 'cat' => $activecat, 'page' => $page - 1])) ?>" class="btn btn--outline btn--sm">← السابق</a>
    <?php endif; ?>
    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
    <a href="?<?= http_build_query(array_filter(['q' => $search, 'cat' => $activecat, 'page' => $p])) ?>"
       class="btn btn--sm <?= $p === $page ? 'btn--gold' : 'btn--outline' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
    <a href="?<?= http_build_query(array_filter(['q' => $search, 'cat' => $activecat, 'page' => $page + 1])) ?>" class="btn btn--outline btn--sm">التالي →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>
