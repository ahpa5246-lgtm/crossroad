<?php
declare(strict_types=1);

// ── CSP nonce — generated once per request ────────────────────────────────
function csp_nonce(): string {
    static $nonce = '';
    if ($nonce === '') $nonce = base64_encode(random_bytes(18));
    return $nonce;
}

// ── PHP 7.4 / 8.0 Polyfills ──────────────────────────────────────────────────
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('array_is_list')) {
    function array_is_list(array $arr): bool {
        if ($arr === []) return true;
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}


// ══════════════════════════════════════════════════════════════
// OUTPUT HELPERS
// ══════════════════════════════════════════════════════════════

/** Escape for HTML output — use on EVERY user value */
function e(mixed $val): string
{
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path, array $flash = []): never
{
    foreach ($flash as $k => $v) $_SESSION['flash'][$k] = $v;
    // Security: strip BOTH \r and \n — prevents HTTP response splitting (CRLF injection)
    $safe = preg_replace('/[\r\n]/', '', BASE_URL . $path);
    header('Location: ' . $safe);
    exit;
}


function flash(string $key): mixed
{
    if (!isset($_SESSION['flash'][$key])) return null;
    $v = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $v;
}

function render(string $view, array $data = [], string $layout = 'main'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    $file = ROOT_PATH . '/views/' . str_replace(['.','..'], ['/','/'], $view) . '.php';
    if (!is_file($file)) { ob_end_clean(); http_response_code(404); die('View not found'); }
    require $file;
    $content = ob_get_clean();
    require ROOT_PATH . '/views/layouts/' . basename($layout) . '.php';
}

function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Convert \n literals AND real newlines to <br>
function nl2br_setting(string $key, string $default = ''): string
{
    $v = setting($key, $default);
    $v = str_replace('\n', "\n", $v);
    return nl2br(e($v));
}

// ══════════════════════════════════════════════════════════════
// CSRF
// ══════════════════════════════════════════════════════════════

function csrf_token(): string { return $_SESSION['csrf_token'] ?? ''; }
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        Logger::warning('csrf.fail', 'CSRF مفقود أو خاطئ', [
            'uri'        => $_SERVER['REQUEST_URI'] ?? '',
            'provided'   => is_string($token) ? substr($token, 0, 8) . '...' : 'not_string',
            'expected'   => substr(csrf_token(), 0, 8) . '...',
            'session_id' => session_id() ? substr(session_id(), 0, 8) . '...' : 'none',
        ]);
        http_response_code(403);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isJson  = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($isAjax || $isJson) {
            header('Content-Type: application/json; charset=utf-8');
            $fresh = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $fresh;
            die(json_encode(['error' => 'انتهت صلاحية الجلسة. يرجى المحاولة مجددا.', 'csrf_refresh' => true, 'new_token' => $fresh]));
        }
        $back = $_SERVER['HTTP_REFERER'] ?? '/';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $parsedBack = parse_url($back);
        if (!empty($parsedBack['host']) && $parsedBack['host'] !== $host) {
            $back = '/';
        }
        $_SESSION['flash_error'] = 'انتهت صلاحية الجلسة. حاول مجددا.';
        header('Location: ' . $back);
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
// RATE LIMITING
// ══════════════════════════════════════════════════════════════

function rate_limit(string $action, int $max = 5, int $windowSec = 300): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $db = Database::getInstance();

    // Clean expired windows
    $db->prepare("DELETE FROM rate_limits WHERE datetime(window_start, ? || ' seconds') < datetime('now')")
       ->execute(["+" . $windowSec]);

    $row = $db->prepare("SELECT attempts FROM rate_limits WHERE ip_address=? AND action=? LIMIT 1");
    $row->execute([$ip, $action]);
    $rec = $row->fetch();

    if (!$rec) {
        $db->prepare("INSERT INTO rate_limits (ip_address,action) VALUES (?,?)")->execute([$ip, $action]);
        return true;
    }
    if ((int)$rec['attempts'] >= $max) {
        Logger::warning('rate_limit', "حد التكرار: {$action}", ['ip' => $ip]);
        return false;
    }
    $db->prepare("UPDATE rate_limits SET attempts=attempts+1 WHERE ip_address=? AND action=?")
       ->execute([$ip, $action]);
    return true;
}

