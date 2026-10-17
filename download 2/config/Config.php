<?php
declare(strict_types=1);

class Config
{
    private static array $cfg = [];
    private static bool  $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) return;
        foreach ([ROOT_PATH . '/.env', ROOT_PATH . '/.env.example'] as $f) {
            if (!is_file($f)) continue;
            foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                self::$cfg[trim($k)] = trim($v, " \t\"'");
            }
            break;
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) self::load();
        return self::$cfg[$key] ?? getenv($key) ?: $default;
    }
}

Config::load();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
// HSTS — only over HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
// CSP — nonce-based, no unsafe-inline
$_cspNonce = csp_nonce();
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$_cspNonce}' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: https: blob:; connect-src 'self' https://api.anthropic.com; frame-src https://www.google.com; worker-src 'self' blob: https://cdnjs.cloudflare.com; manifest-src 'self';");

function recaptcha_site_key(): string  { return Config::get('RECAPTCHA_SITE_KEY') ?: setting('recaptcha_site_key',''); }
function recaptcha_secret(): string   { return Config::get('RECAPTCHA_SECRET_KEY') ?: setting('recaptcha_secret_key',''); }

// SMTP: .env takes priority, then DB settings
function smtp_config(): array {
    return [
        'SMTP_HOST'       => Config::get('SMTP_HOST')       ?: setting('smtp_host',      'smtp.gmail.com'),
        'SMTP_PORT'       => Config::get('SMTP_PORT')       ?: setting('smtp_port',      '587'),
        'SMTP_USER'       => Config::get('SMTP_USER')       ?: setting('smtp_user',      ''),
        'SMTP_PASS'       => Config::get('SMTP_PASS')       ?: setting('smtp_pass',      ''),
        'SMTP_FROM_NAME'  => Config::get('SMTP_FROM_NAME')  ?: setting('smtp_from_name', setting('site_name','مفترق الطرق')),
        'SMTP_FROM_EMAIL' => Config::get('SMTP_FROM_EMAIL') ?: setting('smtp_user',      ''),
    ];
}
