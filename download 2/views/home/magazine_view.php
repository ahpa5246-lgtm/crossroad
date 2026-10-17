<?php $_me = auth(); $_own = $_me && (int)$_me['id'] === (int)$a['author_id']; $_nextQ = rawurlencode('/magazine/view?id=' . (int)$a['id']); ?>
<section class="page-pix">
  <div class="container pp-wrap pp-article">
    <a href="<?= BASE_URL ?>/magazine" class="back-link">← المجلة</a>
    <?php if ($e = flash('error')): ?><div class="alert alert--error"><?= e($e) ?></div><?php endif; ?>
    <?php if ($s = flash('success')): ?><div class="alert alert--success"><?= e($s) ?></div><?php endif; ?>
    <article class="panel art">
      <?php if ($a['cover_image']): ?><div class="art-cover"><img src="<?= BASE_URL . e($a['cover_image']) ?>" alt=""></div><?php endif; ?>
      <h1 class="art-title"><?= e($a['title']) ?></h1>
      <div class="au-row au-row--lg">
        <?= user_avatar_html($a, 48) ?>
        <div class="au-meta"><span class="au-name"><?= e($a['display_name']) ?></span><span class="au-date"><?= date('d / m / Y', strtotime($a['created_at'])) ?></span></div>
      </div>
      <div class="story-text art-body"><?= render_rich_text($a['body']) ?></div>

      <div class="art-actions">
        <?php if ($_me): ?>
        <button type="button" class="like-btn<?= $liked ? ' is-on' : '' ?>" id="likeBtn" data-id="<?= (int)$a['id'] ?>" data-csrf="<?= csrf_token() ?>" data-url="<?= BASE_URL ?>/magazine/like"><?= icon('heart') ?><span id="likeCount"><?= (int)$a['likes'] ?></span></button>
        <?php else: ?>
        <a class="like-btn" href="<?= BASE_URL ?>/auth/login?next=<?= $_nextQ ?>" title="سجل دخولك للإعجاب"><?= icon('heart') ?><span><?= (int)$a['likes'] ?></span></a>
        <?php endif; ?>
        <?php if ($_own): ?><a href="<?= BASE_URL ?>/magazine/edit?id=<?= (int)$a['id'] ?>" class="btn btn--sm btn--outline"><?= icon('pencil') ?> تعديل</a><?php endif; ?>
        <?php if ($_own || is_admin()): ?>
        <form action="<?= BASE_URL ?>/magazine/delete" method="POST" style="display:inline" data-confirm="حذف هذا المقال؟">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <button type="submit" class="btn btn--sm btn--danger"><?= icon('trash') ?> حذف</button>
        </form>
        <?php endif; ?>
      </div>
    </article>
  </div>
</section>
<script src="<?= BASE_URL ?>/public/js/magazine.js?v=<?= @filemtime(ROOT_PATH . '/public/js/magazine.js') ?>" nonce="<?= csp_nonce() ?>" defer></script>
