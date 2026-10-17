<section class="page-pix">
  <div class="container pp-wrap">
    <h2 class="plate"><span>التقديم</span></h2>
    <p class="pp-lead"><?= auth() ? 'استعرض البرامج المفتوحة وقدّم طلبك.' : 'استعرض البرامج المفتوحة وما فيها. التقديم يحتاج تسجيل الدخول أو إنشاء حساب.' ?></p>
    <?php if ($s = flash('success')): ?><div class="alert alert--success" style="margin-bottom:16px"><?= e($s) ?></div><?php endif; ?>
    <?php if ($er = flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($er) ?></div><?php endif; ?>

    <?php if (empty($forms)): ?>
    <div class="panel pp-empty pp-empty--big">
      <div class="pp-empty-ic"><?= icon('clipboard', 44) ?></div>
      <h3>لا توجد برامج مفتوحة حاليا</h3>
      <p>اشترك في القائمة البريدية لتصلك إشعارات فور فتح التقديم.</p>
    </div>
    <?php else: ?>
    <div class="mag-list">
      <?php foreach ($forms as $form): ?>
      <article class="mag-item">
        <div class="mag-cover">
          <?php if (!empty($form['cover_image'])): ?><img src="<?= BASE_URL . e($form['cover_image']) ?>" alt="<?= e($form['title']) ?>" loading="lazy"><?php else: ?><?= icon('clipboard') ?><?php endif; ?>
        </div>
        <div class="mag-body">
          <div class="ach-head"><h3><?= e($form['title']) ?></h3><span class="ach-when ach-when--open">مفتوح</span></div>
          <?php if (!empty($form['description'])): ?><p><?= nl2br(e($form['description'])) ?></p><?php endif; ?>
          <div style="margin-top:14px">
            <?php if ($form['prerequisite_met'] ?? true): ?>
            <a href="<?= BASE_URL ?>/apply/form?id=<?= (int)$form['id'] ?>" class="btn btn--gold"><?= auth() ? 'تقديم على هذا البرنامج' : 'عرض الفورم' ?></a>
            <?php else: ?>
            <div class="pp-lock"><?= icon('lock') ?> متطلب مسبق: يجب قبولك في برنامج سابق للتقديم على هذا البرنامج.</div>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
