<?php
declare(strict_types=1);

/**
 * Dynamic brand assets, built from the logo + name set in the admin panel:
 *   /brand/icon.png?s=192   — square app icon / favicon (logo centred on the brand gradient)
 *   /brand/manifest.json    — PWA manifest with the admin-controlled name
 */
class BrandController
{
    public function icon(): void
    {
        $size = (int)($_GET['s'] ?? 192);
        $size = max(16, min(512, $size));
        $cacheDir = ROOT_PATH . '/public/uploads/.brand-cache';
        $key  = brand_version() . '_' . $size . '.png';
        $file = $cacheDir . '/' . $key;

        if (!is_file($file)) {
            $png = $this->render($size);
            if ($png === null) { $this->fallback($size); return; }
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
            foreach (glob($cacheDir . '/*.png') ?: [] as $old) {
                if (strpos(basename($old), brand_version() . '_') !== 0) @unlink($old);
            }
            @file_put_contents($file, $png);
            $this->send($png);
            return;
        }
        $this->send((string)file_get_contents($file));
    }

    private function send(string $png): void
    {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . strlen($png));
        echo $png;
        exit;
    }

    private function fallback(int $size): void
    {
        // 1x1 orange pixel (only used when the GD extension is unavailable)
        $this->send((string)base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg=='));
    }

    private function render(int $size): ?string
    {
        if (!extension_loaded('gd')) return null;
        $img = imagecreatetruecolor($size, $size);
        if (!$img) return null;
        imagefill($img, 0, 0, imagecolorallocate($img, 249, 115, 22));
        $path = brand_logo_path(false) ?: brand_logo_path(true);
        $drawn = false;
        if ($path && is_file($path)) {
            $info = @getimagesize($path);
            $src = false;
            if ($info) {
                $src = match ($info['mime']) {
                    'image/png'  => @imagecreatefrompng($path),
                    'image/jpeg' => @imagecreatefromjpeg($path),
                    'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
                    default      => false,
                };
            }
            if ($src) {
                $box = (int)($size * 0.70);
                $sc  = min($box / $info[0], $box / $info[1]);
                $dw  = max(1, (int)($info[0] * $sc));
                $dh  = max(1, (int)($info[1] * $sc));
                imagealphablending($img, true);
                imagecopyresampled($img, $src, (int)(($size - $dw) / 2), (int)(($size - $dh) / 2), 0, 0, $dw, $dh, $info[0], $info[1]);
                imagedestroy($src);
                $drawn = true;
            }
        }
        if (!$drawn) {   // no logo yet: draw a simple crossroads mark
            $c = imagecolorallocate($img, 42, 20, 7);
            imagesetthickness($img, max(2, (int)($size * 0.09)));
            $m = (int)($size / 2);
            imageline($img, (int)($size * .22), $m, (int)($size * .78), $m, $c);
            imageline($img, $m, (int)($size * .22), $m, (int)($size * .78), $c);
        }
        ob_start();
        imagepng($img);
        $out = ob_get_clean();
        imagedestroy($img);
        return $out ?: null;
    }

    public function manifest(): void
    {
        $name = brand_name();
        $icons = [];
        foreach ([48, 72, 96, 128, 144, 152, 192, 384, 512] as $s) {
            $icons[] = ['src' => brand_icon_url($s), 'sizes' => "{$s}x{$s}", 'type' => 'image/png', 'purpose' => 'any'];
        }
        $icons[] = ['src' => brand_icon_url(512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'];
        $data = [
            'name' => $name,
            'short_name' => mb_substr($name, 0, 12),
            'description' => setting('hero_subtitle', ''),
            'start_url' => (BASE_URL ?: '') . '/',
            'scope' => (BASE_URL ?: '') . '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#fffbf0',
            'theme_color' => '#f97316',
            'lang' => 'ar',
            'dir' => 'rtl',
            'icons' => $icons,
        ];
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
