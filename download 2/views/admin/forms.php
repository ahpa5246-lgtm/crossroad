<div class="toolbar"><div></div><a href="<?= BASE_URL ?>/admin/forms/new" class="btn btn--gold"><?= icon('plus') ?> نموذج جديد</a></div>
<div class="acard"><div class="table-wrap"><table class="atable">
  <thead><tr><th>الغلاف</th><th>العنوان</th><th>الحالة</th><th>أسئلة</th><th>الكل</th><th>معلق</th><th>مقبول</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($forms as $f): ?>
  <tr>
    <td><?php if($f['cover_image']): ?><img src="<?= BASE_URL . e($f['cover_image']) ?>" class="tbl-thumb"><?php else: ?><div class="tbl-thumb-empty"><?= icon('clipboard') ?></div><?php endif; ?></td>
    <td><strong><?= e($f['title']) ?></strong></td>
    <td><span class="badge badge--<?= $f['status']==='open'?'green':'gray' ?>"><?= $f['status']==='open'?(icon('dot') . ' مفتوح'):(icon('dot') . ' مغلق') ?></span></td>
    <td><?= $f['q_count'] ?></td>
    <td><a href="<?= BASE_URL ?>/admin/submissions?form_id=<?= $f['id'] ?>"><?= $f['s_count'] ?></a></td>
    <td><span style="color:var(--gold);font-weight:700"><?= $f['pending_c'] ?></span></td>
    <td><span style="color:#27ae60;font-weight:700"><?= $f['accepted_c'] ?></span></td>
    <td class="tbl-actions">
      <a href="<?= BASE_URL ?>/admin/forms/edit?id=<?= $f['id'] ?>" class="btn btn--xs btn--outline">تعديل</a>
      <form method="POST" action="<?= BASE_URL ?>/admin/forms/toggle" style="display:inline">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $f['id'] ?>">
        <button type="submit" class="btn btn--xs <?= $f['status']==='open'?'btn--danger':'btn--green' ?>"><?= $f['status']==='open'?'إغلاق':'فتح' ?></button>
      </form>
      <form method="POST" action="<?= BASE_URL ?>/admin/forms/delete" style="display:inline" data-confirm="حذف/إغلاق النموذج؟" data-confirm-title="تأكيد النموذج" data-confirm-ok="متابعة" data-confirm-danger="1">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $f['id'] ?>">
        <button type="submit" class="btn btn--xs btn--danger">حذف</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($forms)): ?><tr><td colspan="8" class="empty-row">لا توجد نماذج. <a href="<?= BASE_URL ?>/admin/forms/new">أنشئ واحدا</a></td></tr><?php endif; ?>
  </tbody>
</table></div></div>
