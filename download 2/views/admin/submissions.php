<?php $fid = (int)($_GET['form_id']??0); $sf = $_GET['status']??'pending'; ?>

<div class="toolbar" style="margin-bottom:14px">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <strong style="font-size:13px;color:var(--tm)"><?= count($submissions) ?> طلب</strong>
    <?php if($fid && (!$sf || $sf==='pending')): ?>
    <button type="button" class="btn btn--outline btn--sm" data-action="ai-all" data-form-id="<?= $fid ?>" id="aiAllBtn"><?= icon('bot') ?> مراجعة AI للكل</button>
    <button type="button" class="btn btn--green btn--sm" data-action="bulk" data-bulk-action="accept-all" data-form-id="<?= $fid ?>" data-confirm-message="قبول كل الطلبات المعلقة؟"><?= icon('circle-check') ?> قبول الكل</button>
    <button type="button" class="btn btn--danger btn--sm" data-action="bulk" data-bulk-action="reject-all" data-form-id="<?= $fid ?>" data-confirm-message="رفض كل الطلبات المعلقة؟"><?= icon('circle-x') ?> رفض الكل</button>
    <?php endif; ?>
    <?php if($fid): ?>
    <a href="<?= BASE_URL ?>/admin/submissions/export?form_id=<?= $fid ?>" class="btn btn--outline btn--sm"><?= icon('download') ?> تصدير CSV</a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>/admin/submissions/export" class="btn btn--outline btn--sm"><?= icon('download') ?> تصدير CSV</a>
    <?php endif; ?>
  </div>
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <select name="form_id" class="form-select" style="font-size:13px" data-auto-submit>
      <option value="">كل الفورمات</option>
      <?php foreach($forms??[] as $f): ?>
      <option value="<?= $f['id'] ?>" <?= $fid===$f['id']?'selected':'' ?>><?= e($f['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select" style="font-size:13px" data-auto-submit>
      <option value="">كل الحالات</option>
      <option value="pending"  <?= $sf==='pending' ?'selected':'' ?>>معلق</option>
      <option value="accepted" <?= $sf==='accepted'?'selected':'' ?>>مقبول</option>
      <option value="rejected" <?= $sf==='rejected'?'selected':'' ?>>مرفوض</option>
    </select>
  </form>
</div>

<!-- AI Bulk progress -->
<div id="aiBulkBar" style="display:none;margin-bottom:12px">
  <div class="acard"><div class="acard-body" style="padding:14px">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:13px;font-weight:700"><?= icon('bot') ?> مراجعة AI جارية...</span>
      <span id="aiBulkCount" style="font-size:12px;color:var(--tm)"></span>
    </div>
    <div style="background:var(--bg-2);border-radius:99px;height:5px;overflow:hidden">
      <div id="aiBulkFill" style="height:100%;width:0%;background:linear-gradient(90deg,var(--sage),var(--gold));border-radius:99px;transition:width .4s"></div>
    </div>
    <div id="aiBulkMsg" style="font-size:12px;color:var(--tm);margin-top:6px"></div>
  </div></div>
</div>

<!-- Accordion list -->
<div class="acard">
  <?php if(empty($submissions)): ?>
  <div style="text-align:center;padding:44px;color:var(--tm)">لا توجد طلبات.</div>
  <?php else: ?>
  <?php foreach ($submissions as $i => $s):
    $answers = json_decode($s['answers']??'{}', true) ?: [];
    // Format answers: use 'label' key when available
    $display = [];
    foreach ($answers as $k => $v) {
      if (is_array($v) && isset($v['label'])) {
        $display[$v['label']] = $v['value'] ?? (isset($v['values']) ? implode(', ', $v['values']) : '');
      } else {
        $display[$k] = is_array($v) ? implode(', ', $v) : (string)$v;
      }
    }
  ?>
  <div class="sub-item" id="row-<?= $s['id'] ?>">
    <!-- Header row -->
    <div class="sub-header" data-action="toggle-sub" data-id="<?= $s['id'] ?>" role="button" tabindex="0">
      <div class="sub-meta">
        <span style="font-size:12px;color:var(--tm);min-width:24px"><?= $i+1 ?></span>
        <strong><?= e($s['display_name']) ?></strong>
        <span style="font-size:12px;color:var(--tm)">@<?= e($s['username']) ?></span>
        <span style="font-size:12px;color:var(--tm)">·</span>
        <span style="font-size:12px;color:var(--tm)"><?= e($s['form_title']) ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="badge badge--<?= $s['status']==='accepted'?'green':($s['status']==='rejected'?'red':'yellow') ?>" style="font-size:11px" id="badge-<?= $s['id'] ?>">
          <?= $s['status']==='accepted'?(icon('circle-check') . ' مقبول'):($s['status']==='rejected'?(icon('circle-x') . ' مرفوض'):(icon('clock') . ' معلق')) ?>
        </span>
        <?php if($s['ai_review']): ?><span title="يوجد مراجعة AI" style="font-size:13px"><?= icon('bot') ?></span><?php endif; ?>
        <span style="font-size:12px;color:var(--tm);white-space:nowrap"><?= date('d/m/Y', strtotime($s['submitted_at'])) ?></span>
        <span class="expand-arrow" id="arrow-<?= $s['id'] ?>"><?= icon('chevron-down') ?></span>
      </div>
    </div>

    <!-- Expanded answers -->
    <div class="sub-answers" id="sub-<?= $s['id'] ?>">
      <div style="padding:0 18px">
        <?php foreach ($display as $label => $val): ?>
        <div style="display:grid;grid-template-columns:160px 1fr;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-l);font-size:13px">
          <div style="font-weight:700;color:var(--tm)"><?= e($label) ?></div>
          <div style="color:var(--td);word-break:break-word"><?= e((string)$val) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($display)): ?>
        <div style="color:var(--tm);padding:12px 0;font-size:13px">لا توجد إجابات.</div>
        <?php endif; ?>
      </div>
      <?php if($s['ai_review']): ?>
      <div style="padding:10px 18px;background:var(--sage-faint);border-top:1px solid var(--border-l);font-size:13px">
        <div style="font-weight:700;margin-bottom:6px"><?= icon('bot') ?> مراجعة AI:</div>
        <div style="white-space:pre-wrap;line-height:1.7"><?= e($s['ai_review']) ?></div>
      </div>
      <?php endif; ?>
      <!-- Actions -->
      <div class="sub-actions-row" id="actions-<?= $s['id'] ?>">
        <?php if($s['status']==='pending'): ?>
        <button type="button" class="btn btn--green btn--sm" data-action="review" data-id="<?= $s['id'] ?>" data-status="accepted"><?= icon('circle-check') ?> قبول</button>
        <button type="button" class="btn btn--danger btn--sm" data-action="review" data-id="<?= $s['id'] ?>" data-status="rejected"><?= icon('circle-x') ?> رفض</button>
        <?php endif; ?>
        <button class="btn btn--xs btn--outline" id="ai-btn-<?= $s['id'] ?>"
          data-action="ai-review" data-id="<?= $s['id'] ?>">
          <?= $s['ai_review'] ? (icon('refresh') . ' إعادة مراجعة AI') : (icon('bot') . ' مراجعة AI') ?>
        </button>
        <a href="<?= BASE_URL ?>/admin/submissions/view?id=<?= $s['id'] ?>" class="btn btn--xs btn--outline" target="_blank"><?= icon('link') ?> صفحة كاملة</a>
        <button type="button" class="btn btn--xs btn--danger" data-action="delete" data-id="<?= $s['id'] ?>"><?= icon('trash') ?></button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- AI Modal -->
<div id="aiModal" class="modal-bg" style="display:none">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-head"><h3><?= icon('bot') ?> مراجعة AI</h3><button type="button" class="modal-close" data-modal-close="#aiModal"><?= icon('x') ?></button></div>
    <div class="modal-body">
      <div id="aiContent" style="white-space:pre-wrap;font-size:13px;line-height:1.8"></div>
      <div id="aiActions" style="display:flex;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border-l);flex-wrap:wrap"></div>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="adminToast" style="
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);
  background:#342314;color:#fff;padding:12px 22px;border-radius:100px;
  font-size:14px;font-weight:600;z-index:9999;
  transition:transform .3s cubic-bezier(.4,0,.2,1),opacity .3s;
  opacity:0;pointer-events:none;white-space:nowrap;
  box-shadow:0 8px 32px rgba(0,0,0,.3);
