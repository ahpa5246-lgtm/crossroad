<div class="two-col">
  <div class="acard">
    <div class="acard-header"><h3>المشتركون (<?= count($subscribers) ?>)</h3><a href="<?= BASE_URL ?>/admin/subscribers/export" class="btn btn--sm btn--outline"><?= icon('download') ?> TXT</a></div>
    <div class="table-wrap"><table class="atable">
      <thead><tr><th>#</th><th>البريد الإلكتروني</th><th>تاريخ الاشتراك</th></tr></thead>
      <tbody>
      <?php foreach ($subscribers as $i => $s): ?>
      <tr><td><?= $i+1 ?></td><td><?= e($s['email']) ?></td><td style="font-size:12px"><?= date('d/m/Y',strtotime($s['subscribed_at'])) ?></td></tr>
      <?php endforeach; ?>
      <?php if(empty($subscribers)): ?><tr><td colspan="3" class="empty-row">لا يوجد مشتركون.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="acard">
    <div class="acard-header"><h3><?= icon('mail') ?> رسالة للإعلام فقط</h3></div>
    <div class="acard-desc" style="margin-bottom:0">ملاحظة: نظام البريد الإلكتروني غير مهيأ حاليا. يمكن تصدير القائمة واستخدامها مع أي خدمة بريد خارجية.</div>
    <div class="acard-body">
      <div style="background:var(--sage-faint);border:1px solid var(--sage);border-radius:var(--r-sm);padding:14px;font-size:14px;color:var(--sage-dark);margin-bottom:16px">
        <?= icon('clipboard') ?> لديك <strong><?= count($subscribers) ?></strong> مشترك. صدر القائمة كـ TXT ثم استخدمها مع Mailchimp أو أي منصة بريد.
      </div>
      <a href="<?= BASE_URL ?>/admin/subscribers/export" class="btn btn--gold btn--full"><?= icon('download') ?> تحميل قائمة البريد (.txt)</a>
    </div>
  </div>
</div>