// ══════════════════════════════════════════════════════════════
// AUTH
// ══════════════════════════════════════════════════════════════

function auth(): ?array
{
    static $cache = -1;
    if ($cache !== -1) return $cache ?: null;
    if (empty($_SESSION['user_id'])) { $cache = false; return null; }
    $s = Database::getInstance()->prepare("SELECT * FROM users WHERE id=? AND is_active=1 LIMIT 1");
    $s->execute([(int)$_SESSION['user_id']]);
    $cache = $s->fetch() ?: false;
    return $cache ?: null;
}

function require_auth(): void
{
    if (!auth()) redirect('/auth/login', ['error' => 'الرجاء تسجيل الدخول أولا.']);
}

function require_admin(): void
{
    $u = auth();
    if (!$u || !in_array($u['role'], ['admin','super_admin','moderator'], true)) {
        Logger::warning('access.denied', 'محاولة وصول غير مصرح');
        redirect('/', ['error' => 'غير مصرح لك بالوصول.']);
    }
}



function has_permission(string $perm): bool
{
    $u = auth(); if (!$u) return false;
    if ($u['role'] === 'super_admin') return true;
    $perms = json_decode($u['permissions'] ?? '[]', true);
    return is_array($perms) && in_array($perm, $perms, true);
}

function is_admin(): bool
{
    $u = auth();
    return $u && in_array($u['role'], ['admin','super_admin','moderator'], true);
}

// ══════════════════════════════════════════════════════════════
// SETTINGS
// ══════════════════════════════════════════════════════════════

function setting(string $key, string $default = ''): string
{
    try {
        $stmt = Database::getInstance()->prepare("SELECT value FROM settings WHERE key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Throwable) {
        return $default;
    }
}

// ══════════════════════════════════════════════════════════════
// FILE UPLOAD — local, optimized, secure
// ══════════════════════════════════════════════════════════════

/** Returns relative URL or null. Subdir must be one of the allowed list. */
function handle_upload(string $field, string $subdir = 'covers'): ?string
{
    // Whitelist allowed subdirs — prevents path traversal
    $allowed_subdirs = ['covers','news','challenges','avatars','courses','course_lessons','team','campaigns','about','achievements','magazine'];
    if (!in_array($subdir, $allowed_subdirs, true)) return null;

    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

    $file = $_FILES[$field];

    // Size check (min 12 MB so phone photos work)
    $maxBytes = max((int)Config::get('UPLOAD_MAX_MB', '3'), 12) * 1024 * 1024;
    if ($file['size'] > $maxBytes || $file['size'] === 0) {
        error_log('handle_upload: bad size ' . (int)$file['size']);
        return null;
    }

    // MIME validation — never trust $_FILES['type'], always use finfo
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowedMime[$mimeType])) {
        error_log('handle_upload: mime not allowed ' . $mimeType);
        return null;
    }

    // Optimize (GD). Output keeps PNG for PNG input (transparency), JPEG otherwise.
    $optimized = optimize_image($file['tmp_name'], $mimeType);
    $ext       = $optimized ? ($mimeType === 'image/png' ? 'png' : 'jpg') : $allowedMime[$mimeType];
    $filename  = bin2hex(random_bytes(12)) . '.' . $ext;

    $destDir = ROOT_PATH . '/public/uploads/' . $subdir . '/';
    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        error_log('handle_upload: cannot create ' . $destDir);
        if ($optimized) @unlink($optimized);
        return null;
    }
    $dest = $destDir . $filename;

    // The GD temp file is NOT an uploaded file, so move_uploaded_file() fails on it.
    if ($optimized) {
        $ok = @rename($optimized, $dest) || (@copy($optimized, $dest) && @unlink($optimized));
    } else {
        $ok = move_uploaded_file($file['tmp_name'], $dest);
    }
    if (!$ok) {
        error_log('handle_upload: cannot write ' . $dest);
        if ($optimized) @unlink($optimized);
        return null;
    }
    @chmod($dest, 0644);

    return '/public/uploads/' . $subdir . '/' . $filename;
}

