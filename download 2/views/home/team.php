<section class="page-pix">
  <div class="container pp-wrap">
    <h2 class="plate"><span><?= e(setting('team_title', 'الإدارة العليا')) ?></span></h2>
    <?php if (!$members): ?><div class="panel pp-empty">لم تتم إضافة أعضاء بعد.</div><?php endif; ?>
    <div class="tcards">
      <?php foreach ($members as $m): $desc = trim((string)($m['bio'] ?? '')); ?>
      <article class="tcard">
        <div class="tcard-pic"><?php if ($m['avatar']): ?><img src="<?= BASE_URL . e($m['avatar']) ?>" alt="<?= e($m['name']) ?>" loading="lazy"><?php else: ?><?= pixel_avatar_svg($m['name'] . $m['role'], 160) ?><?php endif; ?></div>
        <h3 class="tcard-name"><?= e($m['name'] !== '' ? $m['name'] : $m['role']) ?></h3>
        <?php if ($m['name'] !== '' && $m['role'] !== ''): ?><div class="tcard-role"><?= e($m['role']) ?></div><?php endif; ?>
        <?php if ($desc !== ''): ?><p class="tcard-bio"><?= nl2br(e($desc)) ?></p><?php endif; ?>
        <div class="member-social">
          <?php foreach (['facebook' => 'fa-facebook-f', 'twitter' => 'fa-twitter', 'instagram' => 'fa-instagram', 'discord' => 'fa-discord', 'telegram' => 'fa-telegram'] as $k => $fa): if (!empty($m[$k])): ?>
            <a href="<?= e($m[$k]) ?>" target="_blank" rel="noopener" aria-label="<?= $k ?>"><i class="fab <?= $fa ?>"></i></a>
          <?php endif; endforeach; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
