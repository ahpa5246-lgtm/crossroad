<?php
declare(strict_types=1);

/**
 * Certificate (A4 landscape) drawn with GD, wrapped into a one-page PDF.
 * Look: the site's dark pixel-RPG theme — navy field, carved wood frame, gold lettering,
 * a compass/sun medallion for the admin-managed logo. Font: Cairo (same family as the site, OFL).
 */
class CertificatePdf
{
    // A4 landscape @ 300dpi
    private const W = 3508;
    private const H = 2480;

    // ── Palette (taken from the site's dark theme) ───────────
    private const C_NAVY_T  = [ 34,  52,  74];   // field, top
    private const C_NAVY_B  = [ 18,  30,  43];   // field, bottom
    private const C_NAVY_DOT= [ 40,  60,  84];   // dither dots
    private const C_INK     = [ 14,   9,   5];   // outlines
    private const C_WOOD    = [ 75,  54,  36];
    private const C_WOOD_L  = [122,  90,  56];
    private const C_WOOD_D  = [ 43,  31,  21];
    private const C_EDGE    = [168, 116,  58];   // gold-brown edge
    private const C_GOLD    = [255, 210, 122];   // headings
    private const C_GOLD_D  = [232, 169,  59];
    private const C_CREAM   = [251, 232, 192];   // main text
    private const C_TEXT    = [232, 210, 166];
    private const C_MUTED   = [187, 162, 123];

    // Arabic shaping tables
    private static array $FORMS = [
        0x0621=>[0xFE80,0xFE80,null,null],0x0622=>[0xFE81,0xFE82,null,null],
        0x0623=>[0xFE83,0xFE84,null,null],0x0624=>[0xFE85,0xFE86,null,null],
        0x0625=>[0xFE87,0xFE88,null,null],0x0626=>[0xFE89,0xFE8A,0xFE8B,0xFE8C],
        0x0627=>[0xFE8D,0xFE8E,null,null],0x0628=>[0xFE8F,0xFE90,0xFE91,0xFE92],
        0x0629=>[0xFE93,0xFE94,null,null],0x062A=>[0xFE95,0xFE96,0xFE97,0xFE98],
        0x062B=>[0xFE99,0xFE9A,0xFE9B,0xFE9C],0x062C=>[0xFE9D,0xFE9E,0xFE9F,0xFEA0],
        0x062D=>[0xFEA1,0xFEA2,0xFEA3,0xFEA4],0x062E=>[0xFEA5,0xFEA6,0xFEA7,0xFEA8],
        0x062F=>[0xFEA9,0xFEAA,null,null],0x0630=>[0xFEAB,0xFEAC,null,null],
        0x0631=>[0xFEAD,0xFEAE,null,null],0x0632=>[0xFEAF,0xFEB0,null,null],
        0x0633=>[0xFEB1,0xFEB2,0xFEB3,0xFEB4],0x0634=>[0xFEB5,0xFEB6,0xFEB7,0xFEB8],
        0x0635=>[0xFEB9,0xFEBA,0xFEBB,0xFEBC],0x0636=>[0xFEBD,0xFEBE,0xFEBF,0xFEC0],
        0x0637=>[0xFEC1,0xFEC2,0xFEC3,0xFEC4],0x0638=>[0xFEC5,0xFEC6,0xFEC7,0xFEC8],
        0x0639=>[0xFEC9,0xFECA,0xFECB,0xFECC],0x063A=>[0xFECD,0xFECE,0xFECF,0xFED0],
        0x0641=>[0xFED1,0xFED2,0xFED3,0xFED4],0x0642=>[0xFED5,0xFED6,0xFED7,0xFED8],
        0x0643=>[0xFED9,0xFEDA,0xFEDB,0xFEDC],0x0644=>[0xFEDD,0xFEDE,0xFEDF,0xFEE0],
        0x0645=>[0xFEE1,0xFEE2,0xFEE3,0xFEE4],0x0646=>[0xFEE5,0xFEE6,0xFEE7,0xFEE8],
        0x0647=>[0xFEE9,0xFEEA,0xFEEB,0xFEEC],0x0648=>[0xFEED,0xFEEE,null,null],
        0x0649=>[0xFEEF,0xFEF0,null,null],0x064A=>[0xFEF1,0xFEF2,0xFEF3,0xFEF4],
        0x0671=>[0xFB50,0xFB51,null,null],0x067E=>[0xFB56,0xFB57,0xFB58,0xFB59],
        0x0686=>[0xFB7A,0xFB7B,0xFB7C,0xFB7D],0x0698=>[0xFB8A,0xFB8B,null,null],
        0x06AF=>[0xFB92,0xFB93,0xFB94,0xFB95],0x06CC=>[0xFBFC,0xFBFD,0xFBFE,0xFBFF],
    ];
    private static array $RIGHT_JOIN = [
        0x0621,0x0622,0x0623,0x0624,0x0625,0x0627,0x0629,
        0x062F,0x0630,0x0631,0x0632,0x0648,0x0649,0x0671,0x0698
    ];


