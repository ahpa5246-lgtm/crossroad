<?php
declare(strict_types=1);

// Production: errors logged, never displayed
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
define('ROOT_PATH', __DIR__);
// Auto-detect BASE_URL — works on InfinityFree subdomains and custom domains
if (!defined('BASE_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Detect if running in a subdirectory
    $script   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base     = rtrim(dirname($script), '/\\');
    // If in root, base is '' not '/'
    define('BASE_URL', ($base === '/' || $base === '.') ? '' : $base);
}

require_once ROOT_PATH . '/helpers/functions.php';
require_once ROOT_PATH . '/helpers/icons.php';
require_once ROOT_PATH . '/config/Config.php';
require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/config/Logger.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';

// Run DB migrations / schema checks on every request (idempotent + fast)
require_once ROOT_PATH . '/helpers/Migrations.php';
Migrations::run(Database::getInstance());

require_once ROOT_PATH . '/helpers/Mailer.php';
require_once ROOT_PATH . '/helpers/brand.php';
require_once ROOT_PATH . '/helpers/avatar.php';
require_once ROOT_PATH . '/helpers/content.php';

// Safety net: any leftover emoji in HTML output becomes an inline SVG icon
ob_start('emoji_output_filter');

spl_autoload_register(static function (string $class): void {
    $file = ROOT_PATH . '/controllers/' . $class . '.php';
    if (is_file($file)) require_once $file;
    $helper = ROOT_PATH . '/helpers/' . $class . '.php';
    if (is_file($helper)) require_once $helper;
});

// Detect HTTPS behind proxies
if (!isset($_SERVER['HTTPS']) && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Session — secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 30 * 86400,
        'path'     => '/',
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// ── Restore session from remember_me cookie ────────────────────────────────
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_session'])) {
    $rsid = preg_replace('/[^a-f0-9]/','', $_COOKIE['remember_session']);
    if (strlen($rsid) === 64) {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT s.*,u.* FROM sessions s JOIN users u ON s.user_id=u.id WHERE s.id=? AND u.is_active=1 LIMIT 1");
        $stmt->execute([$rsid]);
        $row = $stmt->fetch();
        if ($row) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $row['user_id'];
            $_SESSION['session_id'] = $rsid;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $db->prepare("UPDATE sessions SET last_active=datetime('now') WHERE id=?")->execute([$rsid]);
        } else {
            setcookie('remember_session','',time()-3600,'/','',isset($_SERVER['HTTPS']),true);
        }
    }
}

// ── Maintenance mode ────────────────────────────────────────────
{
    $_maintenanceOn = (int)Database::getInstance()
        ->query("SELECT value FROM settings WHERE key='maintenance_mode' LIMIT 1")
        ->fetchColumn() === 1;
    if ($_maintenanceOn) {
        $_rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $_isAuth = str_starts_with($_rawUri, BASE_URL . '/admin') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/login') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/logout') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/2fa') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/forgot-password') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/reset-otp') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/resend-otp') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/new-password') ||
                   str_starts_with($_rawUri, BASE_URL . '/auth/register-verify');
        $_isAdmin = auth() && is_admin();
        if (!$_isAuth && !$_isAdmin) {
            require ROOT_PATH . '/views/maintenance.php';
            exit;
        }
    }
}

