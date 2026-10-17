<?php
declare(strict_types=1);
/** Pixel-art avatar generated from a seed (used when a team member has no uploaded photo). */
function pixel_avatar_svg(string $seed, int $size = 96): string
{
    $h = crc32($seed !== '' ? $seed : 'x');
    $pick = function (array $a, int $shift) use ($h) { return $a[(($h >> $shift) & 0xFF) % count($a)]; };
    $hair = $pick(['#2B1B12', '#4A2C17', '#7A4A21', '#1C1C1C', '#9A5B22'], 0);
    $skin = $pick(['#F1C27D', '#E0AC69', '#C68642', '#A9703A', '#FFDBAC'], 3);
    $shirt= $pick(['#F4F1EA', '#3E6FB0', '#B23A3A', '#3F8F5B', '#6A4C93'], 6);
    $bg   = $pick(['#F6C453', '#F59E2B', '#F2B84B', '#E9A23B'], 9);
    $beard= (($h >> 12) & 3) === 0;
    $long = (($h >> 14) & 3) === 1;
    $rows = [
        '....HHHH....',
        '...HHHHHH...',
        '..HHHHHHHH..',
        '..HHSSSSHH..',
        '..HSSSSSSH..',
        '..HSESSESH..',
        '..SSSSSSSS..',
        '..SSSMMSSS..',
        '...SSSSSS...',
        '....SSSS....',
        '..TTTCCTTT..',
        '.TTTTCCTTTT.',
        '.TTTTTTTTTT.',
        'TTTTTTTTTTTT',
    ];
    if ($beard) { $rows[7] = '..SBBMMBBS..'; $rows[8] = '...BBBBBB...'; $rows[9] = '....BBBB....'; }
    if ($long)  { foreach ([4,5,6,7,8,9] as $r) { $rows[$r] = 'H' . substr($rows[$r], 1, 10) . 'H'; } $rows[6] = 'HS' . substr($rows[6], 2, 8) . 'SH'; }
    $map = ['H' => $hair, 'S' => $skin, 'E' => '#1B120B', 'M' => '#7A2E1F', 'T' => $shirt, 'C' => '#FFFFFF', 'B' => $hair];
    $out = '<svg class="pxav" viewBox="0 0 12 14" width="' . $size . '" height="' . (int)round($size * 14 / 12) . '" shape-rendering="crispEdges" role="img" aria-hidden="true">'
         . '<rect width="12" height="14" fill="' . $bg . '"/>';
    foreach ($rows as $y => $line) {
        for ($x = 0, $n = strlen($line); $x < $n; $x++) {
            $ch = $line[$x];
            if ($ch === '.') continue;
            $w = 1;
            while ($x + $w < $n && $line[$x + $w] === $ch) $w++;
            $out .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="1" fill="' . $map[$ch] . '"/>';
            $x += $w - 1;
        }
    }
    return $out . '</svg>';
}

/** Team members (visible only), ordered. */
function team_members(bool $onlyVisible = true): array
{
    try {
        $sql = 'SELECT * FROM team_members' . ($onlyVisible ? ' WHERE is_visible=1' : '') . ' ORDER BY sort_order ASC, id ASC';
        return Database::getInstance()->query($sql)->fetchAll() ?: [];
    } catch (Throwable $e) { return []; }
}


/** Round avatar (uploaded photo, or a generated pixel avatar) for a user row. */
function user_avatar_html(array $u, int $size = 40): string
{
    $px = 'width:' . $size . 'px;height:' . $size . 'px';
    if (!empty($u['avatar'])) {
        return '<span class="au-av" style="' . $px . '"><img src="' . BASE_URL . htmlspecialchars($u['avatar'], ENT_QUOTES) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="au-av" style="' . $px . '">' . pixel_avatar_svg((string)($u['username'] ?? $u['display_name'] ?? 'u'), $size) . '</span>';
}

function is_volunteer(): bool
{
    $u = auth();
    return $u && !empty($u['is_volunteer']);
}
