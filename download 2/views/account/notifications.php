<div class="page-hero"><div class="container"><h1><?= icon('bell') ?> الإشعارات</h1></div></div>
<div class="section container" style="max-width:720px">
  <?php if (empty($notifications)): ?>
  <div class="empty-card"><div style="font-size:48px;margin-bottom:16px"><?= icon('bell-off') ?></div><h3>لا توجد إشعارات بعد</h3><p>ستظهر هنا التحديثات المهمة.</p></div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:10px">
    <?php foreach ($notifications as $n):
      $icons=['certificate'=>'award','challenge'=>'target','program'=>'clipboard','news'=>'newspaper','system'=>'megaphone'];
      $ic=$icons[$n['type']]??'bell';
      $ago=time()-strtotime($n['created_at']);
      $timeAgo=$ago<60?'الآن':($ago<3600?floor($ago/60).'د':($ago<86400?floor($ago/3600).'س':floor($ago/86400).'ي'));
    ?>
    <div style="display:flex;gap:14px;padding:16px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--r-lg);<?= $n['is_read']?'opacity:.75':'' ?>">
      <div style="font-size:28px;flex-shrink:0;margin-top:2px"><?= icon($ic, 26) ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:14px;color:var(--text-dark)"><?= e($n['title']) ?></div>
        <?php if($n['message']): ?><div style="font-size:13px;color:var(--text-muted);margin-top:4px"><?= e($n['message']) ?></div><?php endif; ?>
        <?php if($n['link']): ?><a href="<?= BASE_URL.e($n['link']) ?>" style="font-size:12px;color:var(--sage-dark);margin-top:6px;display:inline-block">عرض ←</a><?php endif; ?>
      </div>
      <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;flex-shrink:0"><?= $timeAgo ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
