<?php $isNew=!$ch; $cid=$ch['id']??0; ?>
<div class="toolbar"><a href="<?= BASE_URL ?>/admin/challenges" class="btn btn--outline btn--sm">← كل التحديات</a></div>
<div class="two-col two-col--40-60">

  <!-- Left: Challenge metadata -->
  <div class="acard">
    <div class="acard-header"><h3><?= $isNew?'تحدي جديد':'تعديل التحدي' ?></h3></div>
    <form action="<?= BASE_URL ?>/admin/challenges/save" method="POST" enctype="multipart/form-data" class="acard-body">
      <?= csrf_field() ?>
      <input type="hidden" name="challenge_id" value="<?= $cid ?>">
      <div class="form-stack">
        <div class="form-group"><label class="form-label">العنوان <span class="required-star">*</span></label><input type="text" name="title" class="form-input" required value="<?= e($ch['title']??'') ?>"></div>
        <div class="form-group"><label class="form-label">الوصف</label><textarea name="description" class="form-input form-textarea" rows="3"><?= e($ch['description']??'') ?></textarea></div>
        <div class="form-row-2">
          <div class="form-group"><label class="form-label">التصنيف</label><input type="text" name="category" class="form-input" value="<?= e($ch['category']??'عام') ?>" placeholder="مثال: برمجة، لغة عربية"></div>
          <div class="form-group"><label class="form-label">نسبة النجاح %</label><input type="number" name="passing_score" class="form-input" min="1" max="100" value="<?= $ch['passing_score']??80 ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">الوقت المحدد (دقائق، 0=بلا حد)</label><input type="number" name="time_limit" class="form-input" min="0" value="<?= $ch['time_limit']??0 ?>"></div>
        <div class="form-group">
          <label class="form-label">صورة الغلاف</label>
          <?php if(!empty($ch['cover_image'])): ?><img src="<?= BASE_URL . e($ch['cover_image']) ?>" style="max-width:180px;border-radius:8px;margin-bottom:8px"><?php endif; ?>
          <input type="file" name="cover_image" class="form-input" accept="image/*" style="padding:6px">
        </div>
        <label class="check-label"><input type="checkbox" name="randomize" value="1" <?= ($ch['randomize']??0)?'checked':'' ?>><span>ترتيب عشوائي للأسئلة</span></label>
        <div class="form-group">
          <label class="form-label">الحالة</label>
          <select name="status" class="form-select">
            <option value="draft"     <?= ($ch['status']??'')==='draft'?'selected':'' ?>>مسودة</option>
            <option value="published" <?= ($ch['status']??'')==='published'?'selected':'' ?>>منشور</option>
            <option value="archived"  <?= ($ch['status']??'')==='archived'?'selected':'' ?>>مؤرشف</option>
          </select>
        </div>
        <button type="submit" class="btn btn--gold btn--full"><?= icon('save') ?> حفظ التحدي</button>
      </div>
    </form>
  </div>

  <!-- Right: Questions list -->
  <div class="acard" id="chQPanel" <?= $isNew?'style="opacity:.4;pointer-events:none"':'' ?>>
    <div class="acard-header">
      <h3>الأسئلة (<span id="qCount"><?= count($questions) ?></span>)</h3>
      <button class="btn btn--sm btn--gold" id="addChQBtn" <?= $isNew?'disabled':'' ?>><?= icon('plus') ?> سؤال جديد</button>
    </div>
    <ul class="q-list" id="chQList">
      <?php foreach ($questions as $q): ?>
      <li class="q-item" data-id="<?= $q['id'] ?>" draggable="true">
        <span class="q-drag"><?= icon('menu') ?></span>
        <div class="q-info" style="flex-direction:column;align-items:flex-start">
          <span class="q-label"><?= e(mb_substr($q['question'],0,70)) ?><?= mb_strlen($q['question'])>70?'…':'' ?></span>
          <div style="display:flex;gap:6px;margin-top:4px">
            <span class="q-type"><?= count($optsByQ[$q['id']]??[]) ?> خيار</span>
            <span class="badge badge--gold badge--xs"><?= $q['points'] ?> نقطة</span>
          </div>
        </div>
        <div class="q-acts">
          <button type="button" class="btn btn--xs btn--outline edit-ch-q" data-id="<?= $q['id'] ?>" data-question="<?= e($q['question']) ?>" data-points="<?= (int)$q['points'] ?>">تعديل</button>
          <button type="button" class="btn btn--xs btn--danger del-ch-q" data-id="<?= $q['id'] ?>">×</button>
        </div>
      </li>
      <?php endforeach; ?>
      <?php if(empty($questions)): ?><li class="q-empty" id="chQEmpty">لا توجد أسئلة بعد.</li><?php endif; ?>
    </ul>
  </div>
