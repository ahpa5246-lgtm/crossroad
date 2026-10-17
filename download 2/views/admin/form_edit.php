<?php $isNew=!$form; $fid=$form['id']??0; ?>
<div class="toolbar"><a href="<?= BASE_URL ?>/admin/forms" class="btn btn--outline btn--sm">← كل النماذج</a></div>
<div class="two-col two-col--40-60">

  <!-- Form details -->
  <div class="acard">
    <div class="acard-header"><h3><?= $isNew?'نموذج جديد':'تعديل النموذج' ?></h3></div>
    <form action="<?= BASE_URL ?>/admin/forms/save" method="POST" enctype="multipart/form-data" class="acard-body">
      <?= csrf_field() ?>
      <input type="hidden" name="form_id" value="<?= $fid ?>">
      <div class="form-stack">
        <div class="form-group"><label class="form-label">العنوان <span class="required-star">*</span></label><input type="text" name="title" class="form-input" required value="<?= e($form['title']??'') ?>"></div>
        <div class="form-group"><label class="form-label">الوصف</label><textarea name="description" class="form-input form-textarea" rows="3"><?= e($form['description']??'') ?></textarea></div>
        <div class="form-group">
          <label class="form-label">صورة الغلاف</label>
          <?php if(!empty($form['cover_image'])): ?><img src="<?= BASE_URL . e($form['cover_image']) ?>" style="max-width:180px;border-radius:8px;margin-bottom:8px"><?php endif; ?>
          <input type="file" name="cover_image" class="form-input" accept="image/*" style="padding:6px">
        </div>
        <div class="form-group">
          <label class="form-label">الحالة</label>
          <div style="display:flex;gap:16px">
            <label class="check-label"><input type="radio" name="status" value="open" <?= ($form['status']??'')==='open'?'checked':'' ?>> <?= icon('dot') ?> مفتوح</label>
            <label class="check-label"><input type="radio" name="status" value="closed" <?= ($form['status']??'closed')==='closed'?'checked':'' ?>> <?= icon('dot') ?> مغلق</label>
          </div>
        </div>
        
    <div class="form-group">
      <label class="form-label"><?= icon('lock') ?> متطلب مسبق (اختياري)</label>
      <select name="prerequisite_form_id" class="form-input">
        <option value="">— مفتوح للجميع —</option>
        <?php foreach ($allForms as $af): if ($af['id'] === $form['id']) continue; ?>
        <option value="<?= $af['id'] ?>" <?= ($form['prerequisite_form_id']??null)==$af['id']?'selected':'' ?>><?= e($af['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <div style="font-size:11px;color:var(--text-muted);margin-top:4px">يظهر هذا الفورم فقط للمستخدمين المقبولين في الفورم المحدد</div>
    </div>

<button type="submit" class="btn btn--gold btn--full"><?= icon('save') ?> حفظ التفاصيل</button>
      </div>
    </form>
  </div>

  <!-- Questions panel -->
  <div class="acard" id="qPanel" <?= $isNew?'style="opacity:.4;pointer-events:none"':'' ?>>
    <div class="acard-header">
      <h3>أسئلة النموذج</h3>
      <button type="button" class="btn btn--sm btn--gold" id="addQBtn" <?= $isNew?'disabled':'' ?>><?= icon('plus') ?> إضافة سؤال</button>
    </div>
    <ul class="q-list" id="qList">
      <?php foreach ($questions as $q): ?>
      <li class="q-item" data-id="<?= $q['id'] ?>" draggable="true">
        <span class="q-drag"><?= icon('menu') ?></span>
        <div class="q-info">
          <span class="q-label"><?= e($q['label']) ?></span>
          <span class="q-type"><?= ($q['type']==='short_text'?'نص قصير':($q['type']==='long_text'?'نص طويل':($q['type']==='email'?'بريد':($q['type']==='phone'?'هاتف':($q['type']==='number'?'رقم':($q['type']==='age'?'العمر':($q['type']==='date'?'تاريخ':($q['type']==='radio'?(icon('circle-check') . ' اختيار واحد'):($q['type']==='checkbox'?(icon('check') . ' اختيار متعدد'):($q['type']==='dropdown'?(icon('chevron-down') . ' قائمة منسدلة'):'?')))))))))) ?></span>
          <?= $q['is_required']?'<span class="badge badge--red" style="font-size:10px">مطلوب</span>':'<span class="badge badge--gray" style="font-size:10px">اختياري</span>' ?>
          <?php if(!empty($q['options'])): $opts=json_decode($q['options'],true)?:[]; ?>
          <span style="font-size:11px;color:var(--tm)">(<?= count($opts) ?> خيار)</span>
          <?php endif; ?>
        </div>
        <div class="q-acts">
          <button type="button" class="btn btn--xs btn--outline edit-q"
            data-id="<?= $q['id'] ?>"
            data-label="<?= e($q['label']) ?>"
            data-type="<?= e($q['type']) ?>"
            data-ph="<?= e($q['placeholder']??'') ?>"
            data-req="<?= $q['is_required'] ?>"
            data-options="<?= e($q['options']??'[]') ?>">تعديل</button>
          <button type="button" class="btn btn--xs btn--danger del-q" data-id="<?= $q['id'] ?>">×</button>
        </div>
      </li>
      <?php endforeach; ?>
      <?php if(empty($questions)): ?><li class="q-empty" id="qEmpty" style="padding:24px;text-align:center;color:var(--tm)">لا توجد أسئلة بعد.</li><?php endif; ?>
    </ul>
  </div>
</div>

<!-- Question modal -->
<div class="modal-bg" id="qModal" style="display:none">
  <div class="modal-box" style="max-width:540px">
    <div class="modal-head"><h3 id="mTitle">إضافة سؤال</h3><button type="button" class="modal-close" id="closeModal" data-modal-close="#qModal">×</button></div>
    <div class="modal-body">
      <form id="qForm" class="form-stack">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="form_id" value="<?= $fid ?>">
        <input type="hidden" name="q_id" id="qId" value="">

        <div class="form-group">
          <label class="form-label">السؤال / التسمية <span class="required-star">*</span></label>
          <input type="text" name="label" id="qLabel" class="form-input" required placeholder="مثال: ما تخصصك الدراسي؟">
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">نوع الحقل</label>
            <select name="type" id="qType" class="form-select">
              <optgroup label="نصية">
                <option value="short_text">نص قصير</option>
                <option value="long_text">نص طويل</option>
                <option value="email">بريد إلكتروني</option>
                <option value="phone">رقم هاتف</option>
                <option value="number">رقم</option>
                <option value="age">العمر</option>
                <option value="date">تاريخ</option>
              </optgroup>
              <optgroup label="اختيارات">
                <option value="radio">اختيار واحد (Radio)</option>
                <option value="checkbox">اختيار متعدد (Checkbox)</option>
                <option value="dropdown">قائمة منسدلة (Dropdown)</option>
              </optgroup>
            </select>
          </div>
          <div class="form-group" id="phGroup">
            <label class="form-label">نص تلميح</label>
            <input type="text" name="placeholder" id="qPh" class="form-input" placeholder="اختياري">
          </div>
        </div>

        <!-- Options area — shown only for radio/checkbox/dropdown -->
        <div id="optionsGroup" style="display:none">
          <div class="form-group">
            <label class="form-label">
              الخيارات
              <span class="form-hint">كل خيار في سطر منفصل</span>
            </label>
            <textarea id="qOptions" name="options" class="form-input form-textarea" rows="5"
              placeholder="الرياضيات&#10;العلوم&#10;الأدب&#10;الهندسة"></textarea>
            <p style="font-size:12px;color:var(--tm);margin-top:4px"><?= icon('pencil') ?> اكتب كل خيار في سطر — يمكنك إضافة ما تشاء</p>
          </div>
          <div id="optPreview" style="display:none;margin-top:8px">
            <p style="font-size:12px;font-weight:700;color:var(--tm);margin-bottom:8px">معاينة:</p>
            <div id="optPreviewList"></div>
          </div>
        </div>

        <label class="check-label">
          <input type="checkbox" name="is_required" id="qReq" checked>
          <span>حقل مطلوب</span>
        </label>

        <div class="form-actions">
          
<button type="submit" class="btn btn--gold"><?= icon('save') ?> حفظ السؤال</button>
          <button type="button" class="btn btn--outline" id="cancelModal">إلغاء</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script nonce="<?= csp_nonce() ?>">
const FID=<?= $fid ?>, CSRF='<?= csrf_token() ?>', BASE='<?= BASE_URL ?>';
const CHOICE_TYPES=['radio','checkbox','dropdown'];
let dragEl=null;

// ── Drag & drop reorder ──────────────────────────────
function initDrag(){
  document.querySelectorAll('.q-item').forEach(el=>{
    el.addEventListener('dragstart',()=>{dragEl=el;el.classList.add('dragging');});
    el.addEventListener('dragend',()=>{el.classList.remove('dragging');saveOrder();});
    el.addEventListener('dragover',e=>e.preventDefault());
    el.addEventListener('drop',e=>{
      e.preventDefault();
      if(dragEl&&dragEl!==el)el.compareDocumentPosition(dragEl)&Node.DOCUMENT_POSITION_FOLLOWING?el.before(dragEl):el.after(dragEl);
    });
  });
}
async function saveOrder(){
  const ids=[...document.querySelectorAll('.q-item')].map(e=>e.dataset.id);
  const fd=new FormData();fd.append('_csrf',CSRF);fd.append('form_id',FID);
  ids.forEach(id=>fd.append('order[]',id));
  await fetch(BASE+'/admin/forms/reorder',{method:'POST',body:fd});
}

// ── Modal ────────────────────────────────────────────
const modal=document.getElementById('qModal');
const qTypeEl=document.getElementById('qType');
const optGroup=document.getElementById('optionsGroup');
const optTA=document.getElementById('qOptions');
const phGroup=document.getElementById('phGroup');

function toggleOptGroup(){
  const isChoice=CHOICE_TYPES.includes(qTypeEl.value);
  optGroup.style.display=isChoice?'':'none';
  phGroup.style.display=isChoice&&qTypeEl.value==='checkbox'?'':'';
  updatePreview();
}
qTypeEl.addEventListener('change',toggleOptGroup);

// Live preview of options
optTA.addEventListener('input',updatePreview);
function updatePreview(){
  const lines=optTA.value.split('\n').map(l=>l.trim()).filter(Boolean);
  const prev=document.getElementById('optPreview');
  const list=document.getElementById('optPreviewList');
  if(!lines.length){prev.style.display='none';return;}
  prev.style.display='';
  const type=qTypeEl.value;
  list.innerHTML=lines.map(l=>{
    if(type==='radio')   return `<label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px"><input type="radio" disabled> ${escHtml(l)}</label>`;
    if(type==='checkbox') return `<label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px"><input type="checkbox" disabled> ${escHtml(l)}</label>`;
    return `<option disabled>${escHtml(l)}</option>`;
  }).join('');
  if(type==='dropdown') list.innerHTML=`<select style="padding:8px;border-radius:6px;border:1px solid var(--border);font-size:13px;width:100%" disabled><option>— اختر —</option>${list.innerHTML}</select>`;
}
function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function openModal(d={}){
  document.getElementById('mTitle').textContent=d.id?'تعديل سؤال':'إضافة سؤال';
  document.getElementById('qId').value=d.id||'';
  document.getElementById('qLabel').value=d.label||'';
  qTypeEl.value=d.type||'short_text';
  document.getElementById('qPh').value=d.ph||'';
  document.getElementById('qReq').checked=d.req!=='0';
  // Parse stored options JSON into textarea lines
  if(d.options){
    try{
      const arr=JSON.parse(d.options);
      optTA.value=Array.isArray(arr)?arr.join('\n'):'';
    }catch{optTA.value='';}
  } else { optTA.value=''; }
  toggleOptGroup();
  if(window.adminOpenModal)window.adminOpenModal(modal);else modal.style.display='flex';
  document.getElementById('qLabel').focus();
}

document.getElementById('addQBtn').addEventListener('click',()=>openModal());
document.getElementById('closeModal').addEventListener('click',()=>window.adminCloseModal?window.adminCloseModal(modal):modal.style.display='none');
document.getElementById('cancelModal').addEventListener('click',()=>window.adminCloseModal?window.adminCloseModal(modal):modal.style.display='none');
modal.addEventListener('click',e=>{if(e.target===modal)(window.adminCloseModal?window.adminCloseModal(modal):modal.style.display='none');});

document.querySelectorAll('.edit-q').forEach(b=>b.addEventListener('click',()=>openModal(b.dataset)));
document.querySelectorAll('.del-q').forEach(b=>b.addEventListener('click',async function(){
  const ok=await (window.adminConfirm?window.adminConfirm('حذف هذا السؤال؟',{title:'حذف السؤال',confirmText:'حذف',danger:true}):Promise.resolve(confirm('حذف هذا السؤال؟')));
  if(!ok)return;
  const fd=new FormData();fd.append('_csrf',CSRF);fd.append('form_id',FID);fd.append('q_id',this.dataset.id);
  const res=await fetch(BASE+'/admin/forms/question/delete',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
  if((await res.json()).success)this.closest('.q-item').remove();
}));

document.getElementById('qForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const type=qTypeEl.value;
  if(CHOICE_TYPES.includes(type)){
    const lines=optTA.value.split('\n').map(l=>l.trim()).filter(Boolean);
    if(lines.length<2){
      if(window.adminAlert)window.adminAlert('يجب إدخال خيارين على الأقل، كل خيار في سطر منفصل.',{title:'خيارات ناقصة'});
      else alert('يجب إدخال خيارين على الأقل، كل خيار في سطر منفصل.');
      return;
    }
  }
  const fd=new FormData(this);
  const res=await fetch(BASE+'/admin/forms/question',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd});
  const d=await res.json();
  if(d.success){if(window.adminCloseModal)window.adminCloseModal(modal);else modal.style.display='none';location.reload();}
  else if(window.adminAlert)window.adminAlert(d.error||'فشل الحفظ.',{title:'تعذر الحفظ'});
  else alert(d.error||'فشل الحفظ.');
});

initDrag();
</script>
