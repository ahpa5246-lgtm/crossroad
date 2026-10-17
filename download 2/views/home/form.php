<div class="page-hero">
  <div class="container"><h1><?= e($form['title']) ?></h1><?php if ($form['description']): ?><p><?= nl2br(e($form['description'])) ?></p><?php endif; ?></div>
</div>
<div class="section container" style="max-width:700px">
  <?php if ($form['cover_image']): ?><div class="apply-form-cover"><img src="<?= BASE_URL . e($form['cover_image']) ?>" alt=""></div><?php endif; ?>
  <?php $user = auth(); $guest = !empty($guestPreview); $_nextQ = rawurlencode('/apply/form?id=' . (int)$form['id']); ?>
  <?php if (!empty($alreadyApplied)): ?>
  <div class="already-applied-wrap">
    <div class="already-applied-icon"><?= icon('circle-check') ?></div>
    <h2 class="already-applied-title">أنت مقدم بالفعل!</h2>
    <p class="already-applied-msg">لقد قدمت على هذا البرنامج مسبقا. لا يمكن التقديم أكثر من مرة.</p>
    <a href="<?= BASE_URL ?>/apply" class="btn btn--outline btn--lg">← العودة للبرامج</a>
  </div>
  <style>
  .already-applied-wrap{text-align:center;padding:60px 20px;display:flex;flex-direction:column;align-items:center;gap:16px}
  .already-applied-icon{font-size:64px;line-height:1;animation:aa-pop .5s cubic-bezier(.34,1.56,.64,1) both}
  @keyframes aa-pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
  .already-applied-title{font-family:var(--fa);font-size:clamp(18px,4vw,28px);color:var(--td)}
  .already-applied-msg{font-size:15px;color:var(--tm);max-width:380px;line-height:1.7}
  </style>
  <?php else: ?>
  <?php if ($user && empty($user['telegram_username'])): ?>
  <div class="alert alert--warn" style="margin-bottom:20px"><?= icon('alert') ?> لم تضف معرف التيليغرام بعد. <a href="<?= BASE_URL ?>/account">أضفه الآن</a> حتى نتمكن من التواصل معك.</div>
  <?php endif; ?>
  <div class="apply-form-wrap">
    <div id="formMsg"></div>
    <?php if (empty($questions)): ?>
      <div style="text-align:center;color:var(--tm);padding:40px">لا توجد أسئلة في هذا النموذج بعد.</div>
    <?php else: ?>
    <?php if ($guest): ?>
    <div class="guest-note panel">
      <div class="guest-note-t"><?= icon('lock') ?> للتقديم تحتاج حسابا</div>
      <p>هذه معاينة لأسئلة الفورم. سجّل الدخول أو أنشئ حسابا لتتمكن من التقديم.</p>
      <div class="guest-note-b">
        <a href="<?= BASE_URL ?>/auth/login?next=<?= $_nextQ ?>" class="btn btn--gold">تسجيل الدخول</a>
        <a href="<?= BASE_URL ?>/auth/register?next=<?= $_nextQ ?>" class="btn btn--outline">إنشاء حساب</a>
      </div>
    </div>
    <?php endif; ?>
    <form <?= $guest ? 'id="applyPreview"' : 'id="applyForm"' ?>>
    <fieldset <?= $guest ? 'disabled' : '' ?> class="form-fs">
      <?= csrf_field() ?>
      <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
      <div class="form-stack">
        <?php foreach ($questions as $q):
          $opts = json_decode($q['options'] ?? '[]', true) ?: [];
        ?>
        <div class="form-group">
          <label class="form-label">
            <?= e($q['label']) ?>
            <?php if ($q['is_required']): ?><span class="required-star">*</span><?php endif; ?>
            <?php if ($q['type'] === 'checkbox' && !empty($opts)): ?><span class="form-hint">(يمكن اختيار أكثر من خيار)</span><?php endif; ?>
          </label>

          <?php switch ($q['type']):
            case 'long_text': ?>
              <textarea name="q_<?= $q['id'] ?>" class="form-input form-textarea" rows="4" placeholder="<?= e($q['placeholder'] ?? '') ?>" <?= $q['is_required'] ? 'required' : '' ?>></textarea>
            <?php break; case 'email': ?>
              <input type="email" name="q_<?= $q['id'] ?>" class="form-input" placeholder="<?= e($q['placeholder'] ?? 'بريدك الإلكتروني') ?>" <?= $q['is_required'] ? 'required' : '' ?>>
            <?php break; case 'phone': ?>
              <input type="tel" name="q_<?= $q['id'] ?>" class="form-input" placeholder="<?= e($q['placeholder'] ?? '+964 7xx xxx xxxx') ?>" <?= $q['is_required'] ? 'required' : '' ?>>
            <?php break; case 'number': ?>
              <input type="number" name="q_<?= $q['id'] ?>" class="form-input" placeholder="<?= e($q['placeholder'] ?? '') ?>" <?= $q['is_required'] ? 'required' : '' ?>>
            <?php break; case 'age': ?>
              <input type="number" name="q_<?= $q['id'] ?>" class="form-input" min="1" max="120" placeholder="<?= e($q['placeholder'] ?? 'عمرك') ?>" <?= $q['is_required'] ? 'required' : '' ?>>
            <?php break; case 'date': ?>
              <input type="date" name="q_<?= $q['id'] ?>" class="form-input" <?= $q['is_required'] ? 'required' : '' ?>>
            <?php break; case 'radio': ?>
              <div class="choice-group" role="radiogroup">
                <?php foreach ($opts as $opt): ?>
                <label class="choice-item">
                  <input type="radio" name="q_<?= $q['id'] ?>" value="<?= e($opt) ?>" <?= $q['is_required'] ? 'required' : '' ?>>
                  <span class="choice-box choice-box--radio"></span>
                  <span class="choice-label"><?= e($opt) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            <?php break; case 'checkbox': ?>
              <div class="choice-group" role="group">
                <?php foreach ($opts as $opt): ?>
                <label class="choice-item">
                  <input type="checkbox" name="q_<?= $q['id'] ?>[]" value="<?= e($opt) ?>">
                  <span class="choice-box choice-box--check"></span>
                  <span class="choice-label"><?= e($opt) ?></span>
                </label>
                <?php endforeach; ?>
                <?php if ($q['is_required']): ?>
                <input type="hidden" class="checkbox-req-guard" data-qid="<?= $q['id'] ?>" data-label="<?= e($q['label']) ?>">
                <?php endif; ?>
              </div>
            <?php break; case 'dropdown': ?>
              <select name="q_<?= $q['id'] ?>" class="form-select" <?= $q['is_required'] ? 'required' : '' ?>>
                <option value="">— اختر —</option>
                <?php foreach ($opts as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?>
              </select>
            <?php break; default: ?>
              <input type="text" name="q_<?= $q['id'] ?>" class="form-input" placeholder="<?= e($q['placeholder'] ?? '') ?>" <?= $q['is_required'] ? 'required' : '' ?>>
          <?php endswitch; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($user && !$guest): ?>
      <p style="font-size:13px;color:var(--tm);margin-top:12px">سيرفق معرف التيليغرام: <strong><?= $user['telegram_username'] ? e($user['telegram_username']) : '(غير محدد)' ?></strong> مع طلبك.</p>
      <?php endif; ?>
      </fieldset>
      <?php if (!$guest): ?>
      <div class="apply-submit-wrap">
        <?php $rcSiteKey = Config::get('RECAPTCHA_SITE_KEY', ''); if ($rcSiteKey): ?>
        <div class="recaptcha-container" style="margin-bottom:16px;display:flex;justify-content:center">
          <div class="g-recaptcha" data-sitekey="<?= e($rcSiteKey) ?>" data-callback="applyRecaptchaOk" data-expired-callback="applyRecaptchaExpired"></div>
        </div>
        <div id="rcError" class="alert alert--error" style="display:none;margin-bottom:12px">يرجى إكمال التحقق أولا.</div>
        <button type="submit" class="btn btn--gold btn--full btn--lg" id="submitBtn" disabled>إرسال الطلب ←</button>
        <?php else: ?>
        <button type="submit" class="btn btn--gold btn--full btn--lg" id="submitBtn">إرسال الطلب ←</button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; // alreadyApplied ?>
  <div style="margin-top:16px;text-align:center"><?php if(empty($alreadyApplied)): ?><a href="<?= BASE_URL ?>/apply" style="color:var(--tm);font-size:13px">← العودة للبرامج</a><?php endif; ?></div>
</div>

<style>
.choice-group{display:flex;flex-direction:column;gap:10px;margin-top:4px}
.choice-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px solid var(--border);border-radius:var(--r-md);cursor:pointer;transition:border-color .18s,background .18s;background:var(--card);user-select:none}
.choice-item:hover{border-color:var(--sage);background:var(--sage-faint)}
.choice-item input{display:none}
.choice-box{width:20px;height:20px;border:2px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .18s;background:var(--input-bg)}
.choice-box--radio{border-radius:50%}
.choice-box--check{border-radius:5px}
.choice-item:has(input:checked){border-color:var(--sage-dark);background:var(--sage-faint)}
.choice-item input:checked~.choice-box{border-color:var(--sage-dark);background:var(--sage-dark)}
.choice-box--radio::after{content:'';display:none;width:8px;height:8px;border-radius:50%;background:#fff}
.choice-box--check::after{content:'';display:none;width:5px;height:9px;border:2.5px solid #fff;border-top:none;border-right:none;transform:rotate(-45deg) translateY(-1px)}
.choice-item input:checked~.choice-box::after{display:block}
.choice-item input:checked~.choice-label{color:var(--sage-dark);font-weight:600}
.choice-label{font-size:15px;color:var(--td);line-height:1.4}
</style>
<style>.form-fs{border:0;padding:0;margin:0;min-width:0}.guest-note{margin-bottom:22px;text-align:center}.guest-note-t{font-family:var(--fa);font-weight:900;font-size:19px;color:#F8D98A;margin-bottom:6px}.guest-note p{margin:0 0 14px;color:var(--tb)}.guest-note-b{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}.form-fs:disabled .form-input,.form-fs:disabled .form-select,.form-fs:disabled .choice-item{opacity:.8;cursor:default}</style>

<?php if(Config::get('RECAPTCHA_SITE_KEY','') && empty($guestPreview)): ?>
<script nonce="<?= csp_nonce() ?>" src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<script nonce="<?= csp_nonce() ?>">
var _applyRcDone=false;
function applyRecaptchaOk(){_applyRcDone=true;var b=document.getElementById('submitBtn');if(b)b.disabled=false;var e=document.getElementById('rcError');if(e)e.style.display='none';}
function applyRecaptchaExpired(){_applyRcDone=false;var b=document.getElementById('submitBtn');if(b)b.disabled=true;}

(function(){
  var form=document.getElementById('applyForm');
  if(!form)return;
  form.addEventListener('submit',async function(e){
    e.preventDefault();
    var btn=document.getElementById('submitBtn'),msg=document.getElementById('formMsg');
    // reCAPTCHA check (only if widget present)
    var rcWidget=form.querySelector('.g-recaptcha');
    if(rcWidget&&!_applyRcDone){
      var re=document.getElementById('rcError');if(re)re.style.display='block';
      msg.scrollIntoView({behavior:'smooth',block:'nearest'});return;
    }
    // Validate required checkboxes
    var err=false;
    form.querySelectorAll('.checkbox-req-guard').forEach(function(g){
      if(err)return;
      var checked=form.querySelectorAll('input[name="q_'+g.dataset.qid+'[]"]:checked');
      if(!checked.length){msg.innerHTML='<div class="alert alert--error">'+g.dataset.label+' مطلوب — اختر خيارا على الأقل.</div>';err=true;}
    });
    if(err){msg.scrollIntoView({behavior:'smooth',block:'nearest'});return;}
    btn.disabled=true;btn.textContent='جار الإرسال…';msg.innerHTML='';
    try{
      var fd=new FormData(form);
      var res=await fetch('<?= BASE_URL ?>/apply/submit',{method:'POST',body:fd});
      var d=await res.json();
      if(d.csrf_refresh){
        // CSRF expired — update token and retry once
        form.querySelector('input[name="_csrf"]').value=d.new_token||'';
        btn.disabled=false;btn.textContent='إرسال الطلب ←';
        msg.innerHTML='<div class="alert alert--warn">انتهت الجلسة — حدّث الصفحة ثم أعد الإرسال.</div>';
        msg.scrollIntoView({behavior:'smooth',block:'nearest'});return;
      }
      if(d.already_applied||res.status===409){
        // Replace form with "already applied" UI
        var wrap=form.closest('.apply-form-wrap');
        if(wrap){wrap.innerHTML='<div class="already-applied-wrap"><div class="already-applied-icon"></div><h2 class="already-applied-title">أنت مقدم بالفعل!</h2><p class="already-applied-msg">لقد قدمت على هذا البرنامج مسبقا.</p><a href="<?= BASE_URL ?>/apply" class="btn btn--outline btn--lg">← العودة للبرامج</a></div>';return;}
      }
      if(d.success){
        msg.innerHTML='<div class="alert alert--success">'+d.message+'</div>';
        form.style.display='none';
      } else {
        var errTxt=d.errors?d.errors.map(function(e){return (e+'').replace(/</g,'&lt;');}).join('<br>'):((d.error||'حدث خطأ')+'').replace(/</g,'&lt;');
        msg.innerHTML='<div class="alert alert--error">'+errTxt+'</div>';
        // Reset reCAPTCHA after error
        if(typeof grecaptcha!=='undefined'){try{grecaptcha.reset();}catch(ex){}_applyRcDone=false;if(btn)btn.disabled=true;}
      }
    }catch(ex){msg.innerHTML='<div class="alert alert--error">خطأ في الشبكة. تحقق من اتصالك.</div>';}
    btn.disabled=false;btn.textContent='إرسال الطلب ←';
    msg.scrollIntoView({behavior:'smooth',block:'nearest'});
  });
})();
</script>