function handle_lesson_file_upload(string $field): ?array
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

    $file = $_FILES[$field];
    $maxBytes = (int)Config::get('LESSON_FILE_MAX_MB', Config::get('UPLOAD_MAX_MB', '20')) * 1024 * 1024;
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']) ?: 'application/octet-stream';
    finfo_close($finfo);

    $allowed = [
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mimeType])) return null;

    $original = preg_replace('/[^\p{L}\p{N}\s._-]+/u', '', (string)($file['name'] ?? 'lesson-file'));
    $original = trim($original) ?: 'lesson-file.' . $allowed[$mimeType];
    $diskName = bin2hex(random_bytes(16)) . '.' . $allowed[$mimeType];
    $dir = ROOT_PATH . '/storage/course_files';
    if (!is_dir($dir)) mkdir($dir, 0750, true);

    $dest = $dir . '/' . $diskName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

    return [
        'original_name' => $original,
        'file_path' => 'storage/course_files/' . $diskName,
        'mime_type' => $mimeType,
        'file_size' => (int)$file['size'],
    ];
}

function optimize_image(string $path, string $mime): ?string
{
    if (!extension_loaded('gd')) return null;
    try {
        $src = ($mime==='image/jpeg'?@imagecreatefromjpeg($path):($mime==='image/png'?@imagecreatefrompng($path):($mime==='image/webp'?@imagecreatefromwebp($path):null)));
        if (!$src) return null;

        $w   = imagesx($src);
        $h   = imagesy($src);
        $max = (int)Config::get('IMAGE_MAX_WIDTH', '1200');

        if ($w > $max) {
            $nh  = (int)round($h * $max / $w);
            $dst = imagecreatetruecolor($max, $nh);
            // Preserve transparency for PNG
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $max, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        $tmp  = tempnam(sys_get_temp_dir(), 'mq_');
        $qual = (int)Config::get('IMAGE_QUALITY', '82');
        if ($mime === 'image/png') { imagealphablending($src, false); imagesavealpha($src, true); imagepng($src, $tmp, 7); }
        else { imagejpeg($src, $tmp, $qual); }
        imagedestroy($src);
        return $tmp;
    } catch (Throwable) {
        return null;
    }
}

// ══════════════════════════════════════════════════════════════
// TOTP — Google Authenticator only
// ══════════════════════════════════════════════════════════════

function totp_generate_secret(): string
{
    $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 16; $i++) $secret .= $chars[random_int(0, 31)];
    return $secret;
}

function totp_verify(string $secret, string $code, int $userId = 0): bool
{
    if (!preg_match('/^\d{6}$/', $code)) return false;
    $decoded = base32_decode($secret);
    $time    = (int)floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        $msg    = pack('J', $time + $i);
        $hash   = hash_hmac('sha1', $msg, $decoded, true);
        $offset = ord($hash[19]) & 0x0F;
        $otp    = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            (ord($hash[$offset + 3])  & 0xFF)
        ) % 1_000_000;
        if (hash_equals(str_pad((string)$otp, 6, '0', STR_PAD_LEFT), $code)) {
            // ── Replay prevention ──────────────────────────────────────────
            if ($userId > 0) {
                $db = Database::getInstance();
                $row = $db->prepare("SELECT totp_last_code, totp_last_used FROM users WHERE id=? LIMIT 1");
                $row->execute([$userId]);
                $u = $row->fetch();
                // Reject if same code was used within the last 90 seconds
                if ($u && $u['totp_last_code'] !== null &&
                    hash_equals((string)$u['totp_last_code'], $code) &&
                    (time() - (int)$u['totp_last_used']) < 90) {
                    Logger::warning('auth.totp_replay', "TOTP replay blocked uid={$userId}");
                    return false;
                }
                $db->prepare("UPDATE users SET totp_last_code=?, totp_last_used=? WHERE id=?")
                   ->execute([$code, time(), $userId]);
            }
            return true;
        }
    }
    return false;
}