// Parse URI — strip query, strip leading slash duplicates
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri    = '/' . ltrim(preg_replace('#//+#', '/', $uri), '/');
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET' => [
        '/'                            => ['HomeController',       'index'],
        '/apply'                       => ['ApplicationController','index'],
        '/apply/form'                  => ['ApplicationController','show'],
        '/news'                        => ['NewsController',       'index'],
        '/news/view'                   => ['NewsController',       'view'],
        '/terms'                       => ['HomeController',       'terms'],
        '/install'                     => ['HomeController',       'install'],
        '/offline'                     => ['HomeController',       'offline'],
        '/achievements'                => ['HomeController',       'achievements'],
        '/magazine'                    => ['MagazineController',   'index'],
        '/magazine/view'               => ['MagazineController',   'view'],
        '/magazine/write'              => ['MagazineController',   'write'],
        '/magazine/edit'               => ['MagazineController',   'edit'],
        '/team'                        => ['HomeController',       'team'],
        '/contact'                     => ['HomeController',       'contact'],
        '/brand/icon.png'              => ['BrandController',      'icon'],
        '/brand/manifest.json'         => ['BrandController',      'manifest'],
        '/guide/2fa'                   => ['HomeController',       'guide2fa'],
        '/challenges'                  => ['ChallengeController',  'index'],
        '/challenges/view'             => ['ChallengeController',  'view'],
        '/challenges/take'             => ['ChallengeController',  'take'],
        '/challenges/result'           => ['ChallengeController',  'result'],
        '/certificates/verify'         => ['CertificateController','verify'],
        '/certificates/view'           => ['CertificateController','view'],
        '/certificates/pdf-inline'     => ['CertificateController','pdfInline'],
        '/certificates/download-pdf'   => ['CertificateController','downloadPdf'],
        '/auth/csrf-refresh'           => ['AuthController',       'csrfRefresh'],
        '/auth/login'                  => ['AuthController',       'loginForm'],
        '/auth/register'               => ['AuthController',       'registerForm'],
        '/auth/google'                 => ['AuthController',       'googleStart'],
        '/auth/google/callback'        => ['AuthController',       'googleCallback'],
        '/auth/complete'               => ['AuthController',       'completeForm'],
        '/auth/register-verify'        => ['AuthController',       'registerVerifyForm'],
        '/auth/forgot-password'        => ['AuthController',       'forgotPasswordForm'],
        '/auth/reset-otp'              => ['AuthController',       'resetOtpForm'],
        '/auth/new-password'           => ['AuthController',       'newPasswordForm'],
        '/auth/2fa'                    => ['AuthController',       'twoFactorForm'],
        '/auth/2fa/choose'             => ['AuthController',       'twoFactorChooseForm'],
        '/auth/logout'                 => ['AuthController',       'logout'],
        '/auth/sessions'               => ['AuthController',       'sessions'],
        '/account'                     => ['AccountController',    'index'],
        '/account/notifications'  => ['AccountController','notifications'],
        '/account/notifications/count' => ['AccountController','notificationCount'],
        '/account/certificates'        => ['AccountController',    'certificates'],
        '/account/certificates/download' => ['CertificateController', 'downloadPdf'],
        '/account/2fa/setup'           => ['AccountController',    'twoFaSetup'],
        '/account/password/verify'     => ['AccountController',    'changePasswordVerifyForm'],
        '/account/email/verify'        => ['AccountController',    'changeEmailVerifyForm'],
        '/account/delete/verify'       => ['AccountController',    'deleteAccountVerifyForm'],
        '/admin'                       => ['AdminController',      'dashboard'],
        '/admin/content'               => ['AdminController',      'content'],
        '/admin/team'                  => ['AdminController',      'team'],
        '/admin/campaigns'             => ['AdminController',      'campaigns'],
        '/admin/achievements'          => ['AdminController',      'achievements'],
        '/admin/articles'              => ['AdminController',      'articles'],
        '/admin/articles/edit'         => ['AdminController',      'articleEdit'],
        '/admin/forms'                 => ['AdminController',      'forms'],
        '/admin/forms/new'             => ['AdminController',      'formNew'],
        '/admin/forms/edit'            => ['AdminController',      'formEdit'],
        '/admin/news'                  => ['AdminController',      'news'],
        '/admin/news/new'              => ['AdminController',      'newsNew'],
        '/admin/news/edit'             => ['AdminController',      'newsEdit'],
        '/admin/submissions'           => ['AdminController',      'submissions'],
        '/admin/accepted'              => ['AdminController',      'accepted'],
        '/admin/submissions/export'    => ['AdminController',      'submissionsExport'],
        '/admin/challenges'            => ['AdminController',      'challenges'],
        '/admin/challenges/new'        => ['AdminController',      'challengeNew'],
        '/admin/challenges/edit'       => ['AdminController',      'challengeEdit'],
        '/admin/challenges/completions'=> ['AdminController',      'challengeCompletions'],
        '/admin/users'                 => ['AdminController',      'users'],
        '/admin/users/edit'            => ['AdminController',      'userEditForm'],
        '/admin/certificates'          => ['AdminController',      'certificates'],
        '/admin/certificates/issue'    => ['AdminController',      'certificateIssue'],
        '/admin/certificates/qr'       => ['AdminController',      'certificateQr'],
        '/admin/sql'  => ['AdminController','sqlQuery'],
        '/admin/admins/new'  => ['AdminController','adminCreateForm'],
        '/admin/certs/bulk-form'  => ['AdminController','bulkCertForm'],
        '/admin/users/bulk-form'      => ['AdminController','bulkUserForm'],
        '/admin/users/promote'        => ['AdminController','promoteForm'],
        '/admin/certs/users-for-form' => ['AdminController','usersForForm'],
        '/admin/logs'                  => ['AdminController',      'logs'],
        '/admin/settings'             => ['AdminController',      'settings'],
        '/admin/features'              => ['AdminController',      'features'],
        '/admin/announcements'         => ['AdminController',      'announcements'],
        '/admin/submissions/view'      => ['AdminController',      'submissionView'],

    ],
    'POST' => [
        '/auth/login'                         => ['AuthController',       'login'],
        '/auth/register'                      => ['AuthController',       'register'],
        '/auth/complete'                      => ['AuthController',       'complete'],
        '/auth/register-verify'               => ['AuthController',       'registerVerify'],
        '/auth/forgot-password'               => ['AuthController',       'forgotPassword'],
        '/auth/reset-otp'                     => ['AuthController',       'resetOtp'],
        '/auth/resend-otp'                    => ['AuthController',       'resendOtp'],
        '/auth/new-password'                  => ['AuthController',       'newPassword'],
        '/auth/2fa'                           => ['AuthController',       'twoFactor'],
        '/auth/2fa/choose'                    => ['AuthController',       'twoFactorChoose'],
        '/auth/2fa/resend'                    => ['AuthController',       'twoFactorResend'],
        '/auth/logout'                        => ['AuthController',       'logout'],
        '/auth/revoke-session'                => ['AuthController',       'revokeSession'],
        '/account/notifications/read'         => ['AccountController',    'markNotificationsRead'],
        '/account/update'                     => ['AccountController',    'update'],
        '/account/password/request'           => ['AccountController',    'changePasswordRequest'],
        '/account/password/verify'            => ['AccountController',    'changePasswordVerify'],
        '/account/email/request'              => ['AccountController',    'changeEmailRequest'],
        '/account/email/verify'               => ['AccountController',    'changeEmailVerify'],
        '/account/delete/request'             => ['AccountController',    'deleteAccountRequest'],
        '/account/delete/verify'              => ['AccountController',    'deleteAccountVerify'],
        '/account/otp/resend'                 => ['AccountController',    'resendAccountOtp'],
        '/account/2fa/enable'                 => ['AccountController',    'twoFaEnable'],
        '/account/2fa/disable'                => ['AccountController',    'twoFaDisable'],
        '/account/2fa/email/enable'           => ['AccountController',    'emailTwoFaEnable'],
        '/account/2fa/email/disable'          => ['AccountController',    'emailTwoFaDisable'],
        '/apply/submit'                       => ['ApplicationController','submit'],
        '/challenges/save-progress'           => ['ChallengeController',  'saveProgress'],
        '/challenges/reset'  => ['ChallengeController','resetAttempt'],
        '/challenges/submit'                  => ['ChallengeController',  'submitChallenge'],
        '/admin/team/save'             => ['AdminController',      'teamSave'],
        '/admin/team/delete'           => ['AdminController',      'teamDelete'],
        '/admin/campaigns/save'        => ['AdminController',      'campaignSave'],
        '/admin/campaigns/delete'      => ['AdminController',      'campaignDelete'],
        '/admin/achievements/save'     => ['AdminController',      'achievementSave'],
        '/magazine/save'               => ['MagazineController',   'save'],
        '/magazine/delete'             => ['MagazineController',   'delete'],
        '/magazine/like'               => ['MagazineController',   'like'],
        '/admin/achievements/delete'   => ['AdminController',      'achievementDelete'],
        '/admin/articles/save'         => ['AdminController',      'articleSave'],
        '/admin/articles/delete'       => ['AdminController',      'articleDelete'],
        '/admin/content/save'                 => ['AdminController',      'contentSave'],
        '/admin/forms/save'                   => ['AdminController',      'formSave'],
        '/admin/forms/delete'                 => ['AdminController',      'formDelete'],
        '/admin/forms/toggle'                 => ['AdminController',      'formToggle'],
        '/admin/forms/question'               => ['AdminController',      'questionSave'],
        '/admin/forms/reorder'                => ['AdminController',      'questionReorder'],
        '/admin/forms/question/delete'        => ['AdminController',      'questionDelete'],
        '/admin/news/save'                    => ['AdminController',      'newsSave'],
        '/admin/news/delete'                  => ['AdminController',      'newsDelete'],
        '/admin/submissions/review'           => ['AdminController',      'submissionReview'],
        '/admin/challenges/save'              => ['AdminController',      'challengeSave'],
        '/admin/challenges/delete'            => ['AdminController',      'challengeDelete'],
        '/admin/challenges/question'          => ['AdminController',      'challengeQuestionSave'],
        '/admin/challenges/question/delete'   => ['AdminController',      'challengeQuestionDelete'],
        '/admin/challenges/reorder'           => ['AdminController',      'challengeQuestionReorder'],
        '/admin/users/role'                   => ['AdminController',      'userRoleUpdate'],
        '/admin/users/toggle'                 => ['AdminController',      'userToggle'],
        '/admin/users/delete'                 => ['AdminController',      'userDelete'],
        '/admin/users/password'               => ['AdminController',      'userPassword'],
        '/admin/users/edit'                   => ['AdminController',      'userEdit'],
        '/admin/submissions/delete'           => ['AdminController',      'submissionDelete'],
        '/admin/certificates/revoke'   => ['AdminController',      'certificateRevoke'],
        '/admin/certificates/save'            => ['AdminController',      'certificateSave'],
        '/admin/settings/save'                => ['AdminController',      'settingsSave'],
        '/admin/settings/test-smtp'           => ['AdminController',      'testSmtp'],
        '/admin/feature/toggle'               => ['AdminController',      'featureToggle'],
        '/admin/announcements/save'           => ['AdminController',      'announcementSave'],
        '/admin/announcements/delete'         => ['AdminController',      'announcementDelete'],
        '/admin/submissions/reject-all'       => ['AdminController',      'rejectAll'],
        '/admin/submissions/ai-review-all'    => ['AdminController',      'aiReviewAll'],
        '/admin/ai/ping'                      => ['AdminController',      'aiPing'],
        '/account/avatar'                     => ['AccountController',    'uploadAvatar'],
        '/admin/upload'                       => ['AdminController',      'upload'],
        '/admin/settings/logo'                => ['AdminController',      'logoUpload'],
        '/admin/settings/logo/remove'         => ['AdminController',      'logoRemove'],
        '/admin/sql/execute'  => ['AdminController','sqlExecute'],
        '/admin/admins/create'  => ['AdminController','adminCreate'],
        '/admin/certs/bulk'  => ['AdminController','bulkCertificate'],
        '/admin/users/bulk-create'    => ['AdminController','bulkUserCreate'],
        '/admin/users/promote'        => ['AdminController','promoteSubmit'],
        '/admin/submissions/accept-all'  => ['AdminController','acceptAll'],
        '/admin/submissions/ai-review'  => ['AdminController','aiReview'],
        '/admin/logs/clear'                   => ['AdminController',      'logsClear'],

    ],
];

