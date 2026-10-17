<div style="display:flex;flex-direction:column;gap:16px">

  <!-- New announcement -->
  <div class="acard">
    <div class="acard-header">
      <h3><?= icon('megaphone') ?> إعلان جديد</h3>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/admin/announcements/save" class="acard-body">
      <?= csrf_field() ?>
      <div class="form-stack">
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">العنوان</label>
            <input type="text" name="title" class="form-input" required placeholder="عنوان الإعلان...">
          </div>
          <div class="form-group">
            <label class="form-label">النوع</label>
            <select name="type" class="form-select">
              <option value="info">معلومة</option>
              <option value="plan">خطة مستقبلية</option>
              <option value="warning">تنبيه</option>
              <option value="alert">تنبيه عاجل</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">المحتوى</label>
          <textarea name="body" class="form-input" rows="4" required placeholder="تفاصيل الإعلان..."></textarea>
        </div>
        <label class="check-label">
          <input type="checkbox" name="is_pinned" value="1">
          <span><?= icon('pin') ?> تثبيت في الأعلى</span>
        </label>
        <button class="btn btn--gold"><?= icon('megaphone') ?> نشر الإعلان</button>
      </div>
    </form>
  </div>

  <!-- Existing announcements -->
  <?php if (empty($announcements)): ?>
  <div class="acard"><div class="acard-body" style="text-align:center;color:var(--tm);padding:40px">لا توجد إعلانات بعد.</div></div>
  <?php else: ?>
  <?php foreach ($announcements as $a):
    $colors = ['info'=>'#df7630','plan'=>'#27ae60','warning'=>'#e67e22','alert'=>'#e74c3c'];
    $icons  = ['info'=>'message','plan'=>'calendar','warning'=>'alert','alert'=>'siren'];
    $labels = ['info'=>'معلومة','plan'=>'خطة','warning'=>'تنبيه','alert'=>'عاجل'];
    $c = $colors[$a['type']] ?? '#666';
  ?>
  <div class="acard" style="border-right:4px solid <?= $c ?>">
    <div class="acard-header">
      <div style="display:flex;align-items:center;gap:8px">
        <?php if($a['is_pinned']): ?><span title="مثبت"><?= icon('pin') ?></span><?php endif; ?>
        <span><?= icon($icons[$a['type']]) ?></span>
        <strong><?= e($a['title']) ?></strong>
        <span style="font-size:11px;padding:2px 8px;border-radius:99px;background:<?= $c ?>22;color:<?= $c ?>;font-weight:700"><?= $labels[$a['type']] ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:12px;color:var(--tm)"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></span>
        <form method="POST" action="<?= BASE_URL ?>/admin/announcements/delete" style="display:inline"
              data-confirm="حذف هذا الإعلان؟" data-confirm-title="حذف الإعلان" data-confirm-ok="حذف" data-confirm-danger="1">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <button class="btn btn--xs btn--danger"><?= icon('trash') ?></button>
        </form>
      </div>
    </div>
    <div class="acard-body" style="white-space:pre-wrap;font-size:13px;line-height:1.7;color:var(--tb)"><?= e($a['body']) ?></div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
