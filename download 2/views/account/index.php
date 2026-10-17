<div class="page-wrap" style="max-width:940px">
  <h1 class="page-title">حسابي</h1>
  <?php if ($s=flash('success')): ?><div class="alert alert--success" style="margin-bottom:16px"><?= e($s) ?></div><?php endif; ?>
  <?php if ($e=flash('error')):   ?><div class="alert alert--error"   style="margin-bottom:16px"><?= e($e) ?></div><?php endif; ?>

  <?php
    $_doneN = count(array_filter($steps, fn($x) => $x['done']));
    $_allDone = $_doneN === count($steps);
    if (!$_allDone || $welcome):
  ?>
  <section class="onb panel" aria-label="الخطوات الأولى">
    <div class="onb-head">
      <h2><?= $welcome ? 'أهلا بك يا ' . e(mb_strstr($user['display_name'] . ' ', ' ', true)) . '! جهّز حسابك بأربع خطوات' : 'خطواتك الأولى' ?></h2>
      <span class="onb-count"><?= ar_digits($_doneN) ?> من <?= ar_digits(count($steps)) ?></span>
    </div>
    <div class="onb-bar" aria-hidden="true"><?php foreach ($steps as $st): ?><i class="<?= $st['done'] ? 'on' : '' ?>"></i><?php endforeach; ?></div>
    <ol class="onb-list">
      <?php foreach ($steps as $st): ?>
      <li class="<?= $st['done'] ? 'is-done' : '' ?>">
        <span class="onb-tick"><?= $st['done'] ? icon('check', 16) : '' ?></span>
        <span class="onb-txt"><strong><?= e($st['title']) ?></strong><small><?= e($st['hint']) ?></small></span>
        <?php if (!$st['done'] && $st['href'] !== ''): ?><a class="btn btn--xs btn--gold" href="<?= e($st['href']) ?>"><?= e($st['cta']) ?></a><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

  <div class="account-grid">
    <!-- Sidebar -->
    <div class="account-sidebar">
      <div class="account-card" style="text-align:center">
        <?php if (!empty($user['avatar'])): ?>
        <div class="account-avatar" style="padding:0;overflow:hidden">
          <img src="<?= BASE_URL . e($user['avatar']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block">
        </div>
        <?php else: ?>
        <div class="account-avatar"><?= mb_substr($user['display_name'],0,1) ?></div>
        <?php endif; ?>
        <form id="acc-avatar" action="<?= BASE_URL ?>/account/avatar" method="POST" enctype="multipart/form-data" style="margin-top:10px">
          <?= csrf_field() ?>
          <label style="cursor:pointer;font-size:12px;color:var(--sage-dark);display:inline-flex;align-items:center;gap:5px">
            <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none" class="auto-submit">
            <?= icon('camera') ?> تغيير الصورة
          </label>
          <noscript>
            <button type="submit" class="btn btn--outline btn--sm" style="margin-top:6px;font-size:12px">رفع</button>
          </noscript>
        </form>
        <div style="font-weight:700;font-size:16px;color:var(--td)"><?= e($user['display_name']) ?></div>
        <div style="font-size:13px;color:var(--tm);margin-top:4px"><?= e($user['email']) ?></div>
        <?php if ($user['telegram_username']): ?><div style="font-size:12px;color:var(--sage-dark);margin-top:4px"><?= e($user['telegram_username']) ?></div><?php endif; ?>
        <div style="margin-top:8px">
          <span class="badge" style="background:var(--sage-faint);color:var(--sage-dark)">
            <?= ($user['role']==='super_admin'?'مشرف أعلى':($user['role']==='admin'?'مشرف':($user['role']==='moderator'?'محرر':'عضو'))) ?>
          </span>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/auth/sessions" class="btn btn--outline btn--full"><?= icon('monitor') ?> إدارة الجلسات</a>
      <a href="<?= BASE_URL ?>/account/certificates" class="btn btn--outline btn--full"><?= icon('award') ?> شهاداتي</a>
    </div>

    <!-- Main content -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Personal info -->
      <div class="account-card">
        <h3>المعلومات الشخصية</h3>
        <form action="<?= BASE_URL ?>/account/update" method="POST" class="form-stack">
          <?= csrf_field() ?>
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">الاسم الكامل</label>
              <input type="text" name="display_name" class="form-input" value="<?= e($user['display_name']) ?>" required minlength="2">
            </div>
            <div class="form-group">
              <label class="form-label">اسم المستخدم</label>
              <input type="text" name="username" class="form-input" value="<?= e($user['username']) ?>" required pattern="[a-zA-Z0-9_]{3,30}">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">معرف التيليغرام <span class="form-hint">— مطلوب للتواصل</span></label>
            <input type="text" id="tg-field" name="telegram_username" autocapitalize="none" spellcheck="false" class="form-input <?= empty($user['telegram_username'])?'form-input--warn':'' ?>"
              value="<?= e($user['telegram_username']??'') ?>" placeholder="@username">
            <?php if (empty($user['telegram_username'])): ?>
            <div style="font-size:12px;color:var(--warn-fg);margin-top:4px"><?= icon('alert') ?> يرجى إضافة معرف التيليغرام لنتمكن من التواصل معك.</div>
            <?php endif; ?>
          </div>
          <label class="check-label">
            <input type="checkbox" name="email_notify" <?= $user['email_notify']?'checked':'' ?>>
            <span>أرغب في تلقي إشعارات البرامج</span>
          </label>
          <div><button class="btn btn--gold">حفظ التغييرات</button></div>
        </form>
      </div>

      <!-- Change Email -->
      <div class="account-card">
        <h3><?= icon('refresh') ?> تغيير البريد الإلكتروني</h3>
        <p style="color:var(--tm);font-size:13px;margin-bottom:14px">
          البريد الحالي: <strong><?= e($user['email']) ?></strong><br>
          سيرسل رمز OTP إلى بريدك الحالي للتأكيد.
        </p>
        <form action="<?= BASE_URL ?>/account/email/request" method="POST" class="form-stack">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">البريد الجديد</label>
            <input type="email" name="new_email" class="form-input" placeholder="new@example.com" required>
          </div>
          <button class="btn btn--outline">إرسال رمز التحقق</button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="account-card">
        <h3><?= icon('key') ?> تغيير كلمة المرور</h3>
        <p style="color:var(--tm);font-size:13px;margin-bottom:14px">سيرسل رمز OTP إلى بريدك للتأكيد قبل تغيير كلمة المرور.</p>
        <form action="<?= BASE_URL ?>/account/password/request" method="POST" class="form-stack">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">كلمة المرور الحالية</label>
            <input type="password" name="current_password" class="form-input" required>
          </div>
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">الجديدة</label>
              <input type="password" name="new_password" class="form-input" required minlength="8">
            </div>
            <div class="form-group">
              <label class="form-label">التأكيد</label>
              <input type="password" name="confirm_password" class="form-input" required>
            </div>
          </div>
          <div><button class="btn btn--outline">إرسال رمز التحقق</button></div>
        </form>
      </div>

      <!-- 2FA Security -->
      <div class="account-card">
        <h3><?= icon('lock') ?> التحقق بخطوتين (2FA)</h3>
        <p style="color:var(--tm);font-size:13px;margin-bottom:16px">
          يمكنك تفعيل طريقة واحدة أو كلتيهما. إذا فعلت الاثنتين ستختار الطريقة عند كل دخول.
        </p>

        <!-- TOTP (Google Authenticator) -->
        <div style="border:1px solid var(--border);border-radius:var(--r-md);padding:16px;margin-bottom:14px">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <div style="display:flex;align-items:center;gap:10px">
              <span style="font-size:24px"><?= icon('phone') ?></span>
              <div>
                <div style="font-weight:700">Google Authenticator</div>
                <div style="font-size:12px;color:var(--tm)">Authy · Microsoft Authenticator · أي تطبيق TOTP</div>
              </div>
            </div>
            <span style="font-size:12px;font-weight:700;color:<?= $user['two_fa_enabled'] ? 'var(--sage-dark)' : '#999' ?>">
              <?= $user['two_fa_enabled'] ? (icon('circle-check') . ' مفعل') : (icon('circle') . ' غير مفعل') ?>
            </span>
          </div>
          <div style="margin-top:12px">
            <?php if ($user['two_fa_enabled']): ?>
            <form action="<?= BASE_URL ?>/account/2fa/disable" method="POST" style="display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap">
              <?= csrf_field() ?>
              <input type="text" name="code" placeholder="000000" maxlength="6" pattern="\d{6}" inputmode="numeric"
                     class="form-input" style="width:130px;font-family:monospace;text-align:center" required>
              <button class="btn btn--outline btn--sm" style="color:#c0392b;border-color:#c0392b">تعطيل</button>
            </form>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/account/2fa/setup" class="btn btn--gold btn--sm"><?= icon('settings') ?> إعداد وتفعيل</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Email 2FA -->
        <div style="border:1px solid var(--border);border-radius:var(--r-md);padding:16px">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <div style="display:flex;align-items:center;gap:10px">
              <span style="font-size:24px"><?= icon('mail') ?></span>
              <div>
                <div style="font-weight:700">رمز عبر البريد الإلكتروني</div>
                <div style="font-size:12px;color:var(--tm)">OTP يرسل إلى <?= e($user['email']) ?></div>
              </div>
            </div>
            <span style="font-size:12px;font-weight:700;color:<?= !empty($user['email_2fa_enabled']) ? 'var(--sage-dark)' : '#999' ?>">
              <?= !empty($user['email_2fa_enabled']) ? (icon('circle-check') . ' مفعل') : (icon('circle') . ' غير مفعل') ?>
            </span>
          </div>
          <div style="margin-top:12px">
            <?php if (!empty($user['email_2fa_enabled'])): ?>
            <form action="<?= BASE_URL ?>/account/2fa/email/disable" method="POST" style="display:inline">
              <?= csrf_field() ?>
              <button class="btn btn--outline btn--sm" style="color:#c0392b;border-color:#c0392b"
                      onclick="return confirm('تأكيد تعطيل التحقق عبر البريد؟')">تعطيل</button>
            </form>
            <?php else: ?>
            <form action="<?= BASE_URL ?>/account/2fa/email/enable" method="POST" style="display:inline">
              <?= csrf_field() ?>
              <button class="btn btn--gold btn--sm"><?= icon('circle-check') ?> تفعيل</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Delete Account -->
      <div class="account-card" style="border:1px solid #fed7d7">
        <h3 style="color:#c53030"><?= icon('alert') ?> حذف الحساب</h3>
        <p style="color:var(--tm);font-size:13px;margin-bottom:16px">
          سيتم حذف حسابك وجميع بياناتك بشكل نهائي لا يمكن التراجع عنه.
          سيرسل رمز OTP إلى بريدك للتأكيد.
        </p>
        <form action="<?= BASE_URL ?>/account/delete/request" method="POST">
          <?= csrf_field() ?>
          <button class="btn btn--sm" style="background:#c53030;color:#fff;border:none"
                  onclick="return confirm('هل أنت متأكد من رغبتك في حذف حسابك نهائيا؟')">
            <?= icon('trash') ?> طلب حذف الحساب
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