    // ── Main entry point ──────────────────────────────────────

    public static function generate(array $cert, string $verifyUrl, string $logoPath): string
    {
        if (!extension_loaded('gd')) throw new RuntimeException('GD extension not loaded');

        // The logo always comes from the admin panel (Settings → logo). Dark-mode logo suits the dark field.
        $adminLogo = function_exists('brand_logo_path') ? (brand_logo_path(true) ?: brand_logo_path(false)) : null;
        if ($adminLogo) $logoPath = $adminLogo;
        $brand = function_exists('brand_name') ? brand_name() : 'مفترق الطرق';

        $W = self::W; $H = self::H;
        $img = imagecreatetruecolor($W, $H);
        if (!$img) throw new RuntimeException('imagecreatetruecolor failed');
        imagealphablending($img, true);

        $cInk    = self::gc($img, self::C_INK);
        $cWood   = self::gc($img, self::C_WOOD);
        $cWoodL  = self::gc($img, self::C_WOOD_L);
        $cWoodD  = self::gc($img, self::C_WOOD_D);
        $cEdge   = self::gc($img, self::C_EDGE);
        $cGold   = self::gc($img, self::C_GOLD);
        $cGoldD  = self::gc($img, self::C_GOLD_D);
        $cCream  = self::gc($img, self::C_CREAM);
        $cText   = self::gc($img, self::C_TEXT);
        $cMuted  = self::gc($img, self::C_MUTED);
        $cWhite  = imagecolorallocate($img, 255, 255, 255);

        // ── Fonts (Cairo, bundled) ────────────────────────────
        $fd = ROOT_PATH . '/public/fonts/';
        $fall = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        $fR = is_file($fd . 'cert-regular.ttf') ? $fd . 'cert-regular.ttf' : $fall;
        $fB = is_file($fd . 'cert-bold.ttf')    ? $fd . 'cert-bold.ttf'    : $fall;
        $fK = is_file($fd . 'cert-black.ttf')   ? $fd . 'cert-black.ttf'   : $fB;

        $cx = (int)($W / 2);

        // ── Field: navy gradient + pixel dither ───────────────
        for ($y = 0; $y < $H; $y++) {
            $t = $y / $H;
            $c = imagecolorallocate($img,
                (int)(self::C_NAVY_T[0] + (self::C_NAVY_B[0] - self::C_NAVY_T[0]) * $t),
                (int)(self::C_NAVY_T[1] + (self::C_NAVY_B[1] - self::C_NAVY_T[1]) * $t),
                (int)(self::C_NAVY_T[2] + (self::C_NAVY_B[2] - self::C_NAVY_T[2]) * $t));
            imageline($img, 0, $y, $W, $y, $c);
        }
        $cDot = self::gc($img, self::C_NAVY_DOT);
        for ($y = 12; $y < $H; $y += 36) {
            for ($x = ((intdiv($y, 36) % 2) ? 30 : 12); $x < $W; $x += 36) imagefilledrectangle($img, $x, $y, $x + 5, $y + 5, $cDot);
        }

        // ── Wood frame ────────────────────────────────────────
        self::rect($img, 100, 100, $W - 100, $H - 100, $cInk);                 // outline
        self::rect($img, 116, 116, $W - 116, $H - 116, $cWood);                // plank
        self::frameLine($img, 124, 6, $cWoodL, $W, $H);                        // highlight
        self::frameLine($img, 196, 6, $cWoodD, $W, $H);                        // shade
        self::frameLine($img, 206, 12, $cEdge, $W, $H);                        // gold-brown edge
        self::frameLine($img, 220, 8, $cInk, $W, $H);
        // re-fill the field inside the frame (gradient again, cheap rows)
        for ($y = 228; $y < $H - 228; $y++) {
            $t = $y / $H;
            $c = imagecolorallocate($img,
                (int)(self::C_NAVY_T[0] + (self::C_NAVY_B[0] - self::C_NAVY_T[0]) * $t),
                (int)(self::C_NAVY_T[1] + (self::C_NAVY_B[1] - self::C_NAVY_T[1]) * $t),
                (int)(self::C_NAVY_T[2] + (self::C_NAVY_B[2] - self::C_NAVY_T[2]) * $t));
            imageline($img, 228, $y, $W - 229, $y, $c);
        }
        for ($y = 240; $y < $H - 240; $y += 36) {
            for ($x = ((intdiv($y, 36) % 2) ? 246 : 228); $x < $W - 240; $x += 36) imagefilledrectangle($img, $x, $y, $x + 5, $y + 5, $cDot);
        }
        // thin inner gold rule with stepped (pixel) corners
        self::frameLine($img, 290, 5, $cEdge, $W, $H);
        foreach ([[290, 290, 1, 1], [$W - 290, 290, -1, 1], [290, $H - 290, 1, -1], [$W - 290, $H - 290, -1, -1]] as [$px, $py, $dx, $dy]) {
            for ($i = 0; $i < 4; $i++) {
                $o = $i * 22;
                $x1 = $px + $dx * (10 + $o); $y1 = $py + $dy * (10 + $o);
                self::rect($img, min($x1, $x1 + $dx * 22), min($y1, $y1 + $dy * 22), max($x1, $x1 + $dx * 22), max($y1, $y1 + $dy * 22), $i % 2 ? $cGoldD : $cGold);
            }
            // corner rivet on the wood
            $rx = $px + $dx * -128; $ry = $py + $dy * -128;
            self::rect($img, $rx - 34, $ry - 34, $rx + 34, $ry + 34, $cInk);
            self::rect($img, $rx - 26, $ry - 26, $rx + 26, $ry + 26, $cGoldD);
            self::rect($img, $rx - 26, $ry - 26, $rx + 8, $ry + 8, $cGold);
        }

        // ── Medallion: compass ring + logo, pixel sun rays ────
        $mx = $cx; $my = 610; $mr = 160;
        foreach ([[0, -1], [1, -1], [1, 0], [1, 1], [-1, 1], [-1, 0], [-1, -1]] as [$ux, $uy]) {
            $diag = ($ux !== 0 && $uy !== 0);
            $d = (int)(($mr + 52) * ($diag ? 0.78 : 1.0));
            foreach ([[0, 32, $cGold], [52, 22, $cGoldD], [96, 14, $cEdge]] as [$step, $sz, $col]) {
                $dd = $d + (int)($step * ($diag ? 0.78 : 1.0));
                $px = $mx + $ux * $dd; $py = $my + $uy * $dd;
                self::rect($img, $px - (int)($sz / 2), $py - (int)($sz / 2), $px + (int)($sz / 2), $py + (int)($sz / 2), $col);
            }
        }
        imagefilledellipse($img, $mx, $my, $mr * 2 + 44, $mr * 2 + 44, $cInk);
        imagefilledellipse($img, $mx, $my, $mr * 2 + 24, $mr * 2 + 24, $cEdge);
        imagefilledellipse($img, $mx, $my, $mr * 2 - 6, $mr * 2 - 6, $cGold);
        imagefilledellipse($img, $mx, $my, $mr * 2 - 34, $mr * 2 - 34, $cInk);
        imagefilledellipse($img, $mx, $my, $mr * 2 - 46, $mr * 2 - 46, self::gc($img, [27, 40, 58]));

        $logoDrawn = false;
        if ($logoPath && is_file($logoPath)) {
            $li = @getimagesize($logoPath);
            if ($li) {
                $src = match($li['mime']) {
                    'image/png'  => @imagecreatefrompng($logoPath),
                    'image/jpeg' => @imagecreatefromjpeg($logoPath),
                    'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($logoPath) : false,
                    default      => false,
                };
                if ($src) {
                    $box = (int)(($mr - 36) * 1.5);
                    $sc = min($box / $li[0], $box / $li[1]);
                    $dW = max(1, (int)($li[0] * $sc));
                    $dH = max(1, (int)($li[1] * $sc));
                    imagealphablending($img, true);
                    imagecopyresampled($img, $src, $mx - (int)($dW / 2), $my - (int)($dH / 2), 0, 0, $dW, $dH, $li[0], $li[1]);
                    imagedestroy($src);
                    $logoDrawn = true;
                }
            }
        }
        if (!$logoDrawn) self::pixelSun($img, $mx, $my, 12, $cGold, $cGoldD, $cInk);

        // brand name under the medallion
        $bt = self::hasArabic($brand) ? self::reshape($brand) : $brand;
        self::cx($img, 42, $fB, $bt, $cx, $my + $mr + 118, $cGold);

        // ── Title ─────────────────────────────────────────────
        $ty = 1150;
        self::cxs($img, 125, $fK, self::reshape('شهادة مشاركة'), $cx, $ty, $cGold, $cInk, 7);
        self::spaced($img, 30, $fB, 'CERTIFICATE OF PARTICIPATION', $cx, $ty + 90, $cMuted, 12);
        self::pixelDivider($img, $cx, $ty + 150, $cGold, $cGoldD, $cEdge);

        // ── "presented to" + recipient ────────────────────────
        self::cx($img, 42, $fR, self::reshape('تمنح هذه الشهادة إلى'), $cx, $ty + 262, $cMuted);

        $name = trim($cert['display_name'] ?? '');
        if ($name === '') $name = '—';
        $nameTxt = self::hasArabic($name) ? self::reshape($name) : $name;
        $nameLen = mb_strlen($name);
        $nameSz  = $nameLen > 26 ? 74 : ($nameLen > 18 ? 92 : 112);
        $ny = $ty + 415;
        self::cxs($img, $nameSz, $fK, $nameTxt, $cx, $ny, $cCream, $cInk, 6);
        // wooden underline plate
        $nb = imagettfbbox($nameSz, 0, $fK, $nameTxt);
        $uw = max(700, min(1400, (int)(abs($nb[2] - $nb[0]) / 2) + 40));
        self::rect($img, $cx - $uw - 10, $ny + 44, $cx + $uw + 10, $ny + 62, $cInk);
        self::rect($img, $cx - $uw, $ny + 48, $cx + $uw, $ny + 58, $cGold);
        self::rect($img, $cx - $uw - 34, $ny + 38, $cx - $uw - 14, $ny + 68, $cGoldD);
        self::rect($img, $cx + $uw + 14, $ny + 38, $cx + $uw + 34, $ny + 68, $cGoldD);

        // ── Body ──────────────────────────────────────────────
        $body = trim($cert['description'] ?? '');
        if ($body === '') {
            $ch = trim($cert['course_title'] ?? $cert['challenge_title'] ?? $cert['form_title'] ?? '');
            $body = $ch !== ''
                ? "لإتمامه بنجاح «{$ch}» المقدم من {$brand}، تقديرا لجهده والتزامه خلال الرحلة."
                : "لإتمامه بنجاح برنامجا مقدما من {$brand}، تقديرا لجهده والتزامه خلال الرحلة.";
        }
        self::wrapLogical($img, 44, $fR, $body, $cx, $ny + 170, $cText, 1900, 80);

        // ── Footer: date | signature | QR ─────────────────────
        $fy   = 2050;
        $colR = $W - 700;   // date (right, RTL reading order)
        $colL = 700;        // QR (left)

        $issued = date('d / m / Y', strtotime((string)($cert['issued_at'] ?? 'now')));
        self::rect($img, $colR - 280, $fy - 80, $colR + 280, $fy - 72, $cEdge);
        self::cx($img, 48, $fB, $issued, $colR, $fy, $cCream);
        self::cx($img, 32, $fR, self::reshape('تاريخ الإصدار'), $colR, $fy + 66, $cMuted);

        $signer  = function_exists('setting') ? trim(setting('cert_signer_name', '')) : '';
        $signerT = function_exists('setting') ? trim(setting('cert_signer_title', '')) : '';
        if ($signer !== '') {
            $sTxt = self::hasArabic($signer) ? self::reshape($signer) : $signer;
            self::cx($img, 54, $fB, $sTxt, $cx, $fy, $cGold);
            self::rect($img, $cx - 340, $fy - 80, $cx + 340, $fy - 72, $cEdge);
            if ($signerT !== '') {
                $tTxt = self::hasArabic($signerT) ? self::reshape($signerT) : $signerT;
                self::cx($img, 34, $fR, $tTxt, $cx, $fy + 66, $cMuted);
            }
        }

        // QR in a small wooden plate; certificate id beside it
        $qrSz = 230;
        $qrX  = $colL - (int)($qrSz / 2);
        $qrY  = 1890;
        self::rect($img, $qrX - 40, $qrY - 40, $qrX + $qrSz + 40, $qrY + $qrSz + 40, $cInk);
        self::rect($img, $qrX - 34, $qrY - 34, $qrX + $qrSz + 34, $qrY + $qrSz + 34, $cWood);
        self::rect($img, $qrX - 34, $qrY - 34, $qrX + $qrSz + 34, $qrY - 28, $cWoodL);
        self::rect($img, $qrX - 20, $qrY - 20, $qrX + $qrSz + 20, $qrY + $qrSz + 20, $cEdge);
        self::rect($img, $qrX - 12, $qrY - 12, $qrX + $qrSz + 12, $qrY + $qrSz + 12, $cWhite);
        $qrData = self::fetchQr($verifyUrl);
        if ($qrData) {
            $qs = @imagecreatefromstring($qrData);
            if ($qs) {
                imagecopyresampled($img, $qs, $qrX, $qrY, 0, 0, $qrSz, $qrSz, imagesx($qs), imagesy($qs));
                imagedestroy($qs);
            }
        } else {
            $short = preg_replace('#^https?://#', '', $verifyUrl);
            self::cx($img, 16, $fR, mb_substr($short, 0, 24), $qrX + (int)($qrSz / 2), $qrY + (int)($qrSz / 2), $cInk);
        }
        $certId = strtoupper(substr(md5(($cert['id'] ?? '') . ($cert['user_id'] ?? '')), 0, 10));
        $idX = $qrX + $qrSz + 40 + 40 + 190;
        self::cx($img, 32, $fR, self::reshape('رقم الشهادة'), $idX, $qrY + 95, $cMuted);
        self::cx($img, 34, $fB, $certId, $idX, $qrY + 150, $cGold);

        // ── Render to JPEG bytes ──────────────────────────────
        ob_start();
        imagejpeg($img, null, 94);
        $jpeg = ob_get_clean();
        imagedestroy($img);
        if (!$jpeg) throw new RuntimeException('imagejpeg failed');

        return self::pdf($jpeg, self::W, self::H);
    }

