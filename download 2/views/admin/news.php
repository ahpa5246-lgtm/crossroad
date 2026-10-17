<div class="toolbar"><div></div><a href="<?= BASE_URL ?>/admin/news/new" class="btn btn--gold"><?= icon('plus') ?> فعالية جديدة</a></div>
<div class="acard"><div class="table-wrap"><table class="atable">
  <thead><tr><th>الغلاف</th><th>العنوان</th><th>الحالة</th><th>النشر</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($news as $n): ?>
  <tr>
    <td><?php if($n['cover_image']): ?><img src="<?= BASE_URL . e($n['cover_image']) ?>" class="tbl-thumb"><?php else: ?><div class="tbl-thumb-empty"><?= icon('newspaper') ?></div><?php endif; ?></td>
    <td><strong><?= e($n['title']) ?></strong></td>
    <td><span class="badge badge--<?= $n['is_published']?'green':'yellow' ?>"><?= $n['is_published']?(icon('circle-check') . ' منشور'):(icon('file-text') . ' مسودة') ?></span></td>
    <td style="font-size:12px"><?= $n['published_at']?date('d/m/Y',strtotime($n['published_at'])):'—' ?></td>
    <td class="tbl-actions">
      <a href="<?= BASE_URL ?>/admin/news/edit?id=<?= $n['id'] ?>" class="btn btn--xs btn--outline">تعديل</a>
      <?php if($n['is_published']): ?><a href="<?= BASE_URL ?>/news/view?id=<?= $n['id'] ?>" class="btn btn--xs btn--outline" target="_blank">عرض</a><?php endif; ?>
      <form method="POST" action="<?= BASE_URL ?>/admin/news/delete" style="display:inline" data-confirm="حذف الفعالية؟" data-confirm-title="حذف الفعالية" data-confirm-ok="حذف" data-confirm-danger="1">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $n['id'] ?>">
        <button type="submit" class="btn btn--xs btn--danger">حذف</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($news)): ?><tr><td colspan="5" class="empty-row">لا توجد فعاليات.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