</div>

<!-- Question editor modal -->
<div class="modal-bg" id="chQModal" style="display:none">
  <div class="modal-box" style="max-width:680px">
    <div class="modal-head"><h3 id="chMTitle">إضافة سؤال</h3><button type="button" class="modal-close" id="closeChModal" data-modal-close="#chQModal">×</button></div>
    <div class="modal-body">
      <form id="chQForm" class="form-stack">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="challenge_id" value="<?= $cid ?>">
        <input type="hidden" name="q_id" id="chQId" value="">

        <div class="form-group">
          <label class="form-label">نص السؤال <span class="required-star">*</span> <span class="form-hint">يدعم Markdown: **عريض** `كود` ```block```</span></label>
          <textarea name="question" id="chQText" class="form-input form-textarea" rows="4" required placeholder="أدخل نص السؤال..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">النقاط</label>
          <input type="number" name="points" id="chQPoints" class="form-input" min="1" max="100" value="1" style="max-width:120px">
        </div>

        <div class="form-group">
          <label class="form-label">الخيارات <span class="form-hint">— <?= icon('check') ?> للإجابة الصحيحة (يمكن تحديد أكثر من واحدة)</span></label>
          <div class="option-rows" id="optionRows">
            <!-- Dynamically filled -->
          </div>
          <button type="button" class="btn btn--sm btn--outline" id="addOptBtn" style="margin-top:8px">+ إضافة خيار</button>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn--gold">حفظ السؤال</button>
          <button type="button" class="btn btn--outline" id="cancelChModal">إلغاء</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script nonce="<?= csp_nonce() ?>">
const CID=<?= $cid ?>,CSRF='<?= csrf_token() ?>',BASE='<?= BASE_URL ?>';
// All saved options per question
const savedOptsByQ=<?= json_encode($optsByQ, JSON_UNESCAPED_UNICODE) ?>;

// Drag reorder
let drag=null;
function initDrag(){
  document.querySelectorAll('#chQList .q-item').forEach(el=>{
    el.addEventListener('dragstart',()=>{drag=el;el.classList.add('dragging');});
    el.addEventListener('dragend',()=>{el.classList.remove('dragging');saveQOrder();});
    el.addEventListener('dragover',e=>e.preventDefault());
    el.addEventListener('drop',e=>{e.preventDefault();if(drag&&drag!==el){el.compareDocumentPosition(drag)&Node.DOCUMENT_POSITION_FOLLOWING?el.before(drag):el.after(drag);}});
  });
}
async function saveQOrder(){
  const ids=[...document.querySelectorAll('#chQList .q-item')].map(e=>e.dataset.id);
  const fd=new FormData();fd.append('_csrf',CSRF);fd.append('challenge_id',CID);ids.forEach(id=>fd.append('order[]',id));
  await fetch(BASE+'/admin/challenges/reorder',{method:'POST',body:fd});
}

