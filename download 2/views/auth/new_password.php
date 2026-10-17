<div class="auth-simple"><div class="auth-card">
  <div class="auth-logo"><?php if (brand_has_logo()): ?><?= brand_logo_html('auth-logo-img') ?><?php else: ?><span class="nav-compass"><?= icon('compass', 28) ?></span><?php endif; ?></div>
  <h1>كلمة مرور جديدة</h1>
  <p class="auth-sub">تم التحقق من هويتك. اختر كلمة مرورك الجديدة.</p>
  <?php if ($e=flash('error')): ?><div class="alert alert--error"><?= $e ?></div><?php endif; ?>
  <form action="<?= BASE_URL ?>/auth/new-password" method="POST" class="auth-form" id="newPassForm">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">كلمة المرور الجديدة</label>
      <div class="input-wrap"><input type="password" name="password" id="pwd1" class="form-input" required autofocus autocomplete="new-password"><button type="button" class="input-eye" aria-label="إظهار كلمة المرور" aria-pressed="false"><span class="eye-show"><?= icon('eye') ?></span><span class="eye-hide"><?= icon('eye-off') ?></span></button></div>
      <div style="margin-top:8px"><div style="height:4px;background:var(--border-l);overflow:hidden"><div id="strengthBar" style="height:100%;width:0;transition:width .3s,background .3s"></div></div><div id="strengthLabel" style="font-size:11px;color:var(--tm);margin-top:4px"></div></div>
    </div>
    <div class="form-group"><label class="form-label">تأكيد كلمة المرور</label>
      <div class="input-wrap"><input type="password" name="confirm_password" id="pwd2" class="form-input" required autocomplete="new-password"><button type="button" class="input-eye" aria-label="إظهار كلمة المرور" aria-pressed="false"><span class="eye-show"><?= icon('eye') ?></span><span class="eye-hide"><?= icon('eye-off') ?></span></button></div>
      <div id="matchMsg" style="font-size:12px;margin-top:4px"></div>
    </div>
    <div class="pw-checks">
      <div id="c8" class="chk">8 أحرف على الأقل</div><div id="cUpp" class="chk">حرف كبير (A-Z)</div><div id="cNum" class="chk">رقم (0-9)</div><div id="cMtch" class="chk">كلمتا المرور متطابقتان</div>
    </div>
    <button type="submit" class="btn btn--gold btn--full btn--lg" id="submitBtn" disabled>حفظ كلمة المرور الجديدة</button>
  </form>
</div></div>
<script nonce="<?= csp_nonce() ?>">
(function(){
var p1=document.getElementById('pwd1'),p2=document.getElementById('pwd2'),st={len:false,upp:false,num:false,match:false};
function mark(id,ok){document.getElementById(id).classList.toggle('ok',ok);}
function upd(){
  var v=p1.value; st.len=v.length>=8; st.upp=/[A-Z]/.test(v); st.num=/[0-9]/.test(v); st.match=v===p2.value&&p2.value.length>0;
  mark('c8',st.len);mark('cUpp',st.upp);mark('cNum',st.num);mark('cMtch',st.match);
  var score=[st.len,st.upp,st.num,v.length>=12,/[^a-zA-Z0-9]/.test(v)].filter(Boolean).length;
  var L=[['20%','#e74c3c','ضعيفة جدا'],['40%','#e67e22','ضعيفة'],['60%','#fcba04','متوسطة'],['80%','#27ae60','جيدة'],['100%','#1e8449','قوية جدا']][Math.max(0,Math.min(score-1,4))];
  var bar=document.getElementById('strengthBar'),lb=document.getElementById('strengthLabel');
  bar.style.width=v.length?L[0]:'0';bar.style.background=L[1];lb.textContent=v.length?L[2]:'';
  var m=document.getElementById('matchMsg');m.textContent=p2.value?(st.match?'متطابقتان':'غير متطابقتين'):'';m.style.color=st.match?'#27ae60':'#c0392b';
  document.getElementById('submitBtn').disabled=!(st.len&&st.upp&&st.num&&st.match);
}
p1.addEventListener('input',upd);p2.addEventListener('input',upd);
})();
</script>
