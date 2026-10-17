<div class="toolbar"><div></div><a href="<?= BASE_URL ?>/admin/challenges/new" class="btn btn--gold"><?= icon('plus') ?> تحدي جديد</a></div>
<div class="acard"><div class="table-wrap"><table class="atable">
  <thead><tr><th>الغلاف</th><th>العنوان</th><th>التصنيف</th><th>الحالة</th><th>أسئلة</th><th>نجاح</th><th>متمون</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($challenges as $ch): ?>
  <tr>
    <td><?php if($ch['cover_image']): ?><img src="<?= BASE_URL . e($ch['cover_image']) ?>" class="tbl-thumb"><?php else: ?><div class="tbl-thumb-empty"><?= icon('target') ?></div><?php endif; ?></td>
    <td><strong><?= e($ch['title']) ?></strong></td>
    <td><span class="badge badge--gold"><?= e($ch['category']) ?></span></td>
    <td><span class="badge badge--<?= $ch['status']==='published'?'green':($ch['status']==='draft'?'yellow':'gray') ?>"><?= ($ch['status']==='published'?(icon('circle-check') . ' منشور'):($ch['status']==='draft'?(icon('file-text') . ' مسودة'):(icon('package') . ' مؤرشف'))) ?></span></td>
    <td><?= $ch['q_count'] ?></td>
    <td><?= $ch['passing_score'] ?>%</td>
    <td><a href="<?= BASE_URL ?>/admin/challenges/completions?id=<?= $ch['id'] ?>"><?= $ch['passes'] ?> / <?= $ch['completions'] ?></a></td>
    <td class="tbl-actions">
      <a href="<?= BASE_URL ?>/admin/challenges/edit?id=<?= $ch['id'] ?>" class="btn btn--xs btn--outline">تعديل</a>
      <a href="<?= BASE_URL ?>/challenges/view?id=<?= $ch['id'] ?>" class="btn btn--xs btn--outline" target="_blank">عرض</a>
      <form method="POST" action="<?= BASE_URL ?>/admin/challenges/delete" style="display:inline" data-confirm="حذف التحدي وكل بياناته؟" data-confirm-title="حذف التحدي" data-confirm-ok="حذف" data-confirm-danger="1">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $ch['id'] ?>">
        <button type="submit" class="btn btn--xs btn--danger">حذف</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($challenges)): ?><tr><td colspan="8" class="empty-row">لا توجد تحديات. <a href="<?= BASE_URL ?>/admin/challenges/new">أنشئ واحدا</a></td></tr><?php endif; ?>
  </tbody>
</table></div></div>
