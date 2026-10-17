<?php
$_rl = function (array $rows, string $pfx, string $titleHint, int $bodyRows = 5) {
    if (!$rows) $rows = [['title' => '', 'body' => '']];
    foreach ($rows as $i => $s): ?>
    <div class="rep-row" style="background:var(--bg-2);border:1px solid var(--border);border-radius:var(--r-md);padding:14px">
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
        <span class="rep-num" style="font-weight:800;color:var(--navy);min-width:24px"><?= $i + 1 ?></span>
        <input type="text" name="<?= $pfx ?>_title[]" class="form-input" placeholder="<?= e($titleHint) ?>" value="<?= e($s['title']) ?>" style="flex:1">
        <button type="button" class="btn btn--xs btn--outline" data-rep="up" title="أعلى"><?= icon('chevron-up') ?></button>
        <button type="button" class="btn btn--xs btn--outline" data-rep="down" title="أسفل"><?= icon('chevron-down') ?></button>
        <button type="button" class="btn btn--xs btn--danger" data-rep="del" title="حذف"><?= icon('trash') ?></button>
      </div>
      <textarea name="<?= $pfx ?>_body[]" class="form-input form-textarea" rows="<?= $bodyRows ?>" placeholder="اكتب هنا بحرية…"><?= e($s['body']) ?></textarea>
    </div>
<?php endforeach; };
$_navRows = nav_items();
[$_l1] = brand_logo_urls();
$_fmt = 'تنسيق: **نص عريض** — سطر يبدأ بـ "- " قائمة نقطية — "1. " قائمة مرقمة — سطر فارغ يفصل الفقرات';
?>
<form action="<?= BASE_URL ?>/admin/content/save" method="POST" enctype="multipart/form-data" id="contentForm">
  <?= csrf_field() ?>
  <div class="acard">
    <div class="acard-header">
      <h3>محتوى الموقع</h3>
      <div style="display:flex;gap:8px">
        <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn--sm btn--outline"><?= icon('external') ?> معاينة</a>
        <button type="submit" class="btn btn--sm btn--gold"><?= icon('save') ?> حفظ</button>
      </div>
    </div>
    <div class="acard-body">
      <div class="form-tabs">
        <?php foreach(['hero'=>'الرئيسية والشعار','sections'=>'من نحن','rules'=>'القوانين','nav'=>'القائمة','cert'=>'الشهادة','footer'=>'الفوتر','terms'=>'الشروط'] as $k=>$l): ?>
        <button type="button" class="tab-btn <?= $k==='hero'?'active':'' ?>" data-tab="<?= $k ?>"><?= $l ?></button>
        <?php endforeach; ?>
      </div>

      <!-- ── الرئيسية والشعار ── -->
      <div class="tab-panel active" id="tab-hero">
        <div class="form-stack">
          <div style="background:var(--bg-2);border:1px solid var(--border);border-radius:var(--r-md);padding:14px">
            <div style="font-weight:800;margin-bottom:8px">الشعار (يظهر بالزاوية العلوية بدل الأيقونة، وبالشهادات وأيقونة التطبيق)</div>
            <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
              <div style="width:84px;height:84px;border:2px solid var(--border);display:grid;place-items:center;background:var(--bg)">
                <?php if ($_l1): ?><img src="<?= e(brand_logo_src($_l1)) ?>" alt="" style="max-width:100%;max-height:100%"><?php else: ?><?= brand_emblem_svg(64) ?><?php endif; ?>
              </div>
              <div style="flex:1;min-width:200px">
                <input type="file" id="logoQuick" class="form-input" accept="image/png,image/jpeg,image/webp" style="padding:6px">
                <div style="display:flex;gap:8px;margin-top:8px;align-items:center">
                  <button type="button" class="btn btn--sm btn--gold" id="logoQuickBtn"><?= icon('upload') ?> رفع الشعار</button>
                  <?php if ($_l1): ?><button type="button" class="btn btn--sm btn--danger" id="logoQuickDel"><?= icon('trash') ?> حذف</button><?php endif; ?>
                  <small id="logoQuickMsg" style="color:var(--tm)"></small>
                </div>
                <small style="color:var(--tm);font-size:12px">لشعارين مختلفين (فاتح وغامق) استخدم <a href="<?= BASE_URL ?>/admin/settings">الإعدادات</a>.</small>
              </div>
            </div>
          </div>
          <div class="form-group"><label class="form-label">اسم الموقع</label><input type="text" name="site_name" class="form-input" value="<?= e($settings['site_name']??'') ?>"></div>
          <div class="form-group"><label class="form-label">العنوان تحت القائمة (السطر الأول)</label><textarea name="hero_title" class="form-input form-textarea" rows="2"><?= e(str_replace('\n',"\n",$settings['hero_title']??'')) ?></textarea></div>
          <div class="form-group"><label class="form-label">وصف الموقع (يظهر بمحركات البحث وبأيقونة التطبيق)</label><textarea name="hero_subtitle" class="form-input form-textarea" rows="2"><?= e($settings['hero_subtitle']??'') ?></textarea></div>
          <div class="form-group"><label class="form-label">نص زر الانضمام</label><input type="text" name="hero_cta_text" class="form-input" value="<?= e($settings['hero_cta_text']??'انضم إلينا') ?>"></div>
          <p style="font-size:13px;color:var(--tm);margin:0"><?= icon('info') ?> صور الشريط المتحرك تأتي من <a href="<?= BASE_URL ?>/admin/achievements">الإنجازات</a>، والإدارة العليا من <a href="<?= BASE_URL ?>/admin/team">الفريق</a>.</p>
        </div>
      </div>

      <!-- ── من نحن ── -->
      <div class="tab-panel" id="tab-sections">
        <p style="font-size:13px;color:var(--tm);margin:0 0 14px">كل قسم عنوان وكتابة حرة (من نحن، رسالتنا، هدفنا…) ويظهر بالصفحة الرئيسية بنفس الترتيب. اترك العنوان والنص فارغين لحذف القسم.</p>
        <div style="background:var(--bg-2);border:1px solid var(--border);border-radius:var(--r-md);padding:14px;margin-bottom:14px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
          <div style="width:64px;border:2px solid var(--border);overflow:hidden">
            <?php if (!empty($settings['about_image'])): ?><img src="<?= BASE_URL . e($settings['about_image']) ?>" alt="" style="width:100%;display:block"><?php else: ?><?= pixel_avatar_svg('about', 64) ?><?php endif; ?>
          </div>
          <div style="flex:1;min-width:200px"><label class="form-label">صورة بطاقة "من نحن" (الأول فقط)</label><input type="file" name="about_image" class="form-input" accept="image/*" style="padding:6px">
            <?php if (!empty($settings['about_image'])): ?><label style="font-size:12px"><input type="checkbox" name="about_image_remove" value="1"> حذف الصورة</label><?php endif; ?></div>
        </div>
        <div class="rep-list" data-list="sec" style="display:flex;flex-direction:column;gap:12px"><?php $_rl(content_sections(), 'sec', 'عنوان القسم — مثال: من نحن', 6); ?></div>
        <div style="margin-top:12px"><button type="button" class="btn btn--sm btn--green" data-add="sec"><?= icon('plus') ?> إضافة قسم</button> <small style="color:var(--tm);font-size:12px"><?= $_fmt ?></small></div>
      </div>

      <!-- ── القوانين ── -->
      <div class="tab-panel" id="tab-rules">
        <div class="form-group" style="margin-bottom:14px"><label class="form-label">عنوان القسم</label><input type="text" name="rules_title" class="form-input" value="<?= e($settings['rules_title']??'القوانين') ?>"></div>
        <p style="font-size:13px;color:var(--tm);margin:0 0 14px">كل قانون عنوان ونص. يظهر قسم القوانين بالرئيسية فقط إذا أضفت قانونا واحدا على الأقل.</p>
        <div class="rep-list" data-list="rule" style="display:flex;flex-direction:column;gap:12px"><?php $_rl(rules_items(), 'rule', 'عنوان القانون', 3); ?></div>
        <div style="margin-top:12px"><button type="button" class="btn btn--sm btn--green" data-add="rule"><?= icon('plus') ?> إضافة قانون</button></div>
      </div>

      <!-- ── القائمة ── -->
      <div class="tab-panel" id="tab-nav">
        <p style="font-size:13px;color:var(--tm);margin:0 0 14px">روابط شريط التنقل. الرابط: مسار داخلي مثل <code>/challenges</code> أو قسم بالرئيسية مثل <code>/#team</code> أو رابط كامل.</p>
        <div class="rep-list" data-list="nav" style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($_navRows as $i => $n): ?>
          <div class="rep-row" style="display:flex;gap:8px;align-items:center;background:var(--bg-2);border:1px solid var(--border);border-radius:var(--r-md);padding:10px">
            <span class="rep-num" style="font-weight:800;color:var(--navy);min-width:24px"><?= $i + 1 ?></span>
            <input type="text" name="nav_label[]" class="form-input" placeholder="الاسم" value="<?= e($n['label']) ?>" style="flex:1">
            <input type="text" name="nav_url[]" class="form-input" placeholder="/رابط" value="<?= e($n['url']) ?>" style="flex:1;direction:ltr;text-align:left">
            <button type="button" class="btn btn--xs btn--outline" data-rep="up"><?= icon('chevron-up') ?></button>
            <button type="button" class="btn btn--xs btn--outline" data-rep="down"><?= icon('chevron-down') ?></button>
            <button type="button" class="btn btn--xs btn--danger" data-rep="del"><?= icon('trash') ?></button>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:12px"><button type="button" class="btn btn--sm btn--green" data-add="nav"><?= icon('plus') ?> إضافة رابط</button></div>
      </div>

      <!-- ── الشهادة ── -->
      <div class="tab-panel" id="tab-cert">
        <div class="form-stack">
          <p style="font-size:13px;color:var(--tm);margin:0"><?= icon('info') ?> الشهادة تستخدم الشعار تلقائيا. هنا تحدد صاحب التوقيع.</p>
          <div class="form-row-2">
            <div class="form-group"><label class="form-label">اسم الموقّع</label><input type="text" name="cert_signer_name" class="form-input" value="<?= e($settings['cert_signer_name']??'') ?>"></div>
            <div class="form-group"><label class="form-label">صفته</label><input type="text" name="cert_signer_title" class="form-input" value="<?= e($settings['cert_signer_title']??'') ?>"></div>
          </div>
        </div>
      </div>

      <!-- ── الفوتر ── -->
      <div class="tab-panel" id="tab-footer">
        <div class="form-stack">
          <div class="form-group"><label class="form-label">نص الفوتر</label><input type="text" name="footer_text" class="form-input" value="<?= e($settings['footer_text']??'') ?>"></div>
          <div class="form-group"><label class="form-label">نص صفحة "تواصل معنا"</label><textarea name="contact_text" class="form-input form-textarea" rows="3"><?= e($settings['contact_text']??'') ?></textarea></div>
          <div class="form-group"><label class="form-label">البريد التواصلي</label><input type="email" name="contact_email" class="form-input" value="<?= e($settings['contact_email']??'') ?>"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div class="form-group"><label class="form-label">قناة التليجرام</label><input type="url" name="social_telegram" class="form-input" value="<?= e(($settings['social_telegram']??'') ?: ($settings['social_twitter']??'')) ?>" placeholder="https://t.me/..."></div>
            <div class="form-group"><label class="form-label">إنستغرام</label><input type="url" name="social_instagram" class="form-input" value="<?= e($settings['social_instagram']??'') ?>"></div>
            <div class="form-group"><label class="form-label">لينكدإن</label><input type="url" name="social_linkedin" class="form-input" value="<?= e($settings['social_linkedin']??'') ?>"></div>
          </div>
        </div>
      </div>

      <!-- ── الشروط ── -->
      <div class="tab-panel" id="tab-terms">
        <div class="form-group">
          <label class="form-label">محتوى الشروط والأحكام (صفحة /terms)</label>
          <textarea name="terms_content" class="form-input form-textarea" rows="12"><?= e($settings['terms_content']??'') ?></textarea>
        </div>
      </div>
    </div>
  </div>
