<div class="page-hero">
  <div class="container">
    <h1>التحديات</h1>
    <p>اختبر معلوماتك واحصل على شهادة عند النجاح</p>
  </div>
</div>

<div class="section container">
  <!-- Category filter -->
  <?php if (!empty($categories)): ?>
  <div class="cat-filters">
    <a href="<?= BASE_URL ?>/challenges" class="cat-btn <?= !$activeCat ? 'active' : '' ?>">الكل</a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= BASE_URL ?>/challenges?cat=<?= urlencode($cat) ?>" class="cat-btn <?= $activeCat === $cat ? 'active' : '' ?>"><?= e($cat) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($challenges)): ?>
  <div class="empty-card">
    <div style="font-size:48px;margin:0 auto 16px;text-align:center"><?= icon('target') ?></div>
    <h3>لا توجد تحديات متاحة حاليا</h3>
    <p>تابعونا لإطلاق التحديات قريبا.</p>
  </div>
  <?php else: ?>
  <div class="challenges-grid">
    <?php foreach ($challenges as $i => $ch):
      $att = $userAttempts[$ch['id']] ?? null;
    ?>
    <div class="ch-card reveal reveal--d<?= ($i%3)+1 ?>">
      <?php if ($ch['cover_image']): ?>
      <div class="ch-card-img"><img src="<?= BASE_URL . e($ch['cover_image']) ?>" alt="<?= e($ch['title']) ?>" loading="lazy"></div>
      <?php endif; ?>
      <div class="ch-card-body">
        <div class="ch-card-top">
          <span class="badge badge--gold"><?= e($ch['category']) ?></span>
          <?php if ($att): ?>
            <?php if ($att['status'] === 'completed'): ?>
              <span class="badge badge--<?= $att['passed'] ? 'green' : 'red' ?>"><?= $att['passed'] ? (icon('circle-check') . ' نجحت') : (icon('circle-x') . ' لم تنجح') ?></span>
            <?php else: ?>
              <span class="badge badge--yellow"><?= icon('hourglass') ?> في التقدم</span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <h3><?= e($ch['title']) ?></h3>
        <?php if ($ch['description']): ?><p class="ch-desc"><?= e(mb_substr($ch['description'],0,120)) ?>…</p><?php endif; ?>
        <div class="ch-meta">
          <span><?= icon('target') ?> <?= $ch['q_count'] ?> سؤال</span>
          <span><?= icon('circle-check') ?> نجاح: <?= $ch['passing_score'] ?>%</span>
          <?php if ($ch['time_limit']): ?><span><?= icon('clock') ?> <?= $ch['time_limit'] ?> د</span><?php endif; ?>
          <span><?= icon('users') ?> <?= $ch['completions'] ?> أتمه</span>
        </div>

        <!-- Progress bar for in-progress -->
        <?php if ($att && $att['status'] === 'in_progress'): ?>
        <div class="ch-progress">
          <div class="ch-progress-bar" style="width:<?= challenge_progress_pct($att) ?>%"></div>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;margin-bottom:8px">تقدمك: <?= challenge_progress_pct($att) ?>%</div>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/challenges/<?= $att && $att['status']==='completed' ? 'result' : 'view' ?>?id=<?= $ch['id'] ?>" class="btn btn--gold btn--full">
          <?php if (!$att): ?>ابدأ التحدي →
          <?php elseif ($att['status']==='in_progress'): ?>متابعة التحدي →
          <?php else: ?>عرض النتيجة <?php endif; ?>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