    // ── Pixel-art drawing helpers ─────────────────────────────

    private static function rect($img, int $x1, int $y1, int $x2, int $y2, int $c): void
    {
        imagefilledrectangle($img, min($x1, $x2), min($y1, $y2), max($x1, $x2), max($y1, $y2), $c);
    }

    /** Hollow rectangle ring of thickness $t, $inset px from the page edge. */
    private static function frameLine($img, int $inset, int $t, int $c, int $W, int $H): void
    {
        self::rect($img, $inset, $inset, $W - $inset, $inset + $t - 1, $c);
        self::rect($img, $inset, $H - $inset - $t + 1, $W - $inset, $H - $inset, $c);
        self::rect($img, $inset, $inset, $inset + $t - 1, $H - $inset, $c);
        self::rect($img, $W - $inset - $t + 1, $inset, $W - $inset, $H - $inset, $c);
    }

    /** Stepped pixel diamond with squares running to both sides. */
    private static function pixelDivider($img, int $cx, int $y, int $c1, int $c2, int $c3): void
    {
        $s = 18;
        foreach ([-1, 1] as $dir) {
            for ($i = 0; $i < 14; $i++) {
                $x = $cx + $dir * (110 + $i * ($s + 14));
                $col = $i < 3 ? $c1 : ($i < 8 ? $c2 : $c3);
                self::rect($img, $x - (int)($s / 2), $y - (int)($s / 2), $x + (int)($s / 2), $y + (int)($s / 2), $col);
            }
        }
        $rows = [1, 3, 5, 3, 1];
        foreach ($rows as $r => $n) {
            $yy = $y + ($r - 2) * $s;
            for ($k = 0; $k < $n; $k++) {
                $xx = $cx + ($k - (int)(($n - 1) / 2)) * $s;
                self::rect($img, $xx - (int)($s / 2), $yy - (int)($s / 2), $xx + (int)($s / 2) - 1, $yy + (int)($s / 2) - 1, $r === 2 ? $c1 : $c2);
            }
        }
    }

