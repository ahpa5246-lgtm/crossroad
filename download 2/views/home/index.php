<?php
$_sections  = content_sections();
$_heroLines = array_values(array_filter(array_map('trim', explode("\n", str_replace('\n', "\n", setting('hero_title', brand_name()))))));
$_siteTitle = $_heroLines[0] ?? brand_name();
$_teamTitle = setting('team_title', 'الإدارة العليا');
$_rulesTitle= setting('rules_title', 'القوانين');
$_rules     = rules_items();
$_members   = team_members();
$_aboutImg  = setting('about_image', '');
$_achAll    = achievements_list();
$_ach       = array_slice($_achAll, 0, 3);
$_forms     = latest_open_form();
$_news      = [];
try { $_news = Database::getInstance()->query("SELECT a.id,a.title,a.description,a.cover_image,a.created_at,u.display_name,u.username,u.avatar FROM articles a JOIN users u ON u.id=a.author_id ORDER BY a.created_at DESC LIMIT 3")->fetchAll(); } catch (Throwable $e) {}
$_cl        = contact_links();

/* hero = the fixed pixel sunrise (latest achievements live in their own section below) */
$_arrow = '<button type="button" class="scroll-arrow" data-next aria-label="التالي">' . icon('chevron-down', 26) . '</button>';
?>
<!-- ═════ العنوان + الكاروسيل ═════ -->
<section class="fs stage" id="top">
  <h1 class="site-title"><?= e($_siteTitle) ?></h1>

  <div class="car car--single" aria-hidden="true">
    <div class="car-track">
      <div class="car-slide is-active"><canvas class="px-scene" data-sun="0.5"></canvas></div>
    </div>
  </div>
  <div class="stage-cta">
    <a href="<?= BASE_URL ?>/apply" class="btn btn--gold btn--lg">التقديم</a>
    <?php if (!auth()): ?><a href="<?= BASE_URL ?>/auth/register" class="btn btn--outline btn--lg">إنشاء حساب</a><?php endif; ?>
  </div>
  <?php if ($_forms): ?>
  <div class="open-strips">
    <?php foreach ([$_forms] as $f): ?>
    <a class="open-strip" href="<?= BASE_URL ?>/apply/form?id=<?= (int)$f['id'] ?>">
      <span class="open-dot" aria-hidden="true"></span>
      <span class="open-lbl">التقديم مفتوح</span>
      <span class="open-title"><?= e($f['title']) ?></span>
      <?= icon('arrow-left', 16) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?= $_arrow ?>
</section>

<!-- ═════ الشاشة الثانية: من نحن + كيف تبدأ ═════ -->
<section class="fs flow">
<!-- من نحن -->
<?php foreach ($_sections as $i => $sec): ?>
<div class="blk about" <?= $i === 0 ? 'id="about"' : '' ?>>
  <div class="container">
    <?php if ($sec['title'] !== ''): ?><h2 class="plate"><span><?= e($sec['title']) ?></span></h2><?php endif; ?>
    <?php if ($i === 0): ?>
    <div class="about-row about-row--solo">
      <article class="about-card">
        <div class="about-avatar"><?php if ($_aboutImg): ?><img src="<?= BASE_URL . e($_aboutImg) ?>" alt=""><?php else: ?><?= pixel_avatar_svg($sec['title'] . 'about', 90) ?><?php endif; ?></div>
        <div class="story-text"><?= render_rich_text($sec['body']) ?></div>
      </article>
    </div>
    <?php else: ?>
    <article class="about-card about-card--wide"><div class="story-text"><?= render_rich_text($sec['body']) ?></div></article>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>


</section>

<!-- ═════ الشاشة الثالثة: الإنجازات + المجلة ═════ -->
<section class="fs flow flow--prev">
<?php if ($_ach): ?>
<div class="blk">
  <div class="container">
    <div class="blk-head"><h2 class="plate"><span><?= e(setting('achievements_title', 'الإنجازات')) ?></span></h2><a href="<?= BASE_URL ?>/achievements" class="btn btn--sm btn--outline">عرض الكل <?= icon('arrow-left') ?></a></div>
    <div class="pv-grid">
      <?php foreach ($_ach as $a): ?>
      <a class="pv-card" href="<?= BASE_URL ?>/achievements">
        <div class="pv-img"><?php if ($a['images']): ?><img src="<?= BASE_URL . e($a['images'][0]) ?>" alt="" loading="lazy"><?php else: ?><span><?= icon('trophy', 48) ?></span><?php endif; ?></div>
        <div class="pv-body">
          <?php if ($a['when_text'] !== ''): ?><span class="ach-when"><?= e($a['when_text']) ?></span><?php endif; ?>
          <h3><?= e($a['title']) ?></h3>
          <?php if ($a['body'] !== ''): ?><p><?= e(mb_substr(strip_tags(str_replace(['**', "\n"], ['', ' '], $a['body'])), 0, 90)) ?>…</p><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($_news): ?>
