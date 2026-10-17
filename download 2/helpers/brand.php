<?php
declare(strict_types=1);
/**
 * Brand helpers — the logo and the site name are controlled ONLY from the admin panel
 * (Settings → الهوية). Nothing here is hard-coded to a file on disk.
 */

const BRAND_DEFAULT_NAME = 'مفترق الطرق';

function brand_name(): string
{
    $n = trim(setting('site_name', BRAND_DEFAULT_NAME));
    return $n !== '' ? $n : BRAND_DEFAULT_NAME;
}

/** [lightLogoUrl, darkLogoUrl] — if only one is set it is used for both. */
function brand_logo_urls(): array
{
    static $c = null;
    if ($c !== null) return $c;
    $l = trim(setting('site_logo', ''));
    $d = trim(setting('site_logo_dark', ''));
    if ($l === '' && $d !== '') $l = $d;
    if ($d === '' && $l !== '') $d = $l;
    return $c = [$l, $d];
}

function brand_has_logo(): bool
{
    return brand_logo_urls()[0] !== '';
}

/** Cache-busted logo URL (changes whenever the admin uploads a new file). */
function brand_logo_src(string $url): string
{
    $p = brand_logo_local_path($url);
    $v = $p && is_file($p) ? (string)filemtime($p) : '';
    return $url . ($v !== '' ? (str_contains($url, '?') ? '&' : '?') . 'v=' . $v : '');
}

/**
 * <img> pair (light + dark). CSS shows the right one for the active theme.
 * Returns '' when no logo has been uploaded.
 */
function brand_logo_html(string $class = '', int $height = 0, bool $onDark = false): string
{
    [$l, $d] = brand_logo_urls();
    if ($l === '') return '';
    if ($onDark) {   // always-dark surface (footer, splash, sidebar): single image, uses the dark logo
        $style = $height > 0 ? ' style="height:' . $height . 'px;width:auto"' : '';
        return '<img src="' . e(brand_logo_src($d)) . '" alt="' . e(brand_name()) . '" class="logo-fixed ' . e(trim($class)) . '"' . $style . ' onerror="this.style.display=\'none\'">';
    }
    $alt = e(brand_name());
    $style = $height > 0 ? ' style="height:' . $height . 'px;width:auto"' : '';
    $cls = trim($class);
    $same = ($l === $d);
    $out  = '<img src="' . e(brand_logo_src($l)) . '" alt="' . $alt . '" class="logo-light ' . e($cls) . '"' . $style . ' onerror="this.style.display=\'none\'">';
    $out .= '<img src="' . e(brand_logo_src($same ? $l : $d)) . '" alt="' . $alt . '" class="logo-dark ' . e($cls) . '"' . $style . ' onerror="this.style.display=\'none\'">';
    return $out;
}

/** Logo if uploaded, otherwise the site name as text. */
function brand_mark_html(string $imgClass = '', string $textClass = 'brand-name', int $height = 0, bool $onDark = false): string
{
    $logo = brand_logo_html($imgClass, $height, $onDark);
    return $logo !== '' ? $logo : '<span class="' . e($textClass) . '">' . e(brand_name()) . '</span>';
}

/** Map a stored logo URL to a file inside /public/uploads (null if it is not a local upload). */
function brand_logo_local_path(string $url): ?string
{
    if ($url === '') return null;
    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path) || $path === '') return null;
    $path = rawurldecode($path);
    $pos  = strpos($path, '/public/uploads/');
    if ($pos === false) return null;
    $rel  = substr($path, $pos);
    $full = ROOT_PATH . $rel;
    $real = realpath($full);
    $base = realpath(ROOT_PATH . '/public/uploads');
    if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) return null;
    return $real;
}

/** Local file path of the admin logo (for GD: certificates, generated icons). */
function brand_logo_path(bool $dark = false): ?string
{
    [$l, $d] = brand_logo_urls();
    return brand_logo_local_path($dark ? $d : $l);
}

function brand_icon_url(int $size): string
{
    return BASE_URL . '/brand/icon.png?s=' . $size . '&v=' . brand_version();
}

/** Changes when the logo or name changes — used to bust icon caches. */
function brand_version(): string
{
    [$l] = brand_logo_urls();
    $p = brand_logo_local_path($l);
    return substr(md5(($p && is_file($p) ? filemtime($p) : '0') . '|' . $l), 0, 8);
}


/** Default pixel emblem (shown until a logo is uploaded from the admin panel). */
function brand_emblem_svg(int $size = 100): string
{
    $pts = '';
    $rows = [
        '....GGGG....', '..GGDDDDGG..', '.GDDDDDDDDG.', '.GDDDSSDDDG.', 'GDDDSWWSDDDG', 'GDDSWWWWSDDG',
        'GDDSWWWWSDDG', 'GDDDSWWSDDDG', '.GDDDSSDDDG.', '.GDDDDDDDDG.', '..GGDDDDGG..', '....GGGG....',
    ];
    $c = ['G' => '#C48A3F', 'D' => '#2A1D12', 'S' => '#E8A93B', 'W' => '#FFE2A8'];
    foreach ($rows as $y => $r) for ($x = 0; $x < 12; $x++) { $ch = $r[$x]; if ($ch !== '.') $pts .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1" fill="' . $c[$ch] . '"/>'; }
    return '<svg viewBox="0 0 12 12" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges" aria-hidden="true">' . $pts . '</svg>';
}