"></div>

<script nonce="<?= csp_nonce() ?>">
const CSRF='<?= csrf_token() ?>', BASE='<?= BASE_URL ?>';

function showToast(msg, type) {
  var t = document.getElementById('adminToast');
  t.textContent = msg;
  t.style.background = type === 'error' ? '#8b1a1a' : type === 'warn' ? '#7a5900' : '#342314';
  t.style.opacity = '1';
  t.style.transform = 'translateX(-50%) translateY(0)';
  clearTimeout(t._timer);
  t._timer = setTimeout(function() {
    t.style.opacity = '0';
    t.style.transform = 'translateX(-50%) translateY(80px)';
  }, 3000);
}

function toggleSub(id) {
  var panel = document.getElementById('sub-'+id);
  var arrow = document.getElementById('arrow-'+id);
  var open  = panel.classList.toggle('open');
  if (arrow) arrow.textContent = open ? '' : '';
}

async function reviewSub(sid, status, btn) {
  const ok = await (window.adminConfirm ? window.adminConfirm(status === 'accepted' ? 'قبول هذا الطلب؟' : 'رفض هذا الطلب؟', {
    title: status === 'accepted' ? 'قبول الطلب' : 'رفض الطلب',
    confirmText: status === 'accepted' ? 'قبول' : 'رفض',
    danger: status !== 'accepted'
  }) : Promise.resolve(confirm(status === 'accepted' ? 'قبول هذا الطلب؟' : 'رفض هذا الطلب؟')));
  if (!ok) return;
  btn.disabled = true;
  var origText = btn.textContent;
  btn.textContent = '';
  var fd = new FormData();
  fd.append('submission_id', sid);
  fd.append('status', status);
  fd.append('_csrf', CSRF);
  try {
    var res = await fetch(BASE + '/admin/submissions/review', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: fd
    });
    var d = await res.json();
    if (d.success) {
      // Update badge
      var badge = document.getElementById('badge-' + sid);
      if (badge) {
        badge.className = 'badge badge--' + (status === 'accepted' ? 'green' : 'red');
        badge.textContent = status === 'accepted' ? 'مقبول' : 'مرفوض';
      }
      // Remove accept/reject buttons from actions row
      var actRow = document.getElementById('actions-' + sid);
      if (actRow) {
        actRow.querySelectorAll('.btn--green, .btn--danger:not(.btn--xs)').forEach(function(b) { b.remove(); });
      }
      showToast(status === 'accepted' ? 'تم قبول الطلب' : 'تم رفض الطلب', 'ok');
    } else {
      showToast('حدث خطأ', 'error');
      btn.disabled = false; btn.textContent = origText;
    }
  } catch(e) {
    showToast('خطأ في الشبكة', 'error');
    btn.disabled = false; btn.textContent = origText;
  }
}

