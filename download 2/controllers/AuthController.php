<?php
declare(strict_types=1);

class AuthController
{
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    // ── Forgot Password ──────────────────────────────────────

    public function forgotPasswordForm(): void
    {
        if (auth()) redirect('/');
        render('auth.forgot_password', ['pageTitle' => 'نسيت كلمة المرور']);
    }

    public function forgotPassword(): void
    {
        verify_csrf();

        // Check for hacking tools
        if (detect_hacking_tools()) {
            Logger::warning('auth.forgot', "محاولة أداة اختراق", ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            redirect('/auth/forgot-password', ['error' => 'خطأ. حاول لاحقا.']);
        }

        if (!rate_limit_advanced('forgot_pass', 3, 900)) {
            redirect('/auth/forgot-password', ['error' => 'محاولات كثيرة. انتظر 15 دقيقة.']);
        }

        $email = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('/auth/forgot-password', ['error' => 'بريد إلكتروني غير صالح.']);
        }

        // Always show same message - don't reveal account existence
        $user = $this->db->prepare("SELECT id, display_name FROM users WHERE email=? AND is_active=1 LIMIT 1");
        $user->execute([$email]);
        $user = $user->fetch();

        if ($user) {
            $this->storeAndSendOtp($email, 'reset');
        }

        $_SESSION['otp_email_reset']   = $email;
        $_SESSION['otp_email_display'] = $user ? $user['display_name'] : 'المستخدم';
        Logger::info('auth.forgot', "طلب استعادة كلمة المرور: {$email}");
        // Always show same message regardless of account existence
        redirect('/auth/reset-otp');
    }

    public function resetOtpForm(): void
    {
        if (auth()) redirect('/');
        if (empty($_SESSION['otp_email_reset'])) redirect('/auth/forgot-password');
        $email = $_SESSION['otp_email_reset'];
        $masked = $this->maskEmail($email);
        render('auth.otp_verify', ['pageTitle' => 'التحقق من الرمز', 'maskedEmail' => $masked, 'purpose' => 'reset']);
    }

