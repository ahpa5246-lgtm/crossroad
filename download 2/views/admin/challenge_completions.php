<div class="toolbar">
  <form method="GET" class="filter-form">
    <select name="id" class="form-select" class="auto-submit">
      <option value="">كل التحديات</option>
      <?php foreach ($challenges as $ch): ?><option value="<?= $ch['id'] ?>" <?= $filterId==$ch['id']?'selected':'' ?>><?= e($ch['title']) ?></option><?php endforeach; ?>
    </select>
  </form>
  <span style="font-size:13px;color:var(--tm)"><?= count($completions) ?> ناجح</span>
</div>
<?php if ($filterCh): ?><div style="background:var(--sage-faint);border:1px solid var(--sage);border-radius:var(--r-md);padding:14px 18px;margin-bottom:16px"><strong><?= e($filterCh['title']) ?></strong> · نسبة النجاح المطلوبة: <?= $filterCh['passing_score'] ?>%</div><?php endif; ?>
<div class="acard">
  <div class="table-wrap"><table class="atable">
    <thead><tr><th>#</th><th>الاسم</th><th>المستخدم</th><th>البريد</th><th>التيليغرام</th><th>التحدي</th><th>النتيجة</th><th>تاريخ الإنجاز</th><th>شهادة</th></tr></thead>
    <tbody>
    <?php foreach ($completions as $i => $c): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><strong><?= e($c['display_name']) ?></strong></td>
      <td><code>@<?= e($c['username']) ?></code></td>
      <td style="font-size:12px"><?= e($c['email']) ?></td>
      <td style="color:var(--sage-dark);font-size:13px"><?= $c['telegram_username']?e($c['telegram_username']):'—' ?></td>
      <td><?= e($c['ch_title']) ?></td>
      <td><strong style="color:var(--sage-dark)"><?= round($c['percentage'],1) ?>%</strong> <span style="font-size:11px;color:var(--tm)">(<?= $c['score'] ?>/<?= $c['max_score'] ?>)</span></td>
      <td style="font-size:12px"><?= $c['completed_at']?date('d/m/Y H:i',strtotime($c['completed_at'])):'—' ?></td>
      <td><a href="<?= BASE_URL ?>/admin/certificates/issue?user_id=<?= $c['user_id'] ?>&challenge_id=<?= $c['challenge_id'] ?>" class="btn btn--xs btn--gold"><?= icon('award') ?> إصدار</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($completions)): ?><tr><td colspan="9" class="empty-row">لا يوجد ناجحون.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