// Option row builder
let optCount=0;
function buildOptionRow(text='',isCorrect=false,idx=null){
  const i=idx!==null?idx:optCount++;
  const div=document.createElement('div');
  div.className='option-row';
  div.innerHTML=`<input type="checkbox" name="correct[]" value="${i}" class="option-correct-chk" ${isCorrect?'checked':''}> <input type="text" name="options[]" value="${escAttr(text)}" class="form-input" placeholder="نص الخيار..." required style="flex:1;border:none;outline:none;background:transparent"> <button type="button" class="option-remove" data-remove-option aria-label="حذف الخيار">×</button>`;
  return div;
}
function escAttr(s){return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function addOption(text='',isCorrect=false){
  document.getElementById('optionRows').appendChild(buildOptionRow(text,isCorrect));
}
document.getElementById('addOptBtn').addEventListener('click',()=>addOption());
document.getElementById('optionRows').addEventListener('click',e=>{
  const btn=e.target.closest('[data-remove-option]');
  if(btn)btn.closest('.option-row').remove();
});

// Modal open
const modal=document.getElementById('chQModal');
function openChModal(data={}){
  const qid=data.id||null;
  document.getElementById('chMTitle').textContent=qid?'تعديل سؤال':'إضافة سؤال جديد';
  document.getElementById('chQId').value=qid||'';
  document.getElementById('chQText').value=data.question||'';
  document.getElementById('chQPoints').value=data.points||'1';
  document.getElementById('optionRows').innerHTML='';
  optCount=0;

  if(qid&&savedOptsByQ[qid]){
    savedOptsByQ[qid].forEach((o,i)=>addOption(o.option_text,o.is_correct==1));
    if(savedOptsByQ[qid].length===0){addOption();addOption();}
  } else {
    addOption();addOption(); // Default two empty options
  }
  if(window.adminOpenModal)window.adminOpenModal(modal);else modal.style.display='flex';
  document.getElementById('chQText').focus();
}
document.getElementById('addChQBtn').addEventListener('click',()=>openChModal());
document.getElementById('closeChModal').addEventListener('click',()=>window.adminCloseModal?window.adminCloseModal(modal):modal.style.display='none');
document.getElementById('cancelChModal').addEventListener('click',()=>window.adminCloseModal?window.adminCloseModal(modal):modal.style.display='none');
modal.addEventListener('click',e=>{if(e.target===modal)(window.adminCloseModal?window.adminCloseModal(modal):modal.style.display='none');});

// Edit button — fetch question text
document.querySelectorAll('.edit-ch-q').forEach(b=>b.addEventListener('click',function(){
  openChModal(this.dataset);
}));

// Delete
document.querySelectorAll('.del-ch-q').forEach(b=>b.addEventListener('click',async function(){
  const ok=await (window.adminConfirm?window.adminConfirm('حذف هذا السؤال؟',{title:'حذف السؤال',confirmText:'حذف',danger:true}):Promise.resolve(confirm('حذف هذا السؤال؟')));
  if(!ok)return;
  const fd=new FormData();fd.append('_csrf',CSRF);fd.append('challenge_id',CID);fd.append('q_id',this.dataset.id);
  await fetch(BASE+'/admin/challenges/question/delete',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
  this.closest('.q-item').remove();
  document.getElementById('qCount').textContent=document.querySelectorAll('#chQList .q-item').length;
}));

// Save question
document.getElementById('chQForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const res=await fetch(BASE+'/admin/challenges/question',{method:'POST',body:new FormData(this)});
  const d=await res.json();
  if(d.success){if(window.adminCloseModal)window.adminCloseModal(modal);else modal.style.display='none';location.reload();}
  else if(window.adminAlert)window.adminAlert(d.error||'فشل الحفظ. تأكد من وجود سؤال وخيارين وإجابة صحيحة.',{title:'تعذر الحفظ'});
  else alert(d.error||'فشل الحفظ. تأكد من وجود سؤال وخيارين وإجابة صحيحة.');
});

initDrag();
</script>