    public function resetOtp(): void
    {
        verify_csrf();
        if (empty($_SESSION['otp_email_reset'])) redirect('/auth/forgot-password');
        if (!rate_limit('otp_verify', 5, 300)) {
            redirect('/auth/reset-otp', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);
        }

        $email = $_SESSION['otp_email_reset'];
        $code  = trim(preg_replace('/\D/', '', $_POST['otp'] ?? ''));

        if (!$this->verifyOtp($email, $code, 'reset')) {
            redirect('/auth/reset-otp', ['error' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
        }

        $_SESSION['otp_reset_verified'] = $email;
        unset($_SESSION['otp_email_reset'], $_SESSION['otp_email_display']);
        redirect('/auth/new-password');
    }

    public function resendOtp(): void
    {
        verify_csrf();
        if (!rate_limit('otp_resend', 2, 300)) {
            redirect('/auth/forgot-password', ['error' => 'انتظر قبل إعادة الإرسال.']);
        }
        $purpose = $_POST['purpose'] ?? 'reset';
        // Whitelist purpose values to prevent injection
        if (!in_array($purpose, ['reset', 'register'], true)) $purpose = 'reset'; // '2fa' is handled by twoFactorResend
        if ($purpose === 'reset') {
            $email = $_SESSION['otp_email_reset'] ?? '';
            $back  = '/auth/reset-otp';
        } else {
            $pending = $_SESSION['pending_register'] ?? null;
            $email   = $pending['email'] ?? '';
            $back    = '/auth/register-verify';
        }
        if (!$email) redirect('/auth/login');
        try {
            $this->storeAndSendOtp($email, $purpose);
        } catch (Throwable $e) {
            redirect($back, ['error' => 'فشل إرسال الرمز. تحقق من إعدادات SMTP.']);
        }
        redirect($back, ['success' => 'تم إرسال رمز جديد إلى بريدك.']);
    }

    public function newPasswordForm(): void
    {
        if (auth()) redirect('/');
        if (empty($_SESSION['otp_reset_verified'])) redirect('/auth/forgot-password');
        render('auth.new_password', ['pageTitle' => 'كلمة مرور جديدة']);
    }

    public function newPassword(): void
    {
        verify_csrf();
        if (empty($_SESSION['otp_reset_verified'])) redirect('/auth/forgot-password');

        $email   = $_SESSION['otp_reset_verified'];
        $pass    = $_POST['password']         ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $errors = [];
        if (strlen($pass) < 8)               $errors[] = 'كلمة المرور 8 أحرف على الأقل.';
        if (!preg_match('/[A-Z]/', $pass))    $errors[] = 'يجب أن تحتوي على حرف كبير.';
        if (!preg_match('/[0-9]/', $pass))    $errors[] = 'يجب أن تحتوي على رقم.';
        if ($pass !== $confirm)               $errors[] = 'كلمتا المرور غير متطابقتين.';

        if ($errors) {
            redirect('/auth/new-password', ['error' => implode('<br>', $errors)]);
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $s = $this->db->prepare("UPDATE users SET password=?, updated_at=datetime('now') WHERE email=? AND is_active=1");
        $s->execute([$hash, $email]);

        // Invalidate all sessions for this user (security)
        $u = $this->db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $u->execute([$email]);
        $uid = $u->fetchColumn();
        if ($uid) {
            $this->db->prepare("DELETE FROM sessions WHERE user_id=?")->execute([$uid]);
        }

        unset($_SESSION['otp_reset_verified']);
        Logger::info('auth.reset', "تم تغيير كلمة المرور: {$email}");
        redirect('/auth/login', ['success' => 'تم تغيير كلمة المرور بنجاح. سجل دخولك.']);
    }

    // ── Register with OTP verification ───────────────────────

    public function registerVerifyForm(): void
    {
        if (auth()) redirect('/');
        if (empty($_SESSION['pending_register'])) redirect('/auth/register');
        $pending = $_SESSION['pending_register'];
        $masked  = $this->maskEmail($pending['email']);
        render('auth.otp_verify', ['pageTitle' => 'تفعيل الحساب', 'maskedEmail' => $masked, 'purpose' => 'register']);
    }

    public function registerVerify(): void
    {
        verify_csrf();
        if (empty($_SESSION['pending_register'])) redirect('/auth/register');
        if (!rate_limit('otp_verify', 5, 300)) {
            redirect('/auth/register-verify', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);
        }

        $pending = $_SESSION['pending_register'];
        $code    = trim(preg_replace('/\D/', '', $_POST['otp'] ?? ''));

        if (!$this->verifyOtp($pending['email'], $code, 'register')) {
            redirect('/auth/register-verify', ['error' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
        }

        // If already pre-hashed in session, use directly; otherwise hash now
        $hash = empty($pending['pass_prehashed'])
            ? password_hash($pending['pass'], PASSWORD_BCRYPT, ['cost' => 12])
            : $pending['pass'];  // already bcrypt hash
        $this->db->prepare("INSERT INTO users (display_name,username,telegram_username,email,password,email_notify) VALUES (?,?,?,?,?,?)")
                 ->execute([$pending['name'], $pending['user'], $pending['tg'], $pending['email'], $hash, $pending['email_notify']]);
        $uid = (int)$this->db->lastInsertId();

        unset($_SESSION['pending_register']);
        Logger::info('auth.register', "حساب جديد مفعل: {$pending['user']}", ['uid' => $uid]);
        $this->finishRegistration($uid, (string)$pending['name']);
    }

    // ── OTP helpers ────────────────────────────────────────

    private function storeAndSendOtp(string $email, string $purpose): void
    {
        try {
            // Delete old unused OTPs for this email+purpose
            $this->db->prepare("DELETE FROM email_otp WHERE email=? AND purpose=?")->execute([$email, $purpose]);

            // Fetch display name for email
            $u = $this->db->prepare("SELECT display_name FROM users WHERE email=? LIMIT 1");
            $u->execute([$email]);
            $displayName = $u->fetchColumn() ?: 'المستخدم';
            if ($purpose === 'register' && !empty($_SESSION['pending_register'])) {
                $displayName = $_SESSION['pending_register']['name'];
            }

            $otp = Mailer::sendOtp($email, $displayName, $purpose);

            $hash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
            $this->db->prepare("INSERT INTO email_otp (email, otp_hash, purpose, expires_at) VALUES (?, ?, ?, datetime('now','+10 minutes'))")
                     ->execute([$email, $hash, $purpose]);

            Logger::info('auth.otp', "OTP أرسل إلى {$email} [{$purpose}]");
        } catch (Throwable $e) {
            Logger::error('auth.otp', 'فشل إرسال OTP: ' . $e->getMessage());
            throw $e;
        }
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

        // Max 5 attempts per OTP
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

    /**
     * Send login alert email to user
     */
    private function sendLoginAlert(array $user): void
    {
        try {
            // Respect user's email notification preference
            if (empty($user['email_notify'])) return;

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Detect device type
            $deviceType = 'Unknown';
            $browser = 'Unknown';
            if (preg_match('/Mobile|Android|iPhone/i', $ua)) $deviceType = 'Mobile';
            elseif (preg_match('/Tablet|iPad/i', $ua)) $deviceType = 'Tablet';
            else $deviceType = 'Desktop';

            if (preg_match('/Chrome\/[\d.]+/i', $ua, $m)) $browser = 'Chrome';
            elseif (preg_match('/Firefox\/[\d.]+/i', $ua, $m)) $browser = 'Firefox';
            elseif (preg_match('/Safari\/[\d.]+/i', $ua, $m)) $browser = 'Safari';
            elseif (preg_match('/Edge\/[\d.]+/i', $ua, $m)) $browser = 'Edge';

            $siteName = setting('site_name', 'مفترق الطرق');
            $datetime = date('Y-m-d H:i:s');
            $location = $ip; // Could integrate with IP geolocation service

            $subject = "تنبيه تسجيل دخول جديد - {$siteName}";
            $html = <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#fff0dc;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff0dc;padding:40px 20px">
<tr><td align="center">
<table width="100%" style="max-width:520px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#391b07,#582b0d);padding:32px 40px;text-align:center">
    <div style="color:#f7cd59;font-size:13px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px">{$siteName}</div>
    <div style="color:rgba(255,255,255,.9);font-size:20px;font-weight:700">تنبيه تسجيل دخول جديد</div>
  </td></tr>
  <tr><td style="padding:40px 40px 32px">
    <p style="color:#6e523a;font-size:15px;line-height:1.7;margin:0 0 8px">مرحبا {$user['display_name']}،</p>
    <p style="color:#6e523a;font-size:15px;line-height:1.7;margin:0 0 28px">تم تسجيل دخول جديد إلى حسابك:</p>
    <div style="background:#fff9f0;border-radius:16px;padding:24px;margin:0 0 24px">
      <table width="100%" style="font-size:14px;color:#6e523a">
        <tr><td style="padding:8px 0"><strong>التاريخ:</strong></td><td style="padding:8px 0;text-align:left">{$datetime}</td></tr>
        <tr><td style="padding:8px 0"><strong>الموقع (IP):</strong></td><td style="padding:8px 0;text-align:left">{$location}</td></tr>
        <tr><td style="padding:8px 0"><strong>الجهاز:</strong></td><td style="padding:8px 0;text-align:left">{$deviceType}</td></tr>
        <tr><td style="padding:8px 0"><strong>المتصفح:</strong></td><td style="padding:8px 0;text-align:left">{$browser}</td></tr>
      </table>
    </div>
    <p style="color:#a38265;font-size:13px;line-height:1.7;margin:0;background:#fff2cd;padding:16px;border-radius:8px;border-right:4px solid #ffbd07">
      إذا لم يكن هذا أنت، قم بتغيير كلمة مرورك فورا وألغ جميع الجلسات النشطة.
    </p>
  </td></tr>
  <tr><td style="background:#f8f7f3;padding:20px 40px;text-align:center;border-top:1px solid #f1e5da">
    <div style="color:#bfa289;font-size:12px">{$siteName} — رسالة تلقائية، لا ترد عليها</div>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

            // Send login alert email directly
            (new Mailer())->send($user['email'], $user['display_name'], $subject, $html);

            // Also create in-app notification
            notify((int)$user['id'], 'security', 'تسجيل دخول جديد', "تم تسجيل دخول من {$deviceType} - {$browser}", '/auth/sessions');

        } catch (Throwable $e) {
            Logger::error('auth.login_alert', 'فشل إرسال تنبيه الدخول: ' . $e->getMessage());
        }
    }

    // ── Register ──────────────────────────────────────────────

    public function registerForm(): void
    {
        if (auth()) redirect('/');
        $this->rememberNext();
        render('auth.register', ['pageTitle' => 'إنشاء حساب', 'googleOn' => $this->googleCfg()['id'] !== '']);
    }

    public function register(): void
    {
        verify_csrf();
        if (!check_honeypot()) { Logger::warning('auth.register','Honeypot triggered'); redirect('/auth/register', ['error' => 'خطأ. حاول مجددا.']); }
        if (!rate_limit('register', 5, 3600)) redirect('/auth/register', ['error' => 'محاولات كثيرة. انتظر ساعة.']);

        $name    = trim($_POST['display_name'] ?? '');
        $user    = trim(strtolower($_POST['username'] ?? ''));
        $tg      = trim($_POST['telegram_username'] ?? '');
        $email   = trim(strtolower($_POST['email'] ?? ''));
        $pass    = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $terms   = isset($_POST['agree_terms']);

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'الاسم يجب أن يكون بين 2 و100 حرف.';
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $user))       $errors[] = 'اسم المستخدم: 3-30 حرف إنجليزي أو رقم أو _.';
        if (!validate_telegram($tg))                          $errors[] = 'معرف التيليغرام غير صالح (مثال: @username).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'البريد الإلكتروني غير صالح.';
        if (is_disposable_email($email))                       $errors[] = 'يرجى استخدام بريد إلكتروني حقيقي.';
        if (strlen($pass) < 8)                                 $errors[] = 'كلمة المرور 8 أحرف على الأقل.';
        if (!preg_match('/[A-Z]/', $pass))                     $errors[] = 'كلمة المرور يجب أن تحتوي على حرف كبير.';
        if (!preg_match('/[0-9]/', $pass))                     $errors[] = 'كلمة المرور يجب أن تحتوي على رقم.';
        if ($pass !== $confirm)                                $errors[] = 'كلمتا المرور غير متطابقتين.';
        if (!$terms)                                           $errors[] = 'يجب الموافقة على الشروط والأحكام.';

        if (!$errors) {
            $chk = $this->db->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
            $chk->execute([$user, $email]);
            if ($chk->fetch()) $errors[] = 'اسم المستخدم أو البريد الإلكتروني مستخدم مسبقا.';
        }

        if ($errors) {
            $_SESSION['flash']['old'] = $_POST;
            redirect('/auth/register', ['error' => implode('<br>', $errors)]);
        }

        $tgFormatted = $tg ? format_telegram($tg) : null;

        // If OTP registration is enabled — verify email first
        if (setting('email_otp_register', '1') === '1') {
            $_SESSION['pending_register'] = [
                'name'         => $name,
                'user'         => $user,
                'tg'           => $tgFormatted,
                'email'        => $email,
                // Pre-hash at cost 10 to avoid storing plaintext in session
                'pass'         => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]),
                'pass_prehashed' => true,
                'email_notify' => isset($_POST['email_notify']) ? 1 : 0,
            ];
            try {
                $this->storeAndSendOtp($email, 'register');
            } catch (Throwable $e) {
                redirect('/auth/register', ['error' => 'فشل إرسال رمز التحقق. تأكد من البريد الإلكتروني أو حاول لاحقا.']);
            }
            $_SESSION['otp_email_reset'] = null;
            redirect('/auth/register-verify');
        }

        // OTP disabled — create account directly
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->prepare("INSERT INTO users (display_name,username,telegram_username,email,password,email_notify) VALUES (?,?,?,?,?,?)")
                 ->execute([$name, $user, $tgFormatted, $email, $hash, isset($_POST['email_notify']) ? 1 : 0]);
        $uid = (int)$this->db->lastInsertId();
        Logger::info('auth.register', "تسجيل جديد: {$user}", ['uid' => $uid]);
        $this->finishRegistration($uid, (string)$name);
    }

    // ── Login ─────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (auth()) redirect('/');
        $this->rememberNext();
        render('auth.login', ['pageTitle' => 'تسجيل الدخول', 'googleOn' => $this->googleCfg()['id'] !== '']);
    }

    public function login(): void
    {
        verify_csrf();

        // Check for hacking tools
        if (detect_hacking_tools()) {
            Logger::warning('auth.login', "محاولة أداة اختراق", ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            redirect('/auth/login', ['error' => 'بيانات الدخول غير صحيحة.']);
        }

        if (!rate_limit_advanced('login', 10, 300)) redirect('/auth/login', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);

        $id   = trim($_POST['identifier'] ?? '');
        $pass = $_POST['password'] ?? '';
        $rem  = isset($_POST['remember_me']);

        // Use parameterized — no SQLi possible
        $stmt = $this->db->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND is_active=1 LIMIT 1");
        $stmt->execute([$id, $id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password'])) {
            Logger::warning('auth.login', "فشل تسجيل الدخول: {$id}");
            redirect('/auth/login', ['error' => 'بيانات الدخول غير صحيحة.']);
        }

        // Log successful login attempt before 2FA
        log_user_activity((int)$user['id'], 'login_attempt', [
            'method' => 'password',
            'success' => false,
            'has_2fa' => (bool)($user['two_fa_enabled'] || !empty($user['email_2fa_enabled'])),
        ]);

        if ($user['two_fa_enabled'] || !empty($user['email_2fa_enabled'])) {
            $_SESSION['pending_2fa'] = [
                'uid'       => (int)$user['id'],
                'rem'       => $rem,
                'has_totp'  => (bool)$user['two_fa_enabled'],
                'has_email' => !empty($user['email_2fa_enabled']),
                'chosen'    => null,
            ];

            $hasBoth = $user['two_fa_enabled'] && !empty($user['email_2fa_enabled']);

            if ($hasBoth) {
                // User chooses which method
                redirect('/auth/2fa/choose');
            } elseif (!empty($user['email_2fa_enabled'])) {
                // Send OTP email automatically
                try {
                    $this->storeAndSendOtp($user['email'], '2fa', $user['display_name']);
                    $_SESSION['pending_2fa']['chosen'] = 'email';
                } catch (Throwable) {
                    // If email fails, fall through to login normally if no TOTP
                }
                redirect('/auth/2fa');
            } else {
                // TOTP only
                $_SESSION['pending_2fa']['chosen'] = 'totp';
                redirect('/auth/2fa');
            }
        }

        $this->createSession((int)$user['id'], $rem);

        // Send login alert email
        $this->sendLoginAlert($user);

        Logger::info('auth.login', "دخول: {$user['username']}");
        redirect($this->takeNext());
    }

    // ── 2FA ───────────────────────────────────────────────────

    public function twoFactorChooseForm(): void
    {
        if (!isset($_SESSION['pending_2fa'])) redirect('/auth/login');
        $p = $_SESSION['pending_2fa'];
        if (!$p['has_totp'] || !$p['has_email']) redirect('/auth/login');
        render('auth.2fa_choose', ['pageTitle' => 'اختر طريقة التحقق']);
    }

    public function twoFactorChoose(): void
    {
        verify_csrf();
        if (!isset($_SESSION['pending_2fa'])) redirect('/auth/login');
        $method = $_POST['method'] ?? '';
        if (!in_array($method, ['totp', 'email'], true)) redirect('/auth/2fa/choose');

        $_SESSION['pending_2fa']['chosen'] = $method;

        if ($method === 'email') {
            $uid = (int)$_SESSION['pending_2fa']['uid'];
            $s   = $this->db->prepare("SELECT email, display_name FROM users WHERE id=? LIMIT 1");
            $s->execute([$uid]);
            $user = $s->fetch();
            if ($user) {
                try {
                    $this->storeAndSendOtp($user['email'], '2fa');
                } catch (Throwable) {
                    redirect('/auth/2fa/choose', ['error' => 'فشل إرسال رمز البريد. جرب Google Authenticator.']);
                }
            }
        }
        redirect('/auth/2fa');
    }

    public function twoFactorForm(): void
    {
        if (!isset($_SESSION['pending_2fa'])) redirect('/auth/login');
        $p      = $_SESSION['pending_2fa'];
        $chosen = $p['chosen'] ?? 'totp';
        $masked = '';
        if ($chosen === 'email') {
            $s = $this->db->prepare("SELECT email FROM users WHERE id=? LIMIT 1");
            $s->execute([(int)$p['uid']]);
            $email  = (string)($s->fetchColumn() ?: '');
            $masked = $this->maskEmail($email);
        }
        render('auth.2fa', ['pageTitle' => 'التحقق بخطوتين', 'method' => $chosen, 'maskedEmail' => $masked]);
    }

    public function twoFactor(): void
    {
        verify_csrf();
        if (!rate_limit('2fa', 5, 300))
            redirect('/auth/2fa', ['error' => 'محاولات كثيرة. انتظر 5 دقائق.']);

        if (!isset($_SESSION['pending_2fa'])) redirect('/auth/login');
        $p      = $_SESSION['pending_2fa'];
        $uid    = (int)$p['uid'];
        $rem    = (bool)$p['rem'];
        $chosen = $p['chosen'] ?? 'totp';
        $code   = trim($_POST['code'] ?? '');
        if (!$uid) redirect('/auth/login');

        $s = $this->db->prepare("SELECT email, totp_secret, is_active FROM users WHERE id=? LIMIT 1");
        $s->execute([$uid]);
        $user = $s->fetch();
        if (!$user || !$user['is_active']) redirect('/auth/login');

        $verified = false;
        if ($chosen === 'totp') {
            $verified = totp_verify((string)($user['totp_secret'] ?? ''), $code, $uid);
        } elseif ($chosen === 'email') {
            $verified = $this->verifyOtp($user['email'], $code, '2fa');
        }

        if (!$verified) {
            Logger::warning('auth.2fa', "فشل 2FA [{$chosen}] #{$uid}");
            redirect('/auth/2fa', ['error' => 'الرمز غير صحيح. حاول مجددا.']);
        }

        unset($_SESSION['pending_2fa']);
        $this->createSession($uid, $rem);
        Logger::info('auth.2fa', "نجاح 2FA [{$chosen}] #{$uid}");
        redirect($this->takeNext());
    }

    public function twoFactorResend(): void
    {
        verify_csrf();
        if (!isset($_SESSION['pending_2fa'])) redirect('/auth/login');
        if (!rate_limit('otp_resend', 2, 300))
            redirect('/auth/2fa', ['error' => 'انتظر قبل إعادة الإرسال.']);

        $p = $_SESSION['pending_2fa'];
        if (($p['chosen'] ?? '') !== 'email') redirect('/auth/2fa');

        $s = $this->db->prepare("SELECT email, display_name FROM users WHERE id=? LIMIT 1");
        $s->execute([(int)$p['uid']]);
        $user = $s->fetch();
        if (!$user) redirect('/auth/login');

        try {
            $this->storeAndSendOtp($user['email'], '2fa');
        } catch (Throwable) {
            redirect('/auth/2fa', ['error' => 'فشل إرسال الرمز.']);
        }
        redirect('/auth/2fa', ['success' => 'تم إرسال رمز جديد.']);
    }

    // ── Logout ────────────────────────────────────────────────

    public function logout(): void
    {
        $sid = $_SESSION['session_id'] ?? null;
        if ($sid) $this->db->prepare("DELETE FROM sessions WHERE id=?")->execute([$sid]);
        Logger::info('auth.logout', 'تسجيل خروج');
        session_destroy();
        redirect('/auth/login');
    }

    // ── Sessions ──────────────────────────────────────────────

    public function sessions(): void
    {
        require_auth();
        $user = auth();
        $s    = $this->db->prepare("SELECT * FROM sessions WHERE user_id=? ORDER BY last_active DESC");
        $s->execute([$user['id']]);
        render('auth.sessions', ['sessions' => $s->fetchAll(), 'pageTitle' => 'الجلسات النشطة']);
    }

    public function revokeSession(): void
    {
        require_auth(); verify_csrf();
        $user = auth();
        $sid  = $_POST['session_id'] ?? '';
        // Always bind user_id — prevents IDOR
        $this->db->prepare("DELETE FROM sessions WHERE id=? AND user_id=?")->execute([$sid, $user['id']]);
        Logger::info('auth.revoke', "إلغاء جلسة للمستخدم #{$user['id']}");
        redirect('/auth/sessions', ['success' => 'تم إلغاء الجلسة.']);
    }

    private function createSession(int $uid, bool $remember = true): void
    {
        $sid = bin2hex(random_bytes(32));
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

        $this->db->prepare("INSERT INTO sessions (id,user_id,ip_address,user_agent,remember_me) VALUES (?,?,?,?,?)")
                 ->execute([$sid, $uid, $ip, $ua, 1]); // Always persistent
        $this->db->prepare("UPDATE users SET last_login_at=datetime('now'),last_login_ip=?,updated_at=datetime('now') WHERE id=?")
                 ->execute([$ip, $uid]);

        // Regenerate FIRST (prevents session fixation), THEN write data
        // Calling regenerate after writing can lose data on some PHP session handlers
        session_regenerate_id(true);

        $keepNext = $_SESSION['intended'] ?? null;
        $_SESSION = [];                    // Clear any stale pending_2fa data
        if ($keepNext) $_SESSION['intended'] = $keepNext;
        $_SESSION['user_id']    = $uid;
        $_SESSION['session_id'] = $sid;
        // Re-generate CSRF token for the new authenticated session
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Always set persistent cookie (30 days)
        setcookie('remember_session', $sid, time() + 30 * 86400, '/', '',
            isset($_SERVER['HTTPS']), true);
    }

    // ── Redirect-after-login helpers ──────────────────────────
    private function rememberNext(): void
    {
        $n = (string)($_GET['next'] ?? '');
        if ($n !== '' && preg_match('#^/[A-Za-z0-9_\-./?=&%]*$#', $n) && !str_starts_with($n, '//')) $_SESSION['intended'] = $n;
    }
    /** New account is ready: welcome notification, sign in at once, land on the onboarding checklist (or where they were headed). */
    private function finishRegistration(int $uid, string $name): void
    {
        try {
            notify($uid, 'welcome', 'أهلا بك في ' . brand_name(), 'حسابك جاهز. أكمل خطواتك الأولى من صفحة حسابي، ثم استعرض البرامج المفتوحة وقدّم عليها.', '/account');
        } catch (Throwable) {}
        $this->createSession($uid, true);
        $next = $this->takeNext();
        redirect($next === '/' ? '/account?welcome=1' : $next, ['success' => 'تم إنشاء حسابك بنجاح، أهلا بك!']);
    }

    private function takeNext(): string
    {
        $n = $_SESSION['intended'] ?? '/';
        unset($_SESSION['intended']);
        return is_string($n) && $n !== '' ? $n : '/';
    }

    // ── Google OAuth (only the verified e-mail is taken; the rest is asked on the site) ──
    private function googleCfg(): array
    {
        $redirect = trim((string)Config::get('GOOGLE_REDIRECT_URI', ''));
        return [
            'id'       => trim((string)Config::get('GOOGLE_CLIENT_ID', '')),
            'secret'   => trim((string)Config::get('GOOGLE_CLIENT_SECRET', '')),
            'redirect' => $redirect !== '' ? $redirect : app_url('/auth/google/callback'),
        ];
    }

    private function httpPostForm(string $url, array $fields): ?array
    {
        $body = http_build_query($fields);
        $res = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
            $res = curl_exec($ch); curl_close($ch);
        }
        if (!$res) {
            $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $body, 'timeout' => 12, 'ignore_errors' => true]]);
            $res = @file_get_contents($url, false, $ctx);
        }
        $j = $res ? json_decode((string)$res, true) : null;
        return is_array($j) ? $j : null;
    }

    public function googleStart(): void
    {
        if (auth()) redirect('/');
        $cfg = $this->googleCfg();
        if ($cfg['id'] === '' || $cfg['secret'] === '') redirect('/auth/login', ['error' => 'الدخول عبر Google غير مفعل حاليا.']);
        $this->rememberNext();
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_state'] = $state;
        $q = http_build_query(['client_id' => $cfg['id'], 'redirect_uri' => $cfg['redirect'], 'response_type' => 'code',
            'scope' => 'openid email', 'state' => $state, 'prompt' => 'select_account', 'access_type' => 'online']);
        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $q);
        exit;
    }

    public function googleCallback(): void
    {
        $cfg = $this->googleCfg();
        $state = (string)($_GET['state'] ?? '');
        $good  = $state !== '' && !empty($_SESSION['google_state']) && hash_equals($_SESSION['google_state'], $state);
        unset($_SESSION['google_state']);
        if (!$good || !empty($_GET['error']) || empty($_GET['code'])) redirect('/auth/login', ['error' => 'تم إلغاء الدخول عبر Google أو انتهت الجلسة.']);

        $tok = $this->httpPostForm('https://oauth2.googleapis.com/token', [
            'code' => (string)$_GET['code'], 'client_id' => $cfg['id'], 'client_secret' => $cfg['secret'],
            'redirect_uri' => $cfg['redirect'], 'grant_type' => 'authorization_code',
        ]);
        if (!$tok || empty($tok['id_token'])) {
            Logger::warning('auth.google', 'فشل تبادل الرمز مع Google', ['resp' => json_encode($tok)]);
            redirect('/auth/login', ['error' => 'تعذر الاتصال بـ Google. حاول مجددا.']);
        }
        // id_token was received directly from Google over TLS → payload can be trusted (still validate aud/iss/exp)
        $parts = explode('.', (string)$tok['id_token']);
        $payload = count($parts) === 3 ? json_decode((string)base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
        $okIss = is_array($payload) && in_array($payload['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
        if (!$okIss || ($payload['aud'] ?? '') !== $cfg['id'] || (int)($payload['exp'] ?? 0) < time()) {
            redirect('/auth/login', ['error' => 'رمز Google غير صالح.']);
        }
        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $verified = $payload['email_verified'] ?? false;
        if ($email === '' || !($verified === true || $verified === 'true')) redirect('/auth/login', ['error' => 'بريد Google غير موثّق.']);

        $st = $this->db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user) {
            if (!(int)$user['is_active']) redirect('/auth/login', ['error' => 'هذا الحساب معطّل.']);
            if ($user['two_fa_enabled'] || !empty($user['email_2fa_enabled'])) {
                redirect('/auth/login', ['error' => 'هذا الحساب مفعّل عليه التحقق بخطوتين، سجّل الدخول بكلمة المرور.']);
            }
            $this->createSession((int)$user['id'], true);
            Logger::info('auth.google', "دخول عبر Google: {$user['username']}");
            redirect($this->takeNext());
        }
        // New user → the site asks for the rest of the profile
        $_SESSION['google_pending'] = ['email' => $email, 'ts' => time()];
        redirect('/auth/complete');
    }

    public function completeForm(): void
    {
        if (auth()) redirect('/');
        $p = $_SESSION['google_pending'] ?? null;
        if (!$p || time() - (int)$p['ts'] > 1800) { unset($_SESSION['google_pending']); redirect('/auth/login', ['error' => 'انتهت الجلسة، ابدأ من جديد.']); }
        render('auth.complete', ['pageTitle' => 'إكمال الحساب', 'email' => $p['email']]);
    }

    public function complete(): void
    {
        verify_csrf();
        $p = $_SESSION['google_pending'] ?? null;
        if (!$p || time() - (int)$p['ts'] > 1800) { unset($_SESSION['google_pending']); redirect('/auth/login', ['error' => 'انتهت الجلسة، ابدأ من جديد.']); }
        if (!check_honeypot()) redirect('/auth/complete', ['error' => 'خطأ. حاول مجددا.']);

        $name = trim($_POST['display_name'] ?? '');
        $user = trim(strtolower($_POST['username'] ?? ''));
        $tg   = trim($_POST['telegram_username'] ?? '');
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'الاسم يجب أن يكون بين 2 و100 حرف.';
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $user))       $errors[] = 'اسم المستخدم: 3-30 حرف إنجليزي أو رقم أو _.';
        if (!validate_telegram($tg))                          $errors[] = 'معرف التيليغرام غير صالح (مثال: @username).';
        if (!isset($_POST['agree_terms']))                    $errors[] = 'يجب الموافقة على الشروط والأحكام.';
        if (!$errors) {
            $chk = $this->db->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
            $chk->execute([$user, $p['email']]);
            if ($chk->fetch()) $errors[] = 'اسم المستخدم مستخدم مسبقا.';
        }
        if ($errors) { $_SESSION['flash']['old'] = $_POST; redirect('/auth/complete', ['error' => implode('<br>', $errors)]); }

        $hash = password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT, ['cost' => 10]);   // no password: can be set via "forgot password"
        $tgF  = $tg ? format_telegram($tg) : null;
        $this->db->prepare("INSERT INTO users (display_name,username,telegram_username,email,password,email_notify) VALUES (?,?,?,?,?,?)")
                 ->execute([$name, $user, $tgF, $p['email'], $hash, isset($_POST['email_notify']) ? 1 : 0]);
        $uid = (int)$this->db->lastInsertId();
        unset($_SESSION['google_pending']);
        Logger::info('auth.google', "حساب جديد عبر Google: {$user}", ['uid' => $uid]);
        $this->finishRegistration($uid, (string)$name);
    }

    // ── CSRF Token Refresh ────────────────────────────────────
    // Returns a fresh CSRF token as JSON.
    // Used by AJAX-heavy pages (e.g. application form) after a session
    // regeneration event (login, 2FA completion) that silently rotates
    // the session token. The JS side picks this up and retries the failed
    // request with the new token.
    public function csrfRefresh(): void
    {
        // Rotate token on every call to keep it fresh
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode(['token' => $token]);
        exit;
    }

}