    /** Fallback emblem when no logo is uploaded: blocky pixel sun. */
    private static function pixelSun($img, int $cx, int $cy, int $u, int $c1, int $c2, int $ink): void
    {
        $grid = [
            '..0111110..',
            '.011222110.',
            '01122222110',
            '11222222211',
            '12222222221',
            '12222222221',
            '12222222221',
            '11222222211',
            '01122222110',
            '.011222110.',
            '..0111110..',
        ];
        $n = count($grid);
        $off = (int)($n * $u / 2);
        foreach ($grid as $r => $row) {
            for ($k = 0, $L = strlen($row); $k < $L; $k++) {
                $ch = $row[$k];
                if ($ch === '.') continue;
                $col = $ch === '0' ? $ink : ($ch === '1' ? $c2 : $c1);
                $x = $cx - $off + $k * $u; $y = $cy - $off + $r * $u;
                self::rect($img, $x, $y, $x + $u - 1, $y + $u - 1, $col);
            }
        }
    }

    /** Centre-aligned text with a hard pixel-style drop shadow. */
    private static function cxs($img, int $sz, string $font, string $text, float $cx, float $y, int $color, int $shadow, int $off): void
    {
        self::cx($img, $sz, $font, $text, $cx + $off, $y + $off, $shadow);
        self::cx($img, $sz, $font, $text, $cx, $y, $color);
    }

