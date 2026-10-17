<div class="toolbar">
  <form method="GET" action="<?= BASE_URL ?>/admin/accepted" class="filter-form">
    <select name="form_id" class="form-select" class="auto-submit">
      <option value="">كل النماذج</option>
      <?php foreach ($forms as $f): ?><option value="<?= $f['id'] ?>" <?= $formId==$f['id']?'selected':'' ?>><?= e($f['title']) ?></option><?php endforeach; ?>
    </select>
    <?php if ($formId > 0): ?>
    <a href="<?= BASE_URL ?>/admin/submissions?form_id=<?= $formId ?>&status=pending" class="btn btn--xs btn--outline" target="_blank" title="عرض الطلبات المعلقة لهذا النموذج"><?= icon('clock') ?> معلق</a>
    <a href="<?= BASE_URL ?>/admin/submissions?form_id=<?= $formId ?>&status=rejected" class="btn btn--xs btn--outline" target="_blank" title="عرض الطلبات المرفوضة لهذا النموذج"><?= icon('circle-x') ?> مرفوض</a>
    <?php endif; ?>
  </form>
  <span style="font-size:13px;color:var(--tm)"><?= count($submissions) ?> مقبول</span>
</div>
<div class="acard"><div class="table-wrap"><table class="atable">
  <thead><tr><th>الاسم</th><th>المستخدم</th><th>البريد</th><th>التيليغرام</th><th>النموذج</th><th>تاريخ القبول</th><th>إصدار شهادة</th></tr></thead>
  <tbody>
  <?php foreach ($submissions as $s): ?>
  <tr>
    <td><strong><?= e($s['display_name']??'زائر') ?></strong></td>
    <td><?= $s['username']?'@'.e($s['username']):'—' ?></td>
    <td style="font-size:12px"><?= e($s['user_email']??'—') ?></td>
    <td style="color:var(--sage-dark)"><?= $s['telegram_username']?e($s['telegram_username']):'—' ?></td>
    <td><?= e($s['form_title']??'—') ?></td>
    <td style="font-size:12px"><?= $s['reviewed_at']?date('d/m/Y',strtotime($s['reviewed_at'])):'—' ?></td>
    <td><?php if($s['user_id']): ?><a href="<?= BASE_URL ?>/admin/certificates/issue?user_id=<?= $s['user_id'] ?>&form_id=<?= $s['form_id'] ?>&sub_id=<?= $s['id'] ?>" class="btn btn--xs btn--gold"><?= icon('award') ?> إصدار</a><?php else: ?><span style="color:var(--tm);font-size:12px">زائر</span><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($submissions)): ?><tr><td colspan="7" class="empty-row">لا يوجد مقبولون.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
