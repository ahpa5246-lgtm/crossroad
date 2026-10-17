<?php
declare(strict_types=1);

class AccountController
{
    private PDO $db;
    public function __construct() { require_auth(); $this->db = Database::getInstance(); }

    // ── Main page ─────────────────────────────────────────────

    public function index(): void
    {
        $user = auth();
        $applied = 0;
        try {
            $q = $this->db->prepare("SELECT COUNT(*) FROM form_submissions WHERE user_id=?");
            $q->execute([(int)$user['id']]);
            $applied = (int)$q->fetchColumn();
        } catch (Throwable) {}
        $steps = [
            ['done' => true,                                    'title' => 'إنشاء الحساب',            'hint' => 'تم، أهلا بك.',                                         'href' => '',                 'cta' => ''],
            ['done' => !empty($user['telegram_username']),      'title' => 'إضافة معرف التيليغرام',   'hint' => 'نتواصل معك عليه عند قبول طلبك.',                       'href' => '#tg-field',        'cta' => 'أضفه'],
            ['done' => !empty($user['avatar']),                 'title' => 'رفع صورة شخصية',          'hint' => 'تظهر على مقالاتك وبطاقتك.',                            'href' => '#acc-avatar',      'cta' => 'ارفع صورة'],
            ['done' => $applied > 0,                            'title' => 'التقديم على برنامج',      'hint' => 'اختر من البرامج المفتوحة وقدّم طلبك.',                  'href' => BASE_URL . '/apply', 'cta' => 'استعرض البرامج'],
        ];
        render('account.index', ['user' => $user, 'steps' => $steps, 'welcome' => isset($_GET['welcome']), 'pageTitle' => 'حسابي']);
    }

    public function notifications(): void {
        require_auth();
        $uid = (int)auth()['id'];
        $this->db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
        $notifs = $this->db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100");
        $notifs->execute([$uid]);
        render('account.notifications', ['notifications'=>$notifs->fetchAll(), 'pageTitle'=>'الإشعارات']);
    }

    public function notificationCount(): void {
        $user = auth();
        if (!$user) { json_out(['unread' => 0]); }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->execute([(int)$user['id']]);
        json_out(['unread' => (int)$stmt->fetchColumn()]);
    }