    /** Horizontal colour gradient rectangle. */
    private static function hGradient($img, int $x1, int $y1, int $x2, int $y2, array $from, array $to): void
    {
        $w = max(1, $x2 - $x1);
        for ($x = $x1; $x < $x2; $x++) {
            $t = ($x - $x1) / $w;
            $c = imagecolorallocate($img,
                (int)($from[0] + ($to[0] - $from[0]) * $t),
                (int)($from[1] + ($to[1] - $from[1]) * $t),
                (int)($from[2] + ($to[2] - $from[2]) * $t));
            imageline($img, $x, $y1, $x, $y2 - 1, $c);
        }
    }

    /** Centre-aligned Latin text with letter-spacing. */
    private static function spaced($img, int $sz, string $font, string $text, float $cx, float $y, int $color, int $space): void
    {
        if (!is_file($font) || $text === '') return;
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $widths = []; $total = 0;
        foreach ($chars as $ch) {
            $b = imagettfbbox($sz, 0, $font, $ch);
            $w = abs($b[2] - $b[0]) + ($ch === ' ' ? (int)($sz * 0.35) : 0);
            $widths[] = $w; $total += $w + $space;
        }
        $x = $cx - ($total - $space) / 2;
        foreach ($chars as $i => $ch) {
            imagettftext($img, $sz, 0, (int)$x, (int)$y, $color, $font, $ch);
            $x += $widths[$i] + $space;
        }
    }

