<section class="page-pix">
  <div class="container pp-wrap pp-narrow">
    <h2 class="plate"><span>تواصل معنا</span></h2>
    <div class="panel contact-panel">
      <?php if (setting('contact_text', '') !== ''): ?><div class="story-text"><?= render_rich_text(setting('contact_text')) ?></div><?php endif; ?>
      <div class="contact-btns">
        <?php if ($links['instagram']): ?>
        <a class="contact-btn contact-btn--ig" href="<?= e($links['instagram']) ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>صفحتنا على إنستغرام</span></a>
        <?php endif; ?>
        <?php if ($links['telegram']): ?>
        <a class="contact-btn contact-btn--tg" href="<?= e($links['telegram']) ?>" target="_blank" rel="noopener"><i class="fab fa-telegram"></i><span>قناتنا على تليجرام</span></a>
        <?php endif; ?>
        <?php if ($links['email']): ?>
        <a class="contact-btn contact-btn--mail" href="mailto:<?= e($links['email']) ?>"><?= icon('mail', 26) ?><span><?= e($links['email']) ?></span></a>
        <?php endif; ?>
      </div>
      <?php if (!$links['instagram'] && !$links['telegram'] && !$links['email']): ?><p class="pp-empty">لم تتم إضافة روابط التواصل بعد.</p><?php endif; ?>
    </div>
  </div>
</section>