<div class="blk">
  <div class="container">
    <div class="blk-head"><h2 class="plate"><span>المجلة</span></h2><a href="<?= BASE_URL ?>/magazine" class="btn btn--sm btn--outline">عرض الكل <?= icon('arrow-left') ?></a></div>
    <div class="pv-grid">
      <?php foreach ($_news as $n): ?>
      <a class="pv-card" href="<?= BASE_URL ?>/magazine/view?id=<?= (int)$n['id'] ?>">
        <div class="pv-img"><?php if ($n['cover_image']): ?><img src="<?= BASE_URL . e($n['cover_image']) ?>" alt="" loading="lazy"><?php else: ?><span><?= icon('newspaper', 48) ?></span><?php endif; ?></div>
        <div class="pv-body">
          <h3><?= e($n['title']) ?></h3>
          <?php if ($n['description'] !== ''): ?><p><?= e(mb_substr($n['description'], 0, 90)) ?>…</p><?php endif; ?>
          <div class="au-row" style="margin-top:10px"><?= user_avatar_html($n, 28) ?><span class="au-name"><?= e($n['display_name']) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>
</section>

<!-- ═════ الشاشة الرابعة: الإدارة + القوانين + تواصل ═════ -->
<section class="fs flow flow--prev">
<!-- ═════ الإدارة العليا ═════ -->
<?php if ($_members): ?>
<div class="blk team-sec" id="team">
  <div class="container">
    <h2 class="plate"><span><?= e($_teamTitle) ?></span></h2>
    <div class="tcards">
      <?php foreach ($_members as $m): $desc = trim((string)($m['bio'] ?? '')); ?>
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
</div>
<?php endif; ?>
<!-- ═════ القوانين ═════ -->
<?php if ($_rules): ?>
<div class="blk rules-sec" id="rules">
  <div class="container">
    <h2 class="plate"><span><?= e($_rulesTitle) ?></span></h2>
    <div class="rules-grid">
      <?php foreach ($_rules as $i => $r): ?>
      <article class="rule">
        <div class="rule-n"><?= ar_digits($i + 1) ?></div>
        <div class="rule-body">
          <?php if ($r['title'] !== ''): ?><h3><?= e($r['title']) ?></h3><?php endif; ?>
          <?php if ($r['body'] !== ''): ?><div class="story-text"><?= render_rich_text($r['body']) ?></div><?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="blk">
  <div class="container">
    <div class="blk-head"><h2 class="plate"><span>تواصل معنا</span></h2><a href="<?= BASE_URL ?>/contact" class="btn btn--sm btn--outline">الصفحة كاملة <?= icon('arrow-left') ?></a></div>
    <div class="panel contact-panel contact-panel--row">
      <div class="contact-btns contact-btns--row">
        <?php if ($_cl['instagram']): ?><a class="contact-btn contact-btn--ig" href="<?= e($_cl['instagram']) ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>صفحتنا على إنستغرام</span></a><?php endif; ?>
        <?php if ($_cl['telegram']): ?><a class="contact-btn contact-btn--tg" href="<?= e($_cl['telegram']) ?>" target="_blank" rel="noopener"><i class="fab fa-telegram"></i><span>قناتنا على تليجرام</span></a><?php endif; ?>
        <?php if (!$_cl['instagram'] && !$_cl['telegram']): ?><a class="contact-btn contact-btn--mail" href="<?= BASE_URL ?>/contact"><?= icon('mail', 26) ?><span>تواصل معنا</span></a><?php endif; ?>
      </div>
    </div>
  </div>
</div>
</section>

<script src="<?= BASE_URL ?>/public/js/home.js?v=<?= @filemtime(ROOT_PATH . '/public/js/home.js') ?>" nonce="<?= csp_nonce() ?>" defer></script>