    // ── Color helper ─────────────────────────────────────────

    private static function gc($img, array $rgb): int
    {
        return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    }

    // ── Arabic shaping ────────────────────────────────────────

    private static function hasArabic(string $t): bool
    {
        return (bool)preg_match('/[\x{0600}-\x{06FF}]/u', $t);
    }

    public static function reshape(string $text): string
    {
        preg_match_all('/./us', $text, $m);
        $chars = array_map('mb_ord', $m[0]);
        $n     = count($chars);
        $shaped = [];

        for ($i = 0; $i < $n; $i++) {
            $cp = $chars[$i];
            if (!isset(self::$FORMS[$cp])) { $shaped[] = $cp; continue; }
            if ($cp >= 0x064B && $cp <= 0x065F) { $shaped[] = $cp; continue; }

            $prevA = false;
            for ($p = $i - 1; $p >= 0; $p--) {
                $pc = $chars[$p];
                if ($pc >= 0x064B && $pc <= 0x065F) continue;
                $prevA = isset(self::$FORMS[$pc]) && !in_array($pc, self::$RIGHT_JOIN, true);
                break;
            }
            $nextA = false;
            for ($nx = $i + 1; $nx < $n; $nx++) {
                $nc = $chars[$nx];
                if ($nc >= 0x064B && $nc <= 0x065F) continue;
                $nextA = isset(self::$FORMS[$nc]);
                break;
            }

            [$iso, $fin, $ini, $med] = self::$FORMS[$cp];
            $rj = in_array($cp, self::$RIGHT_JOIN, true);

            if ($prevA && $nextA && !$rj && $med !== null) $shaped[] = $med;
            elseif ($prevA && $fin !== null)                $shaped[] = $fin;
            elseif ($nextA && !$rj && $ini !== null)        $shaped[] = $ini;
            else                                            $shaped[] = $iso ?? $cp;
        }

        // Lam-alef ligature
        $lig = [];
        for ($i = 0; $i < count($shaped); $i++) {
            if (
                $i + 1 < count($shaped) &&
                in_array($shaped[$i], [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0], true) &&
                in_array($shaped[$i + 1], [0xFE8D, 0xFE8E, 0xFE81, 0xFE82, 0xFE83, 0xFE84, 0xFE87, 0xFE88], true)
            ) {
                $lig[] = 0xFEFB;
                $i++;
            } else {
                $lig[] = $shaped[$i];
            }
        }

        // Isolated presentation forms are missing from Cairo; the base letter renders the same isolated glyph.
        static $iso2base = null;
        if ($iso2base === null) {
            $iso2base = [];
            foreach (self::$FORMS as $base => $f) { if ($f[0] !== null && $f[0] !== $base) $iso2base[$f[0]] = $base; }
        }
        foreach ($lig as $k => $cp) { if (isset($iso2base[$cp])) $lig[$k] = $iso2base[$cp]; }

        // Split into words, reverse for RTL
        $words = [];
        $cw    = [];
        $ca    = false;
        foreach ($lig as $cp) {
            if ($cp === 0x20) {
                if ($cw) $words[] = ['c' => $cw, 'a' => $ca];
                $words[] = ['c' => [0x20], 'a' => false];
                $cw = []; $ca = false;
            } else {
                $cw[] = $cp;
                if ($cp > 0x0600) $ca = true;
            }
        }
        if ($cw) $words[] = ['c' => $cw, 'a' => $ca];
        $words = array_reverse($words);

        $out = '';
        foreach ($words as $w) {
            $cps = $w['a'] ? array_reverse($w['c']) : $w['c'];
            foreach ($cps as $cp) {
                if ($w['a']) $cp = [0x28 => 0x29, 0x29 => 0x28, 0x5B => 0x5D, 0x5D => 0x5B, 0xAB => 0xBB, 0xBB => 0xAB][$cp] ?? $cp;
                $out .= mb_chr($cp);
            }
        }
        return $out;
    }

