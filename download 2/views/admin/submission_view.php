<?php $answers = json_decode($submission['answers'] ?? '{}', true) ?: []; ?>

<div style="max-width:720px">

  <!-- Back -->
  <a href="<?= BASE_URL ?>/admin/submissions?form_id=<?= $submission['form_id'] ?>" class="btn btn--outline btn--sm" style="margin-bottom:16px">← عودة</a>

  <!-- Status bar -->
  <div class="acard" style="margin-bottom:16px">
    <div class="acard-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div>
        <div style="font-size:12px;color:var(--tm)">المتقدم</div>
        <div style="font-weight:700"><?= e($submission['display_name']) ?> <span style="color:var(--tm);font-size:13px">@<?= e($submission['username']) ?></span></div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--tm)">الفورم</div>
        <div style="font-weight:700"><?= e($submission['form_title']) ?></div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--tm)">التاريخ</div>
        <div><?= date('d/m/Y H:i', strtotime($submission['submitted_at'])) ?></div>
      </div>
      <div>
        <span class="badge badge--<?= $submission['status']==='accepted'?'green':($submission['status']==='rejected'?'red':'yellow') ?>">
          <?= $submission['status']==='accepted'?(icon('circle-check') . ' مقبول'):($submission['status']==='rejected'?(icon('circle-x') . ' مرفوض'):(icon('clock') . ' معلق')) ?>
        </span>
      </div>
      <?php if($submission['status']==='pending'): ?>
      <div style="display:flex;gap:8px;margin-right:auto">
        <form method="POST" action="<?= BASE_URL ?>/admin/submissions/review" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="submission_id" value="<?= $submission['id'] ?>"><input type="hidden" name="status" value="accepted">
          <button class="btn btn--green btn--sm"><?= icon('circle-check') ?> قبول</button>
        </form>
        <form method="POST" action="<?= BASE_URL ?>/admin/submissions/review" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="submission_id" value="<?= $submission['id'] ?>"><input type="hidden" name="status" value="rejected">
          <button class="btn btn--danger btn--sm"><?= icon('circle-x') ?> رفض</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Answers -->
  <div class="acard" style="margin-bottom:16px">
    <div class="acard-header"><h3><?= icon('clipboard') ?> إجابات الطلب</h3></div>
    <div>
      <?php if($questions): ?>
        <?php foreach ($questions as $q):
          $ans = $answers[$q['label']] ?? '—';
          if (is_array($ans)) $ans = implode(', ', $ans);
        ?>
        <div style="display:grid;grid-template-columns:180px 1fr;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border-l)">
          <div style="font-weight:700;font-size:13px;color:var(--tm)"><?= e($q['label']) ?></div>
          <div style="font-size:13px;color:var(--td);word-break:break-word"><?= e((string)$ans) ?></div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($answers as $k => $v): ?>
        <div style="display:grid;grid-template-columns:180px 1fr;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border-l)">
          <div style="font-weight:700;font-size:13px;color:var(--tm)"><?= e($k) ?></div>
          <div style="font-size:13px;color:var(--td)"><?= e(is_array($v)?implode(', ',$v):(string)$v) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- AI Review -->
  <?php if($submission['ai_review']): ?>
  <div class="acard" style="margin-bottom:16px">
    <div class="acard-header"><h3><?= icon('bot') ?> مراجعة الذكاء الاصطناعي</h3></div>
    <div class="acard-body" style="white-space:pre-wrap;font-size:13px;line-height:1.8"><?= e($submission['ai_review']) ?></div>
  </div>
  <?php endif; ?>

  <!-- Note -->
  <div class="acard">
    <div class="acard-header"><h3><?= icon('file-text') ?> ملاحظة الإدارة</h3></div>
    <form method="POST" action="<?= BASE_URL ?>/admin/submissions/review" class="acard-body">
      <?= csrf_field() ?>
      <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
      <input type="hidden" name="status"        value="<?= $submission['status'] ?>">
      <div class="form-stack">
        <textarea name="admin_note" class="form-input" rows="3" placeholder="ملاحظة داخلية..."><?= e($submission['admin_note']??'') ?></textarea>
        <button class="btn btn--outline btn--sm"><?= icon('save') ?> حفظ الملاحظة</button>
      </div>
    </form>
  </div>
</div>