    public function markNotificationsRead(): void {
        require_auth(); verify_csrf();
        $this->db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([(int)auth()['id']]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function certificates(): void
    {
        $user  = auth();
        $stmt  = $this->db->prepare("
            SELECT c.*, af.title as form_title, ch.title as challenge_title, co.title as course_title
            FROM   certificates c
            LEFT JOIN application_forms af ON c.form_id = af.id
            LEFT JOIN challenges ch        ON c.challenge_id = ch.id
            LEFT JOIN courses co           ON c.course_id = co.id
            WHERE  c.user_id = ?
            ORDER  BY c.issued_at DESC
        ");
        $stmt->execute([$user['id']]);
        render('account.certificates', ['certificates' => $stmt->fetchAll(), 'pageTitle' => 'شهاداتي']);
    }

    // ── Profile update (non-sensitive fields only) ────────────

    public function update(): void
    {
        verify_csrf();
        $me    = auth();
        $uid   = (int)$me['id'];
        $name  = trim($_POST['display_name'] ?? '');
        $uname = trim(strtolower($_POST['username'] ?? ''));
        $tg    = trim($_POST['telegram_username'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2)                        $errors[] = 'الاسم قصير جدا.';
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $uname)) $errors[] = 'اسم المستخدم غير صالح.';
        if (!validate_telegram($tg))                     $errors[] = 'معرف التيليغرام غير صالح.';

        if (!$errors) {
            $chk = $this->db->prepare("SELECT id FROM users WHERE username=? AND id != ? LIMIT 1");
            $chk->execute([$uname, $uid]);
            if ($chk->fetch()) $errors[] = 'اسم المستخدم مستخدم من حساب آخر.';
        }
        if ($errors) redirect('/account', ['error' => implode('<br>', $errors)]);

        $tgFormatted = $tg ? format_telegram($tg) : null;
        $this->db->prepare("UPDATE users SET display_name=?,username=?,telegram_username=?,email_notify=?,updated_at=datetime('now') WHERE id=?")
                 ->execute([$name, $uname, $tgFormatted, isset($_POST['email_notify']) ? 1 : 0, $uid]);
        Logger::info('account.update', "تحديث ملف المستخدم #{$uid}");
        redirect('/account', ['success' => 'تم تحديث معلوماتك بنجاح.']);
    }

    // ── Change Password (with OTP) ────────────────────────────

    public function changePasswordRequest(): void
    {
        verify_csrf();
        $me  = auth(); $uid = (int)$me['id'];
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password']     ?? '';
        $con = $_POST['confirm_password'] ?? '';

        if (!password_verify($cur, $me['password']))
            redirect('/account', ['error' => 'كلمة المرور الحالية غير صحيحة.']);
        if (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new))
            redirect('/account', ['error' => 'كلمة المرور الجديدة ضعيفة (8+ أحرف، حرف كبير، رقم).']);
        if ($new !== $con)
            redirect('/account', ['error' => 'تأكيد كلمة المرور لا يتطابق.']);

        // Store hashed password in session, then send OTP
        $_SESSION['pending_pw_change'] = [
            'uid'  => $uid,
            'hash' => password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]),
        ];
        try {
            $this->storeAndSendOtp($me['email'], 'change_password', $me['display_name']);
        } catch (Throwable $e) {
            unset($_SESSION['pending_pw_change']);
            redirect('/account', ['error' => 'فشل إرسال رمز التحقق. تحقق من إعدادات البريد.']);
        }
        redirect('/account/password/verify');
    }

    public function changePasswordVerifyForm(): void
    {
        if (empty($_SESSION['pending_pw_change'])) redirect('/account');
        $me     = auth();
        $masked = $this->maskEmail($me['email']);
        render('account.otp_verify', [
            'pageTitle'   => 'تأكيد تغيير كلمة المرور',
            'maskedEmail' => $masked,
            'purpose'     => 'change_password',
            'backUrl'     => '/account',
        ]);
    }

    public function changePasswordVerify(): void
    {
        verify_csrf();
        if (empty($_SESSION['pending_pw_change'])) redirect('/account');
        if (!rate_limit('otp_verify', 5, 300))
            redirect('/account/password/verify', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);

        $me   = auth();
        $code = trim(preg_replace('/\D/', '', $_POST['otp'] ?? ''));

        if (!$this->verifyOtp($me['email'], $code, 'change_password')) {
            redirect('/account/password/verify', ['error' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
        }

        $data = $_SESSION['pending_pw_change'];
        $this->db->prepare("UPDATE users SET password=?,updated_at=datetime('now') WHERE id=?")
                 ->execute([$data['hash'], $data['uid']]);

        // Invalidate all other sessions
        $sid = $_SESSION['session_id'] ?? null;
        $q   = $sid
            ? $this->db->prepare("DELETE FROM sessions WHERE user_id=? AND id!=?")
            : $this->db->prepare("DELETE FROM sessions WHERE user_id=?");
        $sid ? $q->execute([$data['uid'], $sid]) : $q->execute([$data['uid']]);

        unset($_SESSION['pending_pw_change']);
        Logger::info('account.password', "تغيير كلمة المرور #{$data['uid']}");
        redirect('/account', ['success' => 'تم تغيير كلمة المرور بنجاح.']);
    }

    // ── Change Email (with OTP) ───────────────────────────────

    public function changeEmailRequest(): void
    {
        verify_csrf();
        $me    = auth(); $uid = (int)$me['id'];
        $email = trim(strtolower($_POST['new_email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            redirect('/account', ['error' => 'البريد الإلكتروني غير صالح.']);
        if ($email === strtolower($me['email']))
            redirect('/account', ['error' => 'البريد الجديد مطابق للحالي.']);

        $chk = $this->db->prepare("SELECT id FROM users WHERE email=? AND id!=? LIMIT 1");
        $chk->execute([$email, $uid]);
        if ($chk->fetch())
            redirect('/account', ['error' => 'هذا البريد مستخدم من حساب آخر.']);

        $_SESSION['pending_email_change'] = ['uid' => $uid, 'new_email' => $email];
        try {
            // OTP يرسل إلى البريد الحالي للتحقق من الهوية
            $this->storeAndSendOtp($me['email'], 'change_email', $me['display_name']);
        } catch (Throwable $e) {
            unset($_SESSION['pending_email_change']);
            redirect('/account', ['error' => 'فشل إرسال رمز التحقق.']);
        }
        redirect('/account/email/verify');
    }

    public function changeEmailVerifyForm(): void
    {
        if (empty($_SESSION['pending_email_change'])) redirect('/account');
        $me     = auth();
        $masked = $this->maskEmail($me['email']);
        render('account.otp_verify', [
            'pageTitle'   => 'تأكيد تغيير البريد الإلكتروني',
            'maskedEmail' => $masked,
            'purpose'     => 'change_email',
            'backUrl'     => '/account',
        ]);
    }

    public function changeEmailVerify(): void
    {
        verify_csrf();
        if (empty($_SESSION['pending_email_change'])) redirect('/account');
        if (!rate_limit('otp_verify', 5, 300))
            redirect('/account/email/verify', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);

        $me   = auth();
        $code = trim(preg_replace('/\D/', '', $_POST['otp'] ?? ''));

        if (!$this->verifyOtp($me['email'], $code, 'change_email')) {
            redirect('/account/email/verify', ['error' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
        }

        $data = $_SESSION['pending_email_change'];
        $this->db->prepare("UPDATE users SET email=?,updated_at=datetime('now') WHERE id=?")
                 ->execute([$data['new_email'], $data['uid']]);

        // Refresh session user data
        $_SESSION['user_email'] = $data['new_email'];
        unset($_SESSION['pending_email_change']);
        Logger::info('account.email', "تغيير البريد #{$data['uid']} → {$data['new_email']}");
        redirect('/account', ['success' => 'تم تغيير البريد الإلكتروني بنجاح.']);
    }

    // ── Delete Account (with OTP) ─────────────────────────────

    public function deleteAccountRequest(): void
    {
        verify_csrf();
        $me = auth();

        $_SESSION['pending_delete_account'] = ['uid' => (int)$me['id']];
        try {
            $this->storeAndSendOtp($me['email'], 'delete_account', $me['display_name']);
        } catch (Throwable $e) {
            unset($_SESSION['pending_delete_account']);
            redirect('/account', ['error' => 'فشل إرسال رمز التحقق.']);
        }
        redirect('/account/delete/verify');
    }

    public function deleteAccountVerifyForm(): void
    {
        if (empty($_SESSION['pending_delete_account'])) redirect('/account');
        $me     = auth();
        $masked = $this->maskEmail($me['email']);
        render('account.otp_verify', [
            'pageTitle'   => 'تأكيد حذف الحساب',
            'maskedEmail' => $masked,
            'purpose'     => 'delete_account',
            'backUrl'     => '/account',
            'danger'      => true,
        ]);
    }

    public function deleteAccountVerify(): void
    {
        verify_csrf();
        if (empty($_SESSION['pending_delete_account'])) redirect('/account');
        if (!rate_limit('otp_verify', 5, 300))
            redirect('/account/delete/verify', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);

        $me   = auth();
        $code = trim(preg_replace('/\D/', '', $_POST['otp'] ?? ''));

        if (!$this->verifyOtp($me['email'], $code, 'delete_account')) {
            redirect('/account/delete/verify', ['error' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
        }

        $uid = (int)$me['id'];
        // Delete in correct order (foreign keys)
        $this->db->prepare("DELETE FROM sessions      WHERE user_id=?")->execute([$uid]);
        $this->db->prepare("DELETE FROM notifications WHERE user_id=?")->execute([$uid]);
        $this->db->prepare("DELETE FROM email_otp     WHERE email=?")->execute([$me['email']]);
        // Soft-delete: disable account and anonymise PII
        $anon = 'deleted_' . $uid . '_' . time();
        $this->db->prepare("UPDATE users SET is_active=0, email=?, username=?, display_name='حساب محذوف',
                            password='', totp_secret=NULL, two_fa_enabled=0, email_2fa_enabled=0,
                            updated_at=datetime('now') WHERE id=?")
                 ->execute([$anon . '@deleted.invalid', $anon, $uid]);

        Logger::info('account.delete', "حذف الحساب #{$uid}");
        session_destroy();
        redirect('/auth/login', ['success' => 'تم حذف حسابك بنجاح.']);
    }

    // ── 2FA: Google Authenticator (TOTP) ──────────────────────

    public function twoFaSetup(): void
    {
        $user   = auth();
        $secret = totp_generate_secret();
        $_SESSION['totp_setup'] = $secret;
        render('account.2fa_setup', [
            'user'      => $user,
            'secret'    => $secret,
            'qrUrl'     => totp_qr_url($secret, $user['email']),
            'pageTitle' => 'إعداد Google Authenticator',
        ]);
    }

    public function twoFaEnable(): void
    {
        verify_csrf();
        $user   = auth(); $uid = (int)$user['id'];
        $code   = trim($_POST['code'] ?? '');
        $secret = $_SESSION['totp_setup'] ?? '';

        if (!$secret || !totp_verify($secret, $code, $uid))
            redirect('/account/2fa/setup', ['error' => 'الرمز غير صحيح. تأكد من التطبيق.']);

        $this->db->prepare("UPDATE users SET totp_secret=?,two_fa_enabled=1,updated_at=datetime('now') WHERE id=?")
                 ->execute([$secret, $uid]);
        unset($_SESSION['totp_setup']);
        Logger::info('account.2fa', "تفعيل TOTP #{$uid}");
        redirect('/account', ['success' => 'تم تفعيل Google Authenticator.']);
    }

    public function twoFaDisable(): void
    {
        verify_csrf();
        $user = auth(); $uid = (int)$user['id'];
        $code = trim($_POST['code'] ?? '');

        if (!totp_verify((string)($user['totp_secret'] ?? ''), $code, $uid))
            redirect('/account', ['error' => 'الرمز غير صحيح لتعطيل Google Authenticator.']);

        $this->db->prepare("UPDATE users SET totp_secret=NULL,two_fa_enabled=0,updated_at=datetime('now') WHERE id=?")
                 ->execute([$uid]);
        Logger::info('account.2fa', "تعطيل TOTP #{$uid}");
        redirect('/account', ['success' => 'تم تعطيل Google Authenticator.']);
    }

    // ── 2FA: Email OTP ────────────────────────────────────────

    public function emailTwoFaEnable(): void
    {
        verify_csrf();
        $user = auth(); $uid = (int)$user['id'];

        $this->db->prepare("UPDATE users SET email_2fa_enabled=1,updated_at=datetime('now') WHERE id=?")
                 ->execute([$uid]);
        Logger::info('account.2fa', "تفعيل Email 2FA #{$uid}");
        redirect('/account', ['success' => 'تم تفعيل التحقق عبر البريد الإلكتروني.']);
    }

    public function emailTwoFaDisable(): void
    {
        verify_csrf();
        $user = auth(); $uid = (int)$user['id'];

        $this->db->prepare("UPDATE users SET email_2fa_enabled=0,updated_at=datetime('now') WHERE id=?")
                 ->execute([$uid]);
        Logger::info('account.2fa', "تعطيل Email 2FA #{$uid}");
        redirect('/account', ['success' => 'تم تعطيل التحقق عبر البريد الإلكتروني.']);
    }

    // ── Avatar ────────────────────────────────────────────────

    public function uploadAvatar(): void {
        verify_csrf();

        // 1. التحقق من الملف
        $file = $_FILES['avatar'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errs = [
                UPLOAD_ERR_INI_SIZE   => 'حجم الصورة أكبر من المسموح في السيرفر.',
                UPLOAD_ERR_FORM_SIZE  => 'حجم الصورة أكبر من المسموح.',
                UPLOAD_ERR_PARTIAL    => 'انقطع الرفع، جرّب مرة ثانية.',
                UPLOAD_ERR_NO_FILE    => 'لم يتم اختيار ملف.',
                UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير موجود على السيرفر.',
                UPLOAD_ERR_CANT_WRITE => 'تعذّر كتابة الملف على السيرفر.',
            ];
            $errorMsg = $file ? ($errs[$file['error']] ?? 'خطأ في رفع الملف.') : 'لم يتم اختيار ملف.';
            redirect('/account', ['error' => $errorMsg]);
        }

        // 2. التحقق من الحجم
        if ($file['size'] > 12 * 1024 * 1024) {
            redirect('/account', ['error' => 'الحجم الأقصى 12 ميجابايت.']);
        }

        // 3. التحقق من نوع الملف
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowed, true)) {
            redirect('/account', ['error' => 'يسمح بـ JPG, PNG, WebP فقط.']);
        }

        // 4. تحديد الامتداد
        $ext = match($mimeType) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        // 5. التأكد من وجود ROOT_PATH
        if (!defined('ROOT_PATH')) {
            error_log('ROOT_PATH غير معرف في AccountController::uploadAvatar()');
            redirect('/account', ['error' => 'خطأ في تكوين المسار.']);
        }

        $dir = ROOT_PATH . '/public/uploads/avatars/';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("فشل إنشاء المجلد: $dir");
                redirect('/account', ['error' => 'لا يمكن إنشاء مجلد الصور.']);
            }
        }

        // 6. حذف الصورة القديمة
        $uid = (int)auth()['id'];
        $oldStmt = $this->db->prepare("SELECT avatar FROM users WHERE id=? LIMIT 1");
        $oldStmt->execute([$uid]);
        $old = $oldStmt->fetchColumn();
        if ($old && is_file(ROOT_PATH . $old)) {
            @unlink(ROOT_PATH . $old);
        }

        // 7. إنشاء اسم الملف الجديد
        $dest = $dir . 'user_' . $uid . '_' . time() . '.' . $ext;

        // 8. معالجة الصورة باستخدام GD (مع fallback للنسخ العادي)
        $uploaded = false;
        if (extension_loaded('gd')) {
            $src = match($mimeType) {
                'image/png'  => @imagecreatefrompng($file['tmp_name']),
                'image/webp' => @imagecreatefromwebp($file['tmp_name']),
                default      => @imagecreatefromjpeg($file['tmp_name']),
            };
            if ($src) {
                $out = imagecreatetruecolor(200, 200);
                imagealphablending($out, false);
                imagesavealpha($out, true);
                $sw = imagesx($src);
                $sh = imagesy($src);
                $sq = min($sw, $sh);
                imagecopyresampled(
                    $out, $src,
                    0, 0,
                    (int)(($sw - $sq) / 2), (int)(($sh - $sq) / 2),
                    200, 200,
                    $sq, $sq
                );
                $saved = match($ext) {
                    'png'  => imagepng($out, $dest),
                    'webp' => imagewebp($out, $dest, 90),
                    default => imagejpeg($out, $dest, 88),
                };
                if ($saved) {
                    $uploaded = true;
                } else {
                    error_log("فشل حفظ الصورة المعالجة باستخدام GD: $dest");
                }
                imagedestroy($src);
                imagedestroy($out);
            } else {
                error_log("فشل إنشاء الصورة من الملف المؤقت: " . $file['tmp_name']);
            }
        }

        // 9. إذا فشلت GD، استخدم move_uploaded_file كحل احتياطي
        if (!$uploaded) {
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $uploaded = true;
            } else {
                error_log("فشل نقل الملف حتى باستخدام move_uploaded_file: $dest");
                redirect('/account', ['error' => 'فشل حفظ الصورة. تحقق من صلاحيات المجلد.']);
            }
        }

        // 10. تحديث قاعدة البيانات
        $rel = '/public/uploads/avatars/' . basename($dest);
        $stmt = $this->db->prepare("UPDATE users SET avatar=?, updated_at=datetime('now') WHERE id=?");
        if ($stmt->execute([$rel, $uid])) {
            Logger::info('account.avatar', "تحديث avatar #{$uid} إلى {$rel}");
            redirect('/account', ['success' => 'تم تحديث صورتك الشخصية بنجاح.']);
        } else {
            error_log("فشل تحديث قاعدة البيانات للمستخدم #{$uid} بالمسار {$rel}");
            redirect('/account', ['error' => 'حدث خطأ أثناء حفظ المسار في قاعدة البيانات.']);
        }
    }

    // ── OTP resend (account actions) ─────────────────────────

    public function resendAccountOtp(): void
    {
        verify_csrf();
        if (!rate_limit('otp_resend', 2, 300))
            redirect('/account', ['error' => 'انتظر قبل إعادة الإرسال.']);

        $purpose = $_POST['purpose'] ?? '';
        $allowedPurposes = ['change_password', 'change_email', 'delete_account'];
        if (!in_array($purpose, $allowedPurposes, true))
            redirect('/account');

        $me = auth();
        try {
            $this->storeAndSendOtp($me['email'], $purpose, $me['display_name']);
        } catch (Throwable) {
            redirect('/account', ['error' => 'فشل إرسال الرمز.']);
        }

        $backMap = [
            'change_password' => '/account/password/verify',
            'change_email'    => '/account/email/verify',
            'delete_account'  => '/account/delete/verify',
        ];
        redirect($backMap[$purpose], ['success' => 'تم إرسال رمز جديد.']);
    }

    // ── OTP helpers ───────────────────────────────────────────

    private function storeAndSendOtp(string $email, string $purpose, string $displayName = ''): void
    {
        $this->db->prepare("DELETE FROM email_otp WHERE email=? AND purpose=?")->execute([$email, $purpose]);

        if (!$displayName) {
            $u = $this->db->prepare("SELECT display_name FROM users WHERE email=? LIMIT 1");
            $u->execute([$email]);
            $displayName = $u->fetchColumn() ?: 'المستخدم';
        }

        $otp  = Mailer::sendOtp($email, $displayName, $purpose);
        $hash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
        $this->db->prepare("INSERT INTO email_otp (email, otp_hash, purpose, expires_at) VALUES (?, ?, ?, datetime('now','+10 minutes'))")
                 ->execute([$email, $hash, $purpose]);

        Logger::info('account.otp', "OTP أرسل إلى {$email} [{$purpose}]");
    }

    private function verifyOtp(string $email, string $code, string $purpose): bool
    {
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $s = $this->db->prepare(
            "SELECT id, otp_hash, attempts FROM email_otp
             WHERE email=? AND purpose=? AND used=0 AND expires_at > datetime('now')
             ORDER BY id DESC LIMIT 1"
        );
        $s->execute([$email, $purpose]);
        $row = $s->fetch();
        if (!$row) return false;

        if ((int)$row['attempts'] >= 5) {
            $this->db->prepare("UPDATE email_otp SET used=1 WHERE id=?")->execute([$row['id']]);
            return false;
        }

        if (!password_verify($code, $row['otp_hash'])) {
            $this->db->prepare("UPDATE email_otp SET attempts=attempts+1 WHERE id=?")->execute([$row['id']]);
            return false;
        }

        $this->db->prepare("UPDATE email_otp SET used=1 WHERE id=?")->execute([$row['id']]);
        return true;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $masked = substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