    // ── Drawing helpers ───────────────────────────────────────

    /** Center-X aligned text */
    private static function cx($img, int $sz, string $font, string $text, float $cx, float $y, int $color): void
    {
        if (!is_file($font) || $text === '') return;
        try {
            $b = imagettfbbox($sz, 0, $font, $text);
            imagettftext($img, $sz, 0, (int)($cx - abs($b[2] - $b[0]) / 2), (int)$y, $color, $font, $text);
        } catch (Throwable) {}
    }

    /** Left-X aligned text */
    private static function lx($img, int $sz, string $font, string $text, float $x, float $y, int $color): void
    {
        if (!is_file($font) || $text === '') return;
        try {
            imagettftext($img, $sz, 0, (int)$x, (int)$y, $color, $font, $text);
        } catch (Throwable) {}
    }

    /** Right-X aligned text (x is the right edge) */
    private static function rrx($img, int $sz, string $font, string $text, float $rx, float $y, int $color): void
    {
        if (!is_file($font) || $text === '') return;
        try {
            $b = imagettfbbox($sz, 0, $font, $text);
            $tw = abs($b[2] - $b[0]);
            imagettftext($img, $sz, 0, (int)($rx - $tw), (int)$y, $color, $font, $text);
        } catch (Throwable) {}
    }