// ── Feature flag gates ───────────────────────────────────────────
{
    $_db2 = Database::getInstance();
    $_featStmt = $_db2->query("SELECT key, value FROM settings WHERE key LIKE 'feature_%' OR key LIKE 'disable_%'");
    $_feats = [];
    foreach ($_featStmt->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $v) $_feats[$k] = (bool)$v;

    $_gateMap = [
        '/auth/login'    => 'feature_login',
        '/auth/register' => 'feature_register',
        '/apply'         => 'feature_apply',
        '/apply/form'    => 'feature_apply',
        '/apply/submit'  => 'feature_apply',
        '/challenges'    => 'feature_challenges',
        '/challenges/view'   => 'feature_challenges',
        '/challenges/take'   => 'feature_challenges',
        '/challenges/submit' => 'feature_challenges',
        '/challenges/reset'  => 'feature_challenges',
        '/news'          => 'feature_news',
        '/news/view'     => 'feature_news',
    ];
    // Check feature flags: feature_* must be truthy to allow, disable_* must be falsy
    if (isset($_gateMap[$uri])) {
        $featureKey = $_gateMap[$uri];
        $disableKey = str_replace('feature_', 'disable_', $featureKey);
        $featureOn  = $_feats[$featureKey] ?? true;  // default ON
        $disabled   = $_feats[$disableKey] ?? false; // default NOT disabled
        if (!$featureOn || $disabled) {
            http_response_code(503);
            require ROOT_PATH . '/views/maintenance.php';
            exit;
        }
    }
}

$handler = $routes[$method][$uri] ?? null;
if ($handler) {
    [$ctrl, $action] = $handler;
    (new $ctrl())->$action();
} else {
    http_response_code(404);
    require ROOT_PATH . '/views/404.php';
}
