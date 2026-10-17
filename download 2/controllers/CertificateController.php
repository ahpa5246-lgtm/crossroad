<?php
declare(strict_types=1);

class CertificateController
{
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function verify(): void
    {
        $token = trim($_GET['token'] ?? '');
        $cert  = null;
        if ($token !== '' && preg_match('/^[a-f0-9]{48}$/', $token)) {
            $s = $this->db->prepare("SELECT c.*, u.display_name, u.username, af.title AS form_title, ch.title AS challenge_title, co.title AS course_title FROM certificates c JOIN users u ON c.user_id=u.id LEFT JOIN application_forms af ON c.form_id=af.id LEFT JOIN challenges ch ON c.challenge_id=ch.id LEFT JOIN courses co ON c.course_id=co.id WHERE c.token=? LIMIT 1");
            $s->execute([$token]);
            $cert = $s->fetch() ?: null;
            Logger::info('cert.verify', "تحقق: {$token}", ['valid' => (bool)$cert]);
        }
        render('certificates.verify', ['cert' => $cert, 'token' => $token, 'pageTitle' => 'التحقق من الشهادة']);
    }

    public function view(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            http_response_code(404);
            require ROOT_PATH . '/views/404.php';
            return;
        }
        $s = $this->db->prepare("SELECT c.*, u.display_name, u.username, af.title AS form_title, ch.title AS challenge_title, co.title AS course_title FROM certificates c JOIN users u ON c.user_id=u.id LEFT JOIN application_forms af ON c.form_id=af.id LEFT JOIN challenges ch ON c.challenge_id=ch.id LEFT JOIN courses co ON c.course_id=co.id WHERE c.token=? AND c.is_valid=1 LIMIT 1");
        $s->execute([$token]);
        $cert = $s->fetch() ?: null;
        if (!$cert) {
            render('certificates.verify', ['cert' => null, 'token' => $token, 'pageTitle' => 'شهادة غير موجودة']);
            return;
        }
        Logger::info('cert.view', "عرض شهادة: {$token}");
        $appUrl = rtrim(Config::get('APP_URL', ''), '/');
        render('certificates.view', [
            'cert'      => $cert,
            'token'     => $token,
            'appUrl'    => $appUrl,
            'pageTitle' => 'شهادة — ' . $cert['display_name'],
        ]);
    }

    /**
     * Stream PDF inline (for embedding in <iframe>) — public, token is the key
     */
    public function pdfInline(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            http_response_code(400); exit('رمز غير صالح');
        }
        $s = $this->db->prepare("SELECT c.*, u.display_name, u.username, af.title AS form_title, ch.title AS challenge_title, co.title AS course_title FROM certificates c JOIN users u ON c.user_id=u.id LEFT JOIN application_forms af ON c.form_id=af.id LEFT JOIN challenges ch ON c.challenge_id=ch.id LEFT JOIN courses co ON c.course_id=co.id WHERE c.token=? AND c.is_valid=1 LIMIT 1");
        $s->execute([$token]);
        $cert = $s->fetch();
        if (!$cert) { http_response_code(404); exit('الشهادة غير موجودة'); }

        require_once ROOT_PATH . '/helpers/CertificatePdf.php';
        $appUrl    = rtrim(Config::get('APP_URL', ''), '/');
        $verifyUrl = $appUrl . '/certificates/verify?token=' . rawurlencode($token);
        // Logo comes from the admin panel (falls back to nothing → brand name text on the certificate)
        $logoPath  = brand_logo_path(false) ?: (brand_logo_path(true) ?: '');
        try {
            $pdf = CertificatePdf::generate($cert, $verifyUrl, $logoPath);
        } catch (Throwable $e) {
            Logger::error('cert.pdf_inline', $e->getMessage());
            http_response_code(500); exit('فشل إنشاء الشهادة');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="certificate.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=300');
        echo $pdf;
        exit;
    }

    /**
     * Force-download PDF — public, token is the key
     */
    public function downloadPdf(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            http_response_code(400); exit('رمز غير صالح');
        }

        // If accessed via /account/* route, verify ownership
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $requireAuth = str_contains($uri, '/account/');

        $s = $this->db->prepare("SELECT c.*, u.display_name, u.username, af.title AS form_title, ch.title AS challenge_title, co.title AS course_title FROM certificates c JOIN users u ON c.user_id=u.id LEFT JOIN application_forms af ON c.form_id=af.id LEFT JOIN challenges ch ON c.challenge_id=ch.id LEFT JOIN courses co ON c.course_id=co.id WHERE c.token=? AND c.is_valid=1 LIMIT 1");
        $s->execute([$token]);
        $cert = $s->fetch();
        if (!$cert) { http_response_code(404); exit('الشهادة غير موجودة'); }

        require_once ROOT_PATH . '/helpers/CertificatePdf.php';
        $appUrl    = rtrim(Config::get('APP_URL', ''), '/');
        $verifyUrl = $appUrl . '/certificates/verify?token=' . rawurlencode($token);
        // Logo comes from the admin panel (falls back to nothing → brand name text on the certificate)
        $logoPath  = brand_logo_path(false) ?: (brand_logo_path(true) ?: '');
        try {
            $pdf = CertificatePdf::generate($cert, $verifyUrl, $logoPath);
        } catch (Throwable $e) {
            Logger::error('cert.pdf', $e->getMessage());
            http_response_code(500); exit('فشل إنشاء الشهادة');
        }

        $rawName = preg_replace('/[\r\n\t"\\\\]/u', '', (string)($cert['display_name'] ?? ''));
        $name = 'شهادة-' . mb_substr($rawName, 0, 40) . '-' . substr($token, 0, 8) . '.pdf';
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"cert.pdf\"; filename*=UTF-8''" . rawurlencode($name));
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-store');
        echo $pdf;
        exit;
    }
}
