<div class="toolbar"><a href="<?= BASE_URL ?>/admin/certificates" class="btn btn--outline btn--sm">← الشهادات</a></div>
<div class="acard" style="max-width:660px">
  <div class="acard-header"><h3>إصدار شهادة جديدة</h3></div>
  <form action="<?= BASE_URL ?>/admin/certificates/save" method="POST" class="acard-body">
    <?= csrf_field() ?>
    <div class="form-stack">
      <div class="form-group"><label class="form-label">المستخدم <span class="required-star">*</span></label><select name="user_id" class="form-select" required><option value="">اختر مستخدما...</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>" <?= $preUserId==$u['id']?'selected':'' ?>><?= e($u['display_name']) ?> (@<?= e($u['username']) ?>)</option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">عنوان الشهادة <span class="required-star">*</span></label><input type="text" name="title" class="form-input" required value="<?= e($preTitle ?? '') ?>" placeholder="مثال: شهادة إتمام برنامج القيادة"></div>
      <div class="form-group"><label class="form-label">الوصف</label><textarea name="description" class="form-input form-textarea" rows="3"></textarea></div>
      <div class="form-row-2">
        <div class="form-group"><label class="form-label">البرنامج المرتبط</label><select name="form_id" class="form-select"><option value="">لا يوجد</option><?php foreach ($forms as $f): ?><option value="<?= $f['id'] ?>" <?= $preFormId==$f['id']?'selected':'' ?>><?= e($f['title']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">التحدي المرتبط</label><select name="challenge_id" class="form-select"><option value="">لا يوجد</option><?php foreach ($challenges as $ch): ?><option value="<?= $ch['id'] ?>" <?= $preChallengeId==$ch['id']?'selected':'' ?>><?= e($ch['title']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="form-group"><label class="form-label">تاريخ الانتهاء (اختياري)</label><input type="date" name="expires_at" class="form-input"></div>
      <input type="hidden" name="submission_id" value="<?= $preSubId ?>">
      <div class="form-actions"><button type="submit" class="btn btn--gold btn--lg"><?= icon('award') ?> إصدار الشهادة</button><a href="<?= BASE_URL ?>/admin/certificates" class="btn btn--outline">إلغاء</a></div>
    </div>
  </form>
</div>