async function deleteSub(sid, btn) {
  const ok = await (window.adminConfirm ? window.adminConfirm('حذف هذا الطلب نهائيا؟', {
    title: 'حذف الطلب',
    confirmText: 'حذف',
    danger: true
  }) : Promise.resolve(confirm('حذف هذا الطلب نهائيا؟')));
  if (!ok) return;
  btn.disabled = true;
  var fd = new FormData();
  fd.append('id', sid);
  fd.append('_csrf', CSRF);
  try {
    var res = await fetch(BASE + '/admin/submissions/delete', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: fd
    });
    var row = document.getElementById('row-' + sid);
    if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; setTimeout(function() { row.remove(); }, 300); }
    showToast('تم الحذف', 'ok');
  } catch(e) {
    showToast('خطأ في الحذف', 'error');
    btn.disabled = false;
  }
}

async function bulkAction(action, fid, confirmMsg) {
  const ok = await (window.adminConfirm ? window.adminConfirm(confirmMsg, {
    title: action === 'accept-all' ? 'قبول الطلبات' : 'رفض الطلبات',
    confirmText: action === 'accept-all' ? 'قبول الكل' : 'رفض الكل',
    danger: action !== 'accept-all'
  }) : Promise.resolve(confirm(confirmMsg)));
  if (!ok) return;
  var fd = new FormData();
  fd.append('form_id', fid);
  fd.append('_csrf', CSRF);
  showToast('جاري التنفيذ...', 'ok');
  try {
    var res = await fetch(BASE + '/admin/submissions/' + action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: fd
    });
    var d = await res.json();
    showToast(d.message || (d.success ? 'تم' : 'فشل'), d.success ? 'ok' : 'error');
    if (d.success) setTimeout(function() { location.reload(); }, 1200);
  } catch(e) {
    showToast('خطأ في الشبكة', 'error');
  }
}