    /** Wrap LOGICAL text first, then shape each line (keeps Arabic line order correct). */
    private static function wrapLogical($img, int $sz, string $font, string $text, float $cx, float $y0, int $color, int $mW, int $lH): void
    {
        if (!is_file($font)) return;
        $ar  = self::hasArabic($text);
        $ws  = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $ln  = ''; $lines = [];
        foreach ($ws as $w) {
            $t = $ln === '' ? $w : $ln . ' ' . $w;
            $vis = $ar ? self::reshape($t) : $t;
            $b = imagettfbbox($sz, 0, $font, $vis);
            if (abs($b[2] - $b[0]) > $mW && $ln !== '') { $lines[] = $ln; $ln = $w; } else { $ln = $t; }
        }
        if ($ln !== '') $lines[] = $ln;
        foreach ($lines as $i => $l) self::cx($img, $sz, $font, $ar ? self::reshape($l) : $l, $cx, $y0 + $i * $lH, $color);
    }

    /** Word-wrapped center-aligned text */
    private static function wrap($img, int $sz, string $font, string $text, float $cx, float $y0, int $color, int $mW, int $lH): void
    {
        if (!is_file($font)) return;
        $ws = preg_split('/ /u', $text);
        $ln = '';
        $ls = [];
        foreach ($ws as $w) {
            $t = $ln ? "$ln $w" : $w;
            try {
                $b = imagettfbbox($sz, 0, $font, $t);
                if (abs($b[2] - $b[0]) > $mW && $ln !== '') { $ls[] = $ln; $ln = $w; }
                else $ln = $t;
            } catch (Throwable) { $ln = $t; }
        }
        if ($ln) $ls[] = $ln;
        foreach ($ls as $i => $l) self::cx($img, $sz, $font, $l, $cx, $y0 + $i * $lH, $color);
    }

    // ── QR fetch ──────────────────────────────────────────────

    private static function fetchQr(string $url): ?string
    {
        $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size'   => '500x500',
            'ecc'    => 'H',
            'format' => 'png',
            'data'   => $url,
            'color'  => '3A1606',
            'bgcolor' => 'FFFFFF',
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($data && $code === 200) return $data;
        }

        if (ini_get('allow_url_fopen')) {
            $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
            $data = @file_get_contents($apiUrl, false, $ctx);
            if ($data && strlen($data) > 100) return $data;
        }

        return null;
    }

    // ── PDF builder ───────────────────────────────────────────

    private static function pdf(string $jpeg, int $iW, int $iH): string
    {
        // A4 landscape in points
        $pw = 841.89;
        $ph = 595.28;
        $il = strlen($jpeg);
        $cs = "q {$pw} 0 0 {$ph} 0 0 cm /Im1 Do Q";
        $cl = strlen($cs);

        $o = [
            1 => "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            2 => "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            3 => "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pw} {$ph}] /Resources << /XObject << /Im1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            4 => "4 0 obj\n<< /Type /XObject /Subtype /Image /Width {$iW} /Height {$iH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$il} >>\nstream\n" . $jpeg . "\nendstream\nendobj\n",
            5 => "5 0 obj\n<< /Length {$cl} >>\nstream\n{$cs}\nendstream\nendobj\n",
        ];

        $h    = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $offs = [];
        $body = $h;

        for ($i = 1; $i <= 5; $i++) {
            $offs[$i] = strlen($body);
            $body    .= $o[$i];
        }

        $xp   = strlen($body);
        $body .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $body .= sprintf('%010d 00000 n ', $offs[$i]) . "\n";
        }
        $body .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xp}\n%%EOF\n";

        return $body;
    }
}