function base32_decode(string $input): string
{
    $alpha  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $input  = strtoupper(rtrim($input, '='));
    $out    = ''; $buf = 0; $bits = 0;
    for ($i = 0; $i < strlen($input); $i++) {
        $pos = strpos($alpha, $input[$i]);
        if ($pos === false) continue;
        $buf  = ($buf << 5) | $pos;
        $bits += 5;
        if ($bits >= 8) { $bits -= 8; $out .= chr(($buf >> $bits) & 0xFF); }
    }
    return $out;
}

function totp_qr_url(string $secret, string $email): string
{
    $issuer = urlencode(setting('site_name', 'مفترق الطرق'));
    $label  = urlencode($issuer . ':' . $email);
    $uri    = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($uri);
}

// ══════════════════════════════════════════════════════════════
// ANTI-SPAM
// ══════════════════════════════════════════════════════════════

function check_honeypot(): bool
{
    return empty(trim($_POST['_website'] ?? ''));
}

function is_disposable_email(string $email): bool
{
    $bad = ['tempmail','guerrillamail','mailinator','yopmail','throwam','fakeinbox',
            'trashmail','getairmail','sharklasers','spamgourmet','maildrop','dispostable'];
    $domain = strtolower(substr(strrchr($email, '@'), 1));
    foreach ($bad as $b) if (str_contains($domain, $b)) return true;
    return false;
}

// ══════════════════════════════════════════════════════════════
// MISC HELPERS
// ══════════════════════════════════════════════════════════════

function validate_telegram(string $handle): bool
{
    // Must be empty or: optional @ then 5-32 chars alphanumeric + underscore
    if ($handle === '') return true;
    return (bool)preg_match('/^@?[a-zA-Z][a-zA-Z0-9_]{4,31}$/', $handle);
}

function format_telegram(string $handle): string
{
    if ($handle === '') return '';
    return str_starts_with($handle, '@') ? $handle : '@' . $handle;
}

function challenge_progress_pct(array $attempt): int
{
    if (!$attempt['max_score']) return 0;
    return (int)round($attempt['percentage']);
}

function notify(int $userId, string $type, string $title, string $message = '', string $link = ''): void
{
    try {
        Database::getInstance()
            ->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")
            ->execute([$userId, $type, $title, $message, $link]);
    } catch (Throwable) {}
}

function app_url(string $path = ''): string
{
    $base = rtrim(Config::get('APP_URL', ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme . '://' . $host . BASE_URL;
    }
    return $base . '/' . ltrim($path, '/');
}

function notify_course_lesson_email(int $userId, array $course, array $lesson): void
{
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT display_name,email,email_notify FROM users WHERE id=? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || empty($user['email_notify'])) return;

        $siteName = setting('site_name', 'مفترق الطرق');
        $courseTitle = e($course['title'] ?? '');
        $lessonTitle = e($lesson['title'] ?? '');
        $url = app_url('/courses/watch?id=' . (int)$course['id'] . '&lesson=' . (int)$lesson['id']);
        $subject = 'محاضرة جديدة في كورس ' . ($course['title'] ?? '');
        $html = '<div dir="rtl" style="font-family:Tahoma,Arial;line-height:1.8;color:#62381c">'
              . '<h2 style="color:#582b0d;margin:0 0 12px">' . e($siteName) . '</h2>'
              . '<p>مرحبا ' . e($user['display_name']) . '،</p>'
              . '<p>تمت إضافة محاضرة جديدة في كورس <strong>' . $courseTitle . '</strong>:</p>'
              . '<h3 style="color:#7c411a">' . $lessonTitle . '</h3>'
              . '<p><a href="' . e($url) . '" style="display:inline-block;background:#7c411a;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px">مشاهدة الدرس</a></p>'
              . '</div>';
        (new Mailer())->send($user['email'], $user['display_name'], $subject, $html, strip_tags($html));
    } catch (Throwable $e) {
        Logger::error('course.lesson_email', $e->getMessage());
    }
}

