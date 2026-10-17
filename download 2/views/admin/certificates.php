<div class="toolbar"><div></div><a href="<?= BASE_URL ?>/admin/certificates/issue" class="btn btn--gold"><?= icon('award') ?> إصدار شهادة</a></div>
<div class="acard"><div class="table-wrap"><table class="atable">
  <thead><tr><th>الاسم</th><th>البريد</th><th>التيليغرام</th><th>الشهادة</th><th>البرنامج/التحدي</th><th>تاريخ الإصدار</th><th>الحالة</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($certs as $c): ?>
  <tr>
    <td><strong><?= e($c['display_name']) ?></strong></td>
    <td style="font-size:12px"><?= e($c['email']) ?></td>
    <td style="color:var(--sage-dark);font-size:13px"><?= $c['telegram_username']?e($c['telegram_username']):'—' ?></td>
    <td><?= e($c['title']) ?></td>
    <td style="font-size:12px"><?= $c['form_title']?e($c['form_title']):($c['challenge_title']?(icon('target') . ' ').e($c['challenge_title']):'—') ?></td>
    <td style="font-size:12px"><?= date('d/m/Y',strtotime($c['issued_at'])) ?></td>
    <td><span class="badge badge--<?= $c['is_valid']?'green':'red' ?>"><?= $c['is_valid']?(icon('circle-check') . ' سارية'):(icon('circle-x') . ' ملغاة') ?></span></td>
    <td class="tbl-actions">
      <a href="<?= BASE_URL ?>/certificates/download-pdf?token=<?= e($c['token']) ?>" class="btn btn--xs btn--gold"><?= icon('file-text') ?> PDF</a>
      <a href="<?= BASE_URL ?>/certificates/verify?token=<?= e($c['token']) ?>" class="btn btn--xs btn--outline" target="_blank">تحقق</a>
      <?php if($c['is_valid']): ?>
      <form method="POST" action="<?= BASE_URL ?>/admin/certificates/revoke" style="display:inline" data-confirm="إلغاء الشهادة؟" data-confirm-title="إلغاء الشهادة" data-confirm-ok="إلغاء" data-confirm-danger="1">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button type="submit" class="btn btn--xs btn--danger">إلغاء</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($certs)): ?><tr><td colspan="8" class="empty-row">لا توجد شهادات.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