</form>

<script nonce="<?= csp_nonce() ?>">
document.querySelectorAll('.tab-btn').forEach(function(b){b.addEventListener('click',function(){
  document.querySelectorAll('.tab-btn').forEach(function(x){x.classList.remove('active');});
  document.querySelectorAll('.tab-panel').forEach(function(x){x.classList.remove('active');});
  this.classList.add('active'); document.getElementById('tab-'+this.dataset.tab).classList.add('active');
});});
(function(){
  function renum(list){list.querySelectorAll('.rep-row').forEach(function(r,i){var n=r.querySelector('.rep-num');if(n)n.textContent=i+1;});}
  document.querySelectorAll('[data-add]').forEach(function(btn){btn.addEventListener('click',function(){
    var list=document.querySelector('.rep-list[data-list="'+btn.dataset.add+'"]');
    var d=list.querySelector('.rep-row').cloneNode(true);
    d.querySelectorAll('input,textarea').forEach(function(x){x.value='';});
    list.appendChild(d); renum(list);
  });});
  document.addEventListener('click',function(e){
    var b=e.target.closest('[data-rep]'); if(!b)return;
    var row=b.closest('.rep-row'), list=row.parentNode, a=b.dataset.rep;
    if(a==='del'){ if(list.querySelectorAll('.rep-row').length>1){row.remove();}else{row.querySelectorAll('input,textarea').forEach(function(x){x.value='';});} }
    if(a==='up'&&row.previousElementSibling){list.insertBefore(row,row.previousElementSibling);}
    if(a==='down'&&row.nextElementSibling){list.insertBefore(row.nextElementSibling,row);}
    renum(list);
  });
  /* quick logo upload (sets both light + dark slots) */
  var inp=document.getElementById('logoQuick'), btn=document.getElementById('logoQuickBtn'), msg=document.getElementById('logoQuickMsg'), del=document.getElementById('logoQuickDel');
  function post(url,fd){return fetch(url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd}).then(function(r){return r.json();});}
  function mk(slot){var fd=new FormData();fd.append('_csrf','<?= csrf_token() ?>');fd.append('slot',slot);return fd;}
  if(btn) btn.addEventListener('click',function(){
    if(!inp.files||!inp.files[0]){msg.textContent='اختر ملفا أولا.';return;}
    btn.disabled=true; msg.textContent='جاري الرفع…';
    var f1=mk('light'); f1.append('logo',inp.files[0]);
    var f2=mk('dark');  f2.append('logo',inp.files[0]);
    post('<?= BASE_URL ?>/admin/settings/logo',f1).then(function(d){ if(!d.ok) throw new Error(d.error||'فشل'); return post('<?= BASE_URL ?>/admin/settings/logo',f2); })
      .then(function(d){ if(!d.ok) throw new Error(d.error||'فشل'); msg.textContent='تم'; location.reload(); })
      .catch(function(e){ btn.disabled=false; msg.textContent=e.message||'فشل'; });
  });
  if(del) del.addEventListener('click',function(){
    if(!confirm('حذف الشعار؟'))return;
    post('<?= BASE_URL ?>/admin/settings/logo/remove',mk('light')).then(function(){return post('<?= BASE_URL ?>/admin/settings/logo/remove',mk('dark'));}).then(function(){location.reload();});
  });
})();
</script>