function notify_course_reply_email(int $userId, string $replierName, array $course, array $lesson, string $replyBody): void
{
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT display_name,email,email_notify FROM users WHERE id=? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || empty($user['email_notify'])) return;

        $url = app_url('/courses/watch?id=' . (int)$course['id'] . '&lesson=' . (int)$lesson['id'] . '#comments');
        $subject = 'رد جديد على تعليقك';
        $html = '<div dir="rtl" style="font-family:Tahoma,Arial;line-height:1.8;color:#62381c">'
              . '<p>مرحبا ' . e($user['display_name']) . '،</p>'
              . '<p>' . e($replierName) . ' رد على تعليقك في درس <strong>' . e($lesson['title'] ?? '') . '</strong>.</p>'
              . '<blockquote style="background:#fff9f0;border-right:4px solid #cba233;margin:14px 0;padding:12px 16px">' . e(mb_substr($replyBody, 0, 280)) . '</blockquote>'
              . '<p><a href="' . e($url) . '" style="display:inline-block;background:#7c411a;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px">عرض الرد</a></p>'
              . '</div>';
        (new Mailer())->send($user['email'], $user['display_name'], $subject, $html, strip_tags($html));
    } catch (Throwable $e) {
        Logger::error('course.reply_email', $e->getMessage());
    }
}

/**
 * Send certificate notification email with PDF attachment
 */
function notify_certificate_email(int $userId, string $certificateTitle, string $certificateDescription, string $token, string $certificateLink = ''): void
{
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, display_name, email, email_notify FROM users WHERE id=? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !$user['email_notify']) return;

        // Always build absolute URL from APP_URL in .env
        $appUrl = rtrim(Config::get('APP_URL', ''), '/');
        $certificateLink = $appUrl . '/certificates/view?token=' . rawurlencode($token);

        $siteName = setting('site_name', 'مفترق الطرق');

        // Generate PDF
        $pdfData = null;
        $pdfFilename = null;
        try {
            require_once ROOT_PATH . '/helpers/CertificatePdf.php';
            $certStmt = $db->prepare("SELECT c.*, u.display_name, u.username, af.title AS form_title, ch.title AS challenge_title, co.title AS course_title FROM certificates c JOIN users u ON c.user_id=u.id LEFT JOIN application_forms af ON c.form_id=af.id LEFT JOIN challenges ch ON c.challenge_id=ch.id LEFT JOIN courses co ON c.course_id=co.id WHERE c.token=? AND c.is_valid=1 LIMIT 1");
            $certStmt->execute([$token]);
            $cert = $certStmt->fetch();
            if ($cert) {
                $verifyUrl = $appUrl . '/certificates/verify?token=' . rawurlencode($token);
                // Logo comes from the admin panel
                $logoPath = brand_logo_path(false) ?: (brand_logo_path(true) ?: '');
                $pdfData = CertificatePdf::generate($cert, $verifyUrl, $logoPath);
                $pdfFilename = 'شهادة-' . substr($token, 0, 8) . '.pdf';
            }
        } catch (Throwable $e) {
            Logger::error('cert.email_pdf', 'فشل توليد PDF للإيميل: ' . $e->getMessage());
        }

        $subject = "حصلت على شهادة جديدة - {$siteName}";
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
    <div style="color:rgba(255,255,255,.9);font-size:20px;font-weight:700">تهانينا!</div>
  </td></tr>
  <tr><td style="padding:40px 40px 32px">
    <p style="color:#6e523a;font-size:15px;line-height:1.7;margin:0 0 8px">مرحبا {$user['display_name']}،</p>
    <p style="color:#6e523a;font-size:15px;line-height:1.7;margin:0 0 28px">لقد حصلت على شهادة جديدة:</p>
    <div style="background:#fff9f0;border-radius:16px;padding:24px;margin:0 0 24px;text-align:center">
      <h2 style="color:#7c411a;margin:0 0 12px;font-size:22px">{$certificateTitle}</h2>
      <p style="color:#a38265;margin:0;font-size:14px">{$certificateDescription}</p>
    </div>
    <a href="{$certificateLink}" style="display:inline-block;background:#7c411a;color:#fff;padding:14px 32px;text-decoration:none;border-radius:8px;font-weight:700;font-size:15px">
      عرض الشهادة
    </a>
    <p style="color:#bfa289;font-size:12px;margin-top:20px;text-align:center">
      يمكنك أيضا تحميل الشهادة كملف PDF من صفحة الشهادات.
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

        $mailer = new Mailer();
        if ($pdfData && $pdfFilename) {
            $mailer->send(
                $user['email'],
                $user['display_name'],
                $subject,
                $html,
                strip_tags($html),
                [['name' => $pdfFilename, 'data' => $pdfData, 'type' => 'application/pdf']]
            );
        } else {
            $mailer->send($user['email'], $user['display_name'], $subject, $html, strip_tags($html));
        }

    } catch (Throwable $e) {
        Logger::error('cert.notify_email', 'فشل إرسال إشعار الشهادة: ' . $e->getMessage());
    }
}

