<?php
declare(strict_types=1);

/**
 * Minimal SMTP mailer — supports Gmail (smtp.gmail.com:587 STARTTLS)
 */
class Mailer
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $fromName;
    private string $fromEmail;
    /** @var resource|false */
    private $socket = false;

    public function __construct()
    {
        $cfg = function_exists('smtp_config') ? smtp_config() : [];
        $this->host      = $cfg['SMTP_HOST']       ?? Config::get('SMTP_HOST',       'smtp.gmail.com');
        $this->port      = (int)($cfg['SMTP_PORT']  ?? Config::get('SMTP_PORT',       '587'));
        $this->user      = $cfg['SMTP_USER']        ?? Config::get('SMTP_USER',       '');
        $this->pass      = $cfg['SMTP_PASS']        ?? Config::get('SMTP_PASS',       '');
        $this->fromName  = $cfg['SMTP_FROM_NAME']   ?? Config::get('SMTP_FROM_NAME',  setting('site_name', 'مفترق الطرق'));
        $this->fromEmail = $cfg['SMTP_FROM_EMAIL']  ?? Config::get('SMTP_FROM_EMAIL', $this->user);
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = '', array $attachments = []): bool
    {
        if (!$this->user || !$this->pass) {
            throw new RuntimeException('SMTP credentials not configured.');
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid recipient email: {$toEmail}");
        }

        $textBody = $textBody ?: strip_tags($htmlBody);

        if (empty($attachments)) {
            $boundary = 'Boundary_' . bin2hex(random_bytes(8));
            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $body .= $textBody . "\r\n\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $body .= $htmlBody . "\r\n\r\n";
            $body .= "--{$boundary}--";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        } else {
            $mixedBoundary = 'Mixed_' . bin2hex(random_bytes(8));
            $altBoundary   = 'Alternative_' . bin2hex(random_bytes(8));

            $altBody  = "--{$altBoundary}\r\n";
            $altBody .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $altBody .= $textBody . "\r\n\r\n";
            $altBody .= "--{$altBoundary}\r\n";
            $altBody .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $altBody .= $htmlBody . "\r\n\r\n";
            $altBody .= "--{$altBoundary}--";

            $attachBody = "";
            foreach ($attachments as $att) {
                $attName = $att['name'] ?? 'attachment.pdf';
                $attData = $att['data'] ?? '';
                $attType = $att['type'] ?? 'application/pdf';
                $attB64  = base64_encode($attData);

                $attachBody .= "--{$mixedBoundary}\r\n";
                $attachBody .= "Content-Type: {$attType}; name=\"=?UTF-8?B?" . base64_encode($attName) . "?=\"\r\n";
                $attachBody .= "Content-Transfer-Encoding: base64\r\n";
                $attachBody .= "Content-Disposition: attachment; filename=\"=?UTF-8?B?" . base64_encode($attName) . "?=\"\r\n\r\n";
                $attachBody .= chunk_split($attB64, 76, "\r\n") . "\r\n";
            }

            $body  = "--{$mixedBoundary}\r\n";
            $body .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";
            $body .= $altBody . "\r\n\r\n";
            $body .= $attachBody;
            $body .= "--{$mixedBoundary}--";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$mixedBoundary}\"\r\n";
        }

        $headers .= "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromEmail}>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . bin2hex(random_bytes(16)) . "@mftaraq.local>\r\n";
        $headers .= "X-Mailer: CrossroadsMailer/2.0\r\n";

        $this->connect();
        $this->smtpConversation($toEmail, $headers . "\r\n" . $body);
        $this->disconnect();

        return true;
    }

    // ── SMTP internals ──────────────────────────────────────
    private function connect(): void
    {
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]]);
        $err = 0; $errMsg = '';
        $this->socket = @stream_socket_client("tcp://{$this->host}:{$this->port}", $err, $errMsg, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->socket) throw new RuntimeException("SMTP connect failed [{$err}]: {$errMsg}");
        stream_set_timeout($this->socket, 15);
        $this->expect(220, 'Connect');
    }

    private function smtpConversation(string $toEmail, string $fullMessage): void
    {
        $domain = gethostname() ?: 'mftaraq.local';
        $this->cmd("EHLO {$domain}", 250);
        $this->cmd('STARTTLS', 220);
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('STARTTLS upgrade failed.');
        }
        $this->cmd("EHLO {$domain}", 250);
        $this->cmd('AUTH LOGIN', 334);
        $this->cmd(base64_encode($this->user), 334);
        $this->cmd(base64_encode($this->pass), 235);
        $this->cmd("MAIL FROM: <{$this->fromEmail}>", 250);
        $this->cmd("RCPT TO: <{$toEmail}>", 250);
        $this->cmd('DATA', 354);
        $stuffed = preg_replace('/^\./m', '..', $fullMessage);
        fwrite($this->socket, $stuffed . "\r\n.\r\n");
        $this->expect(250, 'DATA body');
        $this->cmd('QUIT', 221);
    }

    private function cmd(string $cmd, int $expectedCode): string
    {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->expect($expectedCode, $cmd);
    }

    private function expect(int $expected, string $ctx = ''): string
    {
        $response = '';
        while (($line = fgets($this->socket, 512)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        $code = (int)substr($response, 0, 3);
        if ($code !== $expected) {
            throw new RuntimeException("SMTP [{$ctx}] expected {$expected}, got {$code}: " . trim($response));
        }
        return trim($response);
    }

    private function disconnect(): void
    {
        if ($this->socket) { fclose($this->socket); $this->socket = false; }
    }

    // ── Static helpers ─────────────────────────────────────

    public static function sendOtp(string $toEmail, string $toName, string $purpose): string
    {
        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $siteNm = setting('site_name', 'مفترق الطرق');
        $expMin = 10;

        $purposeLabel = match($purpose) {
            'register'        => 'تفعيل الحساب',
            'reset'           => 'إعادة تعيين كلمة المرور',
            'change_password' => 'تغيير كلمة المرور',
            'change_email'    => 'تغيير البريد الإلكتروني',
            'delete_account'  => 'حذف الحساب',
            '2fa'             => 'التحقق بخطوتين',
            default           => 'التحقق',
        };

        // ── Dark blue + cream email template ──
        $html = <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#fff9f0;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff9f0;padding:40px 20px">
<tr><td align="center">
<table width="100%" style="max-width:540px;background:#fffdf9;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(57,29,11,.10);border:1px solid #f6e5cf">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#391b07,#582b0d);padding:36px 40px;text-align:center;position:relative">
      <!-- Pixel dot grid (table-based) -->
      <div style="position:absolute;top:0;left:0;right:0;bottom:0;background-image:radial-gradient(circle,rgba(255,255,255,.04) 1px,transparent 1px);background-size:18px 18px;pointer-events:none"></div>
      <div style="color:#cba233;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:8px;font-family:'Courier New',monospace;position:relative;z-index:1">{$siteNm}</div>
      <div style="color:rgba(255,255,255,.9);font-size:18px;font-weight:700;position:relative;z-index:1">{$purposeLabel}</div>
      <!-- Bottom accent line -->
      <div style="margin-top:20px;height:2px;background:linear-gradient(90deg,transparent,rgba(203,162,51,.6),transparent);position:relative;z-index:1"></div>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="padding:40px 40px 32px">
      <p style="color:#62381c;font-size:15px;line-height:1.7;margin:0 0 8px">مرحبا {$toName}،</p>
      <p style="color:#62381c;font-size:15px;line-height:1.7;margin:0 0 28px">رمز التحقق الخاص بـ <strong style="color:#582b0d">{$purposeLabel}</strong>:</p>

      <!-- OTP Box -->
      <div style="background:#fcf2e8;border-radius:6px;padding:30px;text-align:center;margin:0 0 24px;border:1px solid #f1d9bc;border-top:3px solid #7c411a">
        <div style="font-family:'Courier New',monospace;font-size:44px;font-weight:900;color:#582b0d;letter-spacing:14px;line-height:1">{$otp}</div>
        <div style="font-size:12px;color:#926648;margin-top:12px;font-family:'Courier New',monospace">صالح لمدة {$expMin} دقائق</div>
      </div>

      <p style="color:#926648;font-size:13px;line-height:1.7;margin:0">إذا لم تطلب هذا، تجاهل الرسالة. لا تشارك هذا الرمز مع أحد.</p>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#fff0dc;padding:18px 40px;text-align:center;border-top:1px solid #f1d9bc">
      <div style="color:#926648;font-size:11px;font-family:'Courier New',monospace">{$siteNm} — رسالة تلقائية، لا ترد عليها</div>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

        $text = "رمز التحقق الخاص بـ {$purposeLabel}: {$otp}\nصالح لمدة {$expMin} دقائق.\nلا تشارك هذا الرمز مع أحد.";

        $mailer = new self();
        $mailer->send($toEmail, $toName, "{$purposeLabel} — {$siteNm}", $html, $text);

        return $otp;
    }
}
