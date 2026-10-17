<?php
declare(strict_types=1);
/**
 * Free-text content sections (title + free writing) managed from Admin → المحتوى → الأقسام.
 * Stored as JSON in settings.content_sections : [{"title":"رسالتنا","body":"…"}, …]
 */

function content_sections(): array
{
    $raw = setting('content_sections', '');
    $arr = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $s) {
        if (!is_array($s)) continue;
        $t = trim((string)($s['title'] ?? ''));
        $b = trim((string)($s['body'] ?? ''));
        if ($t === '' && $b === '') continue;
        $out[] = ['title' => $t, 'body' => $b];
    }
    return $out;
}

/** Safe mini-markup: paragraphs, **bold**, "- " bullets, "1. " numbered lists, [text](https://link). */
function render_rich_text(string $text): string
{
    $text = str_replace(["\r\n", "\r", '\n'], "\n", $text);
    $blocks = preg_split("/\n{2,}/", trim($text)) ?: [];
    $html = '';
    $inline = function (string $s): string {
        $s = e($s);
        $s = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u', '<a href="$2" target="_blank" rel="noopener">$1</a>', $s) ?? $s;
        return $s;
    };
    foreach ($blocks as $block) {
        $lines = array_values(array_filter(array_map('rtrim', explode("\n", $block)), fn($l) => trim($l) !== ''));
        if (!$lines) continue;
        $isUl = true; $isOl = true;
        foreach ($lines as $l) {
            if (!preg_match('/^\s*[-•*]\s+/u', $l)) $isUl = false;
            if (!preg_match('/^\s*\d+[.)]\s+/u', $l)) $isOl = false;
        }
        if ($isUl || $isOl) {
            $tag = $isUl ? 'ul' : 'ol';
            $html .= "<$tag>";
            foreach ($lines as $l) {
                $item = preg_replace($isUl ? '/^\s*[-•*]\s+/u' : '/^\s*\d+[.)]\s+/u', '', $l) ?? $l;
                $html .= '<li>' . $inline($item) . '</li>';
            }
            $html .= "</$tag>";
        } else {
            $html .= '<p>' . implode('<br>', array_map($inline, $lines)) . '</p>';
        }
    }
    return $html;
}

/** Number → Arabic-Indic digits for section markers (01, 02 …). */
function ar_digits(int|string $n): string
{
    return strtr((string)$n, ['0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤','5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩']);
}


/** Generic JSON list stored in settings: [{title, body}] (rules) */
function rules_items(): array
{
    $arr = json_decode(setting('rules_items', ''), true);
    $out = [];
    foreach (is_array($arr) ? $arr : [] as $r) {
        if (!is_array($r)) continue;
        $t = trim((string)($r['title'] ?? '')); $b = trim((string)($r['body'] ?? ''));
        if ($t !== '' || $b !== '') $out[] = ['title' => $t, 'body' => $b];
    }
    return $out;
}

/** Navigation bar items (editable from admin). */
function nav_items(): array
{
    $arr = json_decode(setting('nav_items', ''), true);
    $out = [];
    foreach (is_array($arr) ? $arr : [] as $r) {
        if (!is_array($r)) continue;
        $l = trim((string)($r['label'] ?? '')); $u = trim((string)($r['url'] ?? ''));
        if ($l !== '' && $u !== '' && stripos($u, '/news') === false) $out[] = ['label' => $l, 'url' => $u];
    }
    if ($out) return $out;
    return [
        ['label' => 'الرئيسية',   'url' => '/'],
        ['label' => 'عن التيم',   'url' => '/#about'],
        ['label' => 'التقديم',    'url' => '/apply'],
        ['label' => 'الإنجازات',  'url' => '/achievements'],
        ['label' => 'المجلة',     'url' => '/magazine'],
        ['label' => 'الإدارة',    'url' => '/team'],
        ['label' => 'القوانين',   'url' => rules_items() ? '/#rules' : '/terms'],
        ['label' => 'تواصل معنا', 'url' => '/contact'],
    ];
}

function nav_href(string $url): string
{
    if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) return $url;
    if ($url[0] === '#') return BASE_URL . '/' . $url;
    return BASE_URL . ($url[0] === '/' ? $url : '/' . $url);
}

/** Latest open application forms (home preview). */
function open_forms(int $limit = 3): array
{
    try {
        return Database::getInstance()->query("SELECT id,title,description,cover_image FROM application_forms WHERE status='open' ORDER BY sort_order, updated_at DESC LIMIT " . (int)$limit)->fetchAll() ?: [];
    } catch (Throwable $e) { return []; }
}

/** The most recently created open application form (or null). */
function latest_open_form(): ?array
{
    try {
        return Database::getInstance()->query("SELECT id,title FROM application_forms WHERE status='open' ORDER BY id DESC LIMIT 1")->fetch() ?: null;
    } catch (Throwable $e) { return null; }
}

/** Admin-managed campaign slides for the home carousel. */
function campaign_slides(): array
{
    try {
        return Database::getInstance()->query("SELECT * FROM campaigns WHERE is_visible=1 ORDER BY sort_order ASC, id DESC")->fetchAll() ?: [];
    } catch (Throwable $e) { return []; }
}


function achievements_list(): array
{
    try {
        $rows = Database::getInstance()->query("SELECT * FROM achievements WHERE is_visible=1 ORDER BY sort_order ASC, id DESC")->fetchAll() ?: [];
    } catch (Throwable $e) { return []; }
    foreach ($rows as &$r) { $im = json_decode((string)$r['images'], true); $r['images'] = is_array($im) ? array_values($im) : []; }
    return $rows;
}

/** Instagram / Telegram links for the contact page + footer. */
function contact_links(): array
{
    $tg = trim(setting('social_telegram', '')) ?: trim(setting('social_twitter', ''));
    return ['instagram' => trim(setting('social_instagram', '')), 'telegram' => $tg, 'email' => trim(setting('contact_email', ''))];
}


/** Is this nav item the current page? (hash links never count as active) */
function nav_is_active(string $url): bool
{
    if (str_contains($url, '#') && $url !== '/') return false;
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim(BASE_URL, '/');
    if ($base !== '' && str_starts_with($cur, $base)) $cur = substr($cur, strlen($base)) ?: '/';
    $u = parse_url($url, PHP_URL_PATH) ?: '/';
    if (preg_match('#^(https?:)?//#i', $url)) return false;
    return rtrim($cur, '/') === rtrim($u, '/') || ($u !== '/' && str_starts_with($cur, rtrim($u, '/') . '/'));
}


/** Icon for a nav / dock item, chosen from its URL. */
function nav_icon(string $url): string
{
    $u = strtolower($url);
    $map = ['achievements' => 'trophy', 'magazine' => 'newspaper', 'news' => 'calendar', 'team' => 'users', 'contact' => 'mail', 'apply' => 'clipboard',
            'terms' => 'file-text', 'rules' => 'file-text', 'about' => 'info', 'account' => 'user'];
    foreach ($map as $k => $ic) if (str_contains($u, $k)) return $ic;
    return $u === '/' || $u === '' ? 'home' : 'compass';
}

/** Items shown in the bottom dock (tablet + phone): real pages only, max 4 — the 5th slot is "المزيد"; everything else lives there. */
function dock_items(): array
{
    $out = [];
    foreach (nav_items() as $i) {
        if (str_contains($i['url'], '#') && $i['url'] !== '/') continue;
        if (stripos($i['url'], 'challenges') !== false) continue;
        $out[] = $i;
        if (count($out) >= 4) break;
    }
    return $out;
}
