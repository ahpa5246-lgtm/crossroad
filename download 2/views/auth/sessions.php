<div class="page-wrap">
  <h1 class="page-title">الجلسات النشطة</h1>
  <p class="page-sub">إدارة أجهزتك المسجل دخولها. يمكنك إلغاء أي جلسة لا تعرفها.</p>
  <?php if ($s=flash('success')): ?><div class="alert alert--success" style="margin-bottom:16px"><?= e($s) ?></div><?php endif; ?>
  <div style="display:flex;flex-direction:column;gap:10px">
    <?php $cur = $_SESSION['session_id'] ?? ''; ?>
    <?php foreach ($sessions as $s): ?>
    <div class="session-item <?= $s['id']===$cur?'session-item--current':'' ?>">
      <div style="font-size:26px"><?= str_contains($s['user_agent']??'','Mobile')?(icon('phone')):(icon('laptop')) ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <?= e(mb_substr($s['user_agent']??'جهاز غير معروف',0,80)) ?>
          <?php if ($s['id']===$cur): ?><span class="badge badge--green">الحالية</span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">IP: <?= e($s['ip_address']??'—') ?> · <?= e($s['last_active']) ?></div>
      </div>
      <?php if ($s['id']!==$cur): ?>
      <form action="<?= BASE_URL ?>/auth/revoke-session" method="POST">
        <?= csrf_field() ?><input type="hidden" name="session_id" value="<?= e($s['id']) ?>">
        <button class="btn btn--danger btn--sm" onclick="return confirm('إلغاء هذه الجلسة؟')">إلغاء</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (empty($sessions)): ?><div class="empty-card"><h3>لا توجد جلسات</h3></div><?php endif; ?>
  </div>
  <div style="margin-top:24px"><a href="<?= BASE_URL ?>/" class="btn btn--outline">← الرئيسية</a></div>
</div>
