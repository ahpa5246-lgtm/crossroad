<div class="acard" style="max-width:900px">
  <h2 style="font-family:var(--font-display);margin-bottom:24px"><?= icon('award') ?> إصدار شهادات جماعية</h2>
  <?php if ($e = flash('error')): ?><div class="alert alert--error" style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>/admin/certs/bulk" id="bulkCertForm">
    <?= csrf_field() ?>

    <!-- Source selector -->
    <div class="form-group">
      <label class="form-label">المصدر</label>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <label class="radio-pill"><input type="radio" name="source" value="form" checked> من فورم معين</label>
        <label class="radio-pill"><input type="radio" name="source" value="manual"> إدخال يدوي</label>
      </div>
    </div>

    <!-- Form source -->
    <div id="srcForm">
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">اختر الفورم</label>
          <select name="form_ref_id" id="formPicker" class="form-input">
            <option value="">-- اختر --</option>
            <?php foreach ($forms as $f): ?>
            <option value="<?= $f['id'] ?>"><?= e($f['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">حالة الطلب</label>
          <select name="submission_status" class="form-input">
            <option value="accepted">مقبول فقط</option>
            <option value="all">الكل</option>
          </select>
        </div>
      </div>
      <div id="formUsersList" style="display:none;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <strong id="formUsersCount"></strong>
          <label style="font-size:13px;cursor:pointer"><input type="checkbox" id="selectAll"> تحديد الكل</label>
        </div>
        <div id="formUsersGrid" style="max-height:280px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--r-md);padding:10px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px"></div>
      </div>
    </div>

    <!-- Manual source -->
    <div id="srcManual" style="display:none">
      <div class="form-group">
        <label class="form-label">أرقام المستخدمين (user_id) — كل رقم في سطر أو مفصولة بفاصلة</label>
        <textarea name="user_ids_manual" class="form-input" rows="5" placeholder="1&#10;2&#10;3&#10;...أو: 1,2,3,4"></textarea>
      </div>
    </div>

    <!-- Certificate details -->
    <div style="border-top:1px solid var(--border);padding-top:20px;margin-top:8px">
      <div class="form-group">
        <label class="form-label">عنوان الشهادة <span class="required-star">*</span></label>
        <input type="text" name="title" class="form-input" required placeholder="شهادة إتمام برنامج <?= e(brand_name()) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">وصف الشهادة</label>
        <textarea name="description" class="form-input" rows="2" placeholder="has successfully completed the program…"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">ربط بتحدي (اختياري)</label>
        <select name="challenge_id" class="form-input">
          <option value="">-- لا شيء --</option>
          <?php foreach ($challenges as $ch): ?>
          <option value="<?= $ch['id'] ?>"><?= e($ch['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <input type="hidden" name="user_ids" id="finalUserIds">
    <button type="submit" class="btn btn--gold btn--lg" onclick="return prepareBulk()"><?= icon('award') ?> إصدار الشهادات ←</button>
    <a href="<?= BASE_URL ?>/admin/certificates" class="btn btn--outline">إلغاء</a>
  </form>
</div>

<script nonce="<?= csp_nonce() ?>">
const BASE = '<?= BASE_URL ?>';
const CSRF = '<?= csrf_token() ?>';

// Toggle source
document.querySelectorAll('input[name="source"]').forEach(r => r.addEventListener('change', function() {
  document.getElementById('srcForm').style.display   = this.value === 'form'   ? '' : 'none';
  document.getElementById('srcManual').style.display = this.value === 'manual' ? '' : 'none';
}));

// Load users when form changes
document.getElementById('formPicker').addEventListener('change', loadFormUsers);
document.querySelector('select[name="submission_status"]').addEventListener('change', loadFormUsers);

async function loadFormUsers() {
  const fid    = document.getElementById('formPicker').value;
  const status = document.querySelector('select[name="submission_status"]').value;
  if (!fid) { document.getElementById('formUsersList').style.display='none'; return; }

  const res  = await fetch(`${BASE}/admin/certs/users-for-form?form_id=${fid}&status=${status}&_csrf=${CSRF}`);
  const data = await res.json();
  const grid = document.getElementById('formUsersGrid');
  grid.innerHTML = '';

  data.forEach(u => {
    grid.insertAdjacentHTML('beforeend', `
      <label style="display:flex;align-items:center;gap:8px;padding:8px;border:1px solid var(--border);border-radius:var(--r-md);cursor:pointer;font-size:13px">
        <input type="checkbox" class="user-cb" value="${u.id}" checked style="accent-color:var(--sage-dark)">
        <div>
          <div style="font-weight:600">${u.display_name}</div>
          <div style="font-size:11px;color:var(--text-muted)">@${u.username}</div>
        </div>
      </label>`);
  });

  document.getElementById('formUsersCount').textContent = `${data.length} مستخدم`;
  document.getElementById('formUsersList').style.display = data.length ? '' : 'none';

  document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.user-cb').forEach(cb => cb.checked = this.checked);
  });
}

function prepareBulk() {
  const src = document.querySelector('input[name="source"]:checked').value;
  if (src === 'form') {
    const ids = [...document.querySelectorAll('.user-cb:checked')].map(cb => cb.value);
    if (!ids.length) { alert('اختر مستخدما واحدا على الأقل'); return false; }
    document.getElementById('finalUserIds').value = ids.join(',');
  } else {
    document.getElementById('finalUserIds').value = document.querySelector('textarea[name="user_ids_manual"]').value;
  }
  return true;
}
</script>