async function runAI(sid, btn) {
  if(btn){btn.disabled=true;btn.textContent='';}
  if (window.adminOpenModal) window.adminOpenModal('#aiModal');
  else document.getElementById('aiModal').style.display='flex';
  document.getElementById('aiContent').textContent='جاري المراجعة...';
  document.getElementById('aiActions').innerHTML='';
  var fd=new FormData(); fd.append('submission_id',sid); fd.append('_csrf',CSRF);
  try {
    var res=await fetch(BASE+'/admin/submissions/ai-review',{
      method:'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body:fd
    });
    var d=await res.json();
    if(d.error){document.getElementById('aiContent').textContent=''+d.error;if(btn){btn.disabled=false;btn.textContent='مراجعة AI';}return;}
    document.getElementById('aiContent').textContent=d.review;
    if(btn){btn.disabled=false;btn.textContent='إعادة مراجعة AI';}
    var panel=document.getElementById('sub-'+sid);
    if(panel){
      var aiBlock=panel.querySelector('.ai-inline');
      if(!aiBlock){aiBlock=document.createElement('div');aiBlock.className='ai-inline';aiBlock.style.cssText='padding:10px 18px;background:var(--sage-faint);border-top:1px solid var(--border-l);font-size:13px';panel.insertBefore(aiBlock,panel.querySelector('.sub-actions-row'));}
      aiBlock.innerHTML='<div style="font-weight:700;margin-bottom:6px">مراجعة AI:</div><div style="white-space:pre-wrap;line-height:1.7">'+d.review.replace(/</g,'&lt;')+'</div>';
    }
    document.getElementById('aiActions').innerHTML=
      '<button type="button" class="btn btn--green" data-action="review-from-modal" data-id="'+sid+'" data-status="accepted">قبول</button>'+
      '<button type="button" class="btn btn--danger" data-action="review-from-modal" data-id="'+sid+'" data-status="rejected">رفض</button>';
  } catch(e) {
    document.getElementById('aiContent').textContent='خطأ: ' + e.message;
    if(btn){btn.disabled=false;btn.textContent='مراجعة AI';}
  }
}

async function runAIAll(fid) {
  var btn=document.getElementById('aiAllBtn'),bar=document.getElementById('aiBulkBar');
  var fill=document.getElementById('aiBulkFill'),msg=document.getElementById('aiBulkMsg'),cnt=document.getElementById('aiBulkCount');
  btn.disabled=true; btn.textContent=''; bar.style.display='block'; fill.style.width='10%'; msg.textContent='يتصل بـ Ollama...';
  var p=10, t=setInterval(function(){if(p<85){p+=2;fill.style.width=p+'%';}},600);
  try{
    var fd=new FormData(); fd.append('form_id',fid); fd.append('_csrf',CSRF);
    var res=await fetch(BASE+'/admin/submissions/ai-review-all',{
      method:'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body:fd
    });
    var d=await res.json();
    clearInterval(t); fill.style.width='100%';
    if(d.error){msg.innerHTML='<span style="color:#c0392b">'+d.error+'</span>';btn.disabled=false;btn.textContent='مراجعة AI للكل';}
    else{msg.innerHTML='<span style="color:var(--sage-dark)">'+d.message+'</span>';cnt.textContent=d.done+' طلب';btn.textContent='إعادة';btn.disabled=false;setTimeout(function(){location.reload();},1500);}
  }catch(e){clearInterval(t);msg.innerHTML='<span style="color:#c0392b">'+e.message+'</span>';btn.disabled=false;btn.textContent='مراجعة AI للكل';}
}

window.addEventListener('load',function(){ var items=document.querySelectorAll('.sub-item'); if(items.length===1){var id=items[0].id.replace('row-','');toggleSub(parseInt(id));} });

document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-action]');
  if (!btn) return;
  var action = btn.dataset.action;
  if (action === 'toggle-sub') {
    toggleSub(btn.dataset.id);
  } else if (action === 'review') {
    reviewSub(btn.dataset.id, btn.dataset.status, btn);
  } else if (action === 'review-from-modal') {
    reviewSub(btn.dataset.id, btn.dataset.status, btn);
    if (window.adminCloseModal) window.adminCloseModal('#aiModal');
    else document.getElementById('aiModal').style.display = 'none';
  } else if (action === 'ai-review') {
    runAI(btn.dataset.id, btn);
  } else if (action === 'delete') {
    deleteSub(btn.dataset.id, btn);
  } else if (action === 'bulk') {
    bulkAction(btn.dataset.bulkAction, btn.dataset.formId, btn.dataset.confirmMessage);
  } else if (action === 'ai-all') {
    runAIAll(btn.dataset.formId);
  }
});

document.addEventListener('keydown', function(e) {
  var header = e.target.closest('[data-action="toggle-sub"]');
  if (!header || (e.key !== 'Enter' && e.key !== ' ')) return;
  e.preventDefault();
  toggleSub(header.dataset.id);
});
</script>