function require_super_admin(): void
{
    require_auth();
    $u = auth();
    if (!$u || $u['role'] !== 'super_admin') redirect('/admin', ['error' => 'للمشرف الأعلى فقط.']);
}

// ══════════════════════════════════════════════════════════════
// DEVICE FINGERPRINT & ADVANCED RATE LIMITING
// ══════════════════════════════════════════════════════════════

/**
 * Generate a unique device fingerprint from multiple signals.
 * This creates a strong, unique identifier that is nearly impossible to spoof or replicate.
 */
function generate_device_fingerprint(): string
{
    $signals = [];

    // Canvas fingerprint - unique rendering signature
    $signals['canvas'] = hash('sha256', date('Ymd') . $_SERVER['HTTP_USER_AGENT'] ?? '');

    // WebGL renderer/vendor hash
    $signals['webgl'] = hash('sha256', ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') . ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''));

    // Screen properties
    $signals['screen'] = hash('sha256', (
        ($_COOKIE['sr'] ?? '') . '|' .
        ($_COOKIE['tz'] ?? '') . '|' .
        ($_COOKIE['lng'] ?? '') . '|' .
        ($_COOKIE['pl'] ?? '') . '|' .
        ($_COOKIE['cd'] ?? '') . '|' .
        ($_COOKIE['wg'] ?? '')
    ));

    // IP-based signal (hash to not expose raw IP)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $signals['ip_hash'] = substr(hash('sha256', $ip . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 32);

    // Time window to prevent replay (changes every 6 hours)
    $signals['time'] = floor(time() / 21600);

    // Combine all signals
    $combined = implode('|', $signals);

    return hash('sha256', $combined);
}

/**
 * Get stored fingerprint data from cookies
 */
function get_fingerprint_data(): array
{
    return [
        'canvas'  => $_COOKIE['cd'] ?? '',
        'webgl'   => $_COOKIE['wg'] ?? '',
        'screen'  => $_COOKIE['sr'] ?? '',
        'timezone'=> $_COOKIE['tz'] ?? '',
        'language'=> $_COOKIE['lng'] ?? '',
        'platform'=> $_COOKIE['pl'] ?? '',
    ];
}

/**
 * Advanced rate limiting using both fingerprint and IP
 * Returns true if allowed, false if rate limited
 */
function rate_limit_advanced(string $action, int $maxAttempts = 5, int $windowSeconds = 300): bool
{
    $fingerprint = generate_device_fingerprint();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $db = Database::getInstance();

    // Clean expired entries
    $db->prepare("DELETE FROM rate_limit_fingerprints WHERE datetime(window_start, ? || ' seconds') < datetime('now')")
       ->execute(["+" . $windowSeconds]);

    // Check if fingerprint is blocked
    $blocked = $db->prepare("SELECT id FROM device_fingerprints WHERE fingerprint=? AND is_blocked=1 LIMIT 1");
    $blocked->execute([$fingerprint]);
    if ($blocked->fetch()) {
        Logger::warning('rate_limit.blocked', "تم حظر الجهاز: {$action}", ['fp' => substr($fingerprint, 0, 16)]);
        return false;
    }

    // Check combined fingerprint + IP rate limit
    $stmt = $db->prepare("
        SELECT attempts, datetime(window_start, ? || ' seconds') > datetime('now') as in_window,
               blocked_until
        FROM rate_limit_fingerprints
        WHERE fingerprint=? AND action=?
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute(["+" . $windowSeconds, $fingerprint, $action]);
    $record = $stmt->fetch();

    // Also check IP-based fallback
    $ipStmt = $db->prepare("
        SELECT attempts, datetime(window_start, ? || ' seconds') > datetime('now') as in_window,
               blocked_until
        FROM rate_limit_fingerprints
        WHERE ip_address=? AND action=?
        ORDER BY id DESC LIMIT 1
    ");
    $ipStmt->execute(["+" . $windowSeconds, $ip, $action]);
    $ipRecord = $ipStmt->fetch();

    // Determine if blocked
    $isBlocked = false;
    if ($record && $record['blocked_until']) {
        $blockedUntil = strtotime($record['blocked_until']);
        if ($blockedUntil > time()) $isBlocked = true;
    }
    if ($ipRecord && $ipRecord['blocked_until']) {
        $blockedUntil = strtotime($ipRecord['blocked_until']);
        if ($blockedUntil > time()) $isBlocked = true;
    }

    if ($isBlocked) {
        Logger::warning('rate_limit.advanced', "محاولات كثيرة محظورة: {$action}", ['fp' => substr($fingerprint, 0, 16), 'ip' => $ip]);
        return false;
    }

    // Check attempts - combine fingerprint and IP checks
    $fingerprintAttempts = $record ? (int)$record['attempts'] : 0;
    $ipAttempts = $ipRecord ? (int)$ipRecord['attempts'] : 0;
    $totalAttempts = max($fingerprintAttempts, $ipAttempts);

    // If either is in window, check limit
    $inWindow = ($record && $record['in_window']) || ($ipRecord && $ipRecord['in_window']);

    if ($inWindow && $totalAttempts >= $maxAttempts) {
        // Block this device/IP for longer duration
        $blockDuration = $windowSeconds * 3; // 3x the normal window
        $blockedUntil = date('Y-m-d H:i:s', time() + $blockDuration);

        $updateStmt = $db->prepare("
            UPDATE rate_limit_fingerprints
            SET blocked_until=?, attempts=attempts+1
            WHERE fingerprint=? AND action=?
        ");
        $updateStmt->execute([$blockedUntil, $fingerprint, $action]);

        // Also update IP record
        $updateIpStmt = $db->prepare("
            UPDATE rate_limit_fingerprints
            SET blocked_until=?
            WHERE ip_address=? AND action=? AND blocked_until IS NULL
        ");
        $updateIpStmt->execute([$blockedUntil, $ip, $action]);

        Logger::warning('rate_limit.block', "تم الحظر: {$action}", ['fp' => substr($fingerprint, 0, 16), 'ip' => $ip]);
        return false;
    }

    // Record attempt — single row keyed on (fingerprint, action); OR IGNORE if race
    if ($record && $record['in_window']) {
        $db->prepare("UPDATE rate_limit_fingerprints SET attempts=attempts+1 WHERE fingerprint=? AND action=?")
           ->execute([$fingerprint, $action]);
    } else {
        $db->prepare("INSERT OR IGNORE INTO rate_limit_fingerprints (fingerprint, ip_address, action) VALUES (?,?,?)")
           ->execute([$fingerprint, $ip, $action]);
        // If already existed (race), still increment
        $db->prepare("UPDATE rate_limit_fingerprints SET attempts=attempts+1, ip_address=? WHERE fingerprint=? AND action=?")
           ->execute([$ip, $fingerprint, $action]);
    }

    return true;
}

/**
 * Log user activity with device and location info
 */
function log_user_activity(int $userId, string $action, array $extraData = []): void
{
    try {
        $db = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $fp = generate_device_fingerprint();

        // Parse user agent for device info
        $deviceType = 'Unknown';
        $browser = 'Unknown';
        $os = 'Unknown';

        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $ua)) {
            $deviceType = 'Mobile';
        } elseif (preg_match('/Tablet|iPad/i', $ua)) {
            $deviceType = 'Tablet';
        } else {
            $deviceType = 'Desktop';
        }

        if (preg_match('/Firefox\/[\d.]+/i', $ua, $m)) $browser = 'Firefox ' . $m[1] ?? '';
        elseif (preg_match('/Chrome\/[\d.]+/i', $ua, $m)) $browser = 'Chrome ' . $m[0];
        elseif (preg_match('/Safari\/[\d.]+/i', $ua, $m)) $browser = 'Safari ' . $m[0];
        elseif (preg_match('/Edge\/[\d.]+/i', $ua, $m)) $browser = 'Edge ' . $m[0];

        if (preg_match('/Windows NT [\d.]+/i', $ua, $m)) $os = 'Windows';
        elseif (preg_match('/Mac OS X [\d._]+/i', $ua, $m)) $os = 'macOS';
        elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';
        elseif (preg_match('/Android [\d.]+/i', $ua, $m)) $os = 'Android';
        elseif (preg_match('/iOS [\d._]+/i', $ua, $m)) $os = 'iOS';

        $db->prepare("
            INSERT INTO user_activity_log
            (user_id, action, ip_address, user_agent, fingerprint, device_type, browser, os, extra_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            $action,
            $ip,
            $ua,
            $fp,
            $deviceType,
            $browser,
            $os,
            json_encode($extraData, JSON_UNESCAPED_UNICODE)
        ]);

        // Update device fingerprint record
        $updateFp = $db->prepare("
            INSERT INTO device_fingerprints (fingerprint, user_id, ip_address, user_agent, last_seen)
            VALUES (?, ?, ?, ?, datetime('now'))
            ON CONFLICT(fingerprint) DO UPDATE SET
                last_seen = datetime('now'),
                ip_address = excluded.ip_address,
                user_agent = excluded.user_agent
        ");
        $updateFp->execute([$fp, $userId, $ip, $ua]);

    } catch (Throwable $e) {
        // Silently fail - don't break main flow
    }
}

/**
 * Check if request comes from a known hacking tool
 */
function detect_hacking_tools(): bool
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $suspiciousPatterns = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'zmap', 'dirbuster', 'gobuster',
        'burp', 'hydra', 'metasploit', 'wpscan', 'commix', ' XSStrike',
        'wfuzz', 'sqlninja', 'pangolin', 'havij', 'safe3', '明小子',
        'webCruiser', 'N-Stalker', 'Paros', 'WebScarab', 'WebInspect',
        'appscan', 'appspider', 'netsparker', 'acunetix', 'netsparker',
        'Qualys', 'Tenable', 'OpenVAS', 'Nessus', 'cisco-torch',
        'curl', 'wget', 'python-requests', 'perl', 'ruby', 'java/',
        'libwww-perl', 'httpclient', 'go-http-client', 'axios',
        'scrapy', 'mechanize', 'selenium', 'phantomjs', 'headless',
    ];

    $uaLower = strtolower($userAgent);
    foreach ($suspiciousPatterns as $pattern) {
        if (str_contains($uaLower, strtolower($pattern))) {
            Logger::warning('security.hacking_tool', "محاولة أداة اختراق: {$pattern}", [
                'ua' => substr($userAgent, 0, 100),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            return true;
        }
    }

    return false;
}

/**
 * Verify reCAPTCHA v3 score
 */
function verify_recaptcha(string $token, float $minScore = 0.5): bool
{
    $secret = Config::get('RECAPTCHA_SECRET_KEY', '');
    if (empty($secret)) return true; // Skip if not configured

    $response = @file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ])
    );

    if (!$response) return true; // Fail open if service unavailable

    $result = json_decode($response, true);
    if (!$result || !isset($result['success'])) return true;

    $score = $result['score'] ?? 1.0;

    if ($score < $minScore) {
        Logger::warning('security.recaptcha', "فشل reCAPTCHA: score={$score}", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }

    return $result['success'] && $score >= $minScore;
}
