<?php
// TEMPORARY DIAGNOSTIC — DELETE AFTER DEBUGGING
// Access: yourdomain.com/debug.php
// DELETE THIS FILE after fixing the issue!

$secret = 'mftaraq_debug_2024';
if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    die('403');
}
?><!DOCTYPE html>
<html dir="ltr"><head><meta charset="UTF-8"><title>Debug</title>
<style>body{font-family:monospace;padding:20px;background:#f5f5f5} table{border-collapse:collapse;width:100%} td,th{border:1px solid #ccc;padding:6px 10px;text-align:left} .ok{color:green} .fail{color:red} h2{margin-top:24px}</style>
</head><body>
<h1>Server Diagnostics</h1>
<p style="color:red;font-weight:bold">DELETE THIS FILE after fixing the issue!</p>

<h2>PHP Version</h2>
<p class="<?= version_compare(PHP_VERSION,'7.4.0','>=') ? 'ok' : 'fail' ?>">
  PHP <?= PHP_VERSION ?> <?= version_compare(PHP_VERSION,'8.0.0','>=') ? '(8.0+)' : '(7.4 — upgrade to 8.x in hosting panel)' ?>
</p>

<h2>Required Extensions</h2>
<table>
<?php foreach (['pdo','pdo_sqlite','gd','mbstring','curl','json','session','openssl','intl'] as $ext): ?>
<tr>
  <td><?= $ext ?></td>
  <td class="<?= extension_loaded($ext)?'ok':'fail' ?>"><?= extension_loaded($ext)?'Loaded':'MISSING' ?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>File Permissions</h2>
<table>
<?php
$paths = [
    __DIR__ . '/database'            => 'Database folder (need write)',
    __DIR__ . '/database/muntalaq.sqlite' => 'SQLite file (need write)',
    __DIR__ . '/public/uploads'      => 'Uploads folder (need write)',
    __DIR__ . '/.htaccess'           => '.htaccess (need read)',
    __DIR__ . '/index.php'           => 'index.php (need read)',
];
foreach ($paths as $path => $label):
    $exists = file_exists($path);
    $writable = $exists && is_writable($path);
    $readable = $exists && is_readable($path);
?>
<tr>
  <td><?= $label ?></td>
  <td class="<?= $exists?'ok':'fail' ?>"><?= $exists?'Exists':'Missing' ?></td>
  <td class="<?= $readable?'ok':'fail' ?>"><?= $readable?'Readable':'Not readable' ?></td>
  <td class="<?= $writable?'ok':'' ?>"><?= $writable?'Writable':'— Not writable' ?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>mod_rewrite</h2>
<?php if (function_exists('apache_get_modules')): ?>
  <?php $mods = apache_get_modules(); ?>
  <p class="<?= in_array('mod_rewrite', $mods)?'ok':'fail' ?>">
    mod_rewrite: <?= in_array('mod_rewrite',$mods)?'Enabled':'DISABLED — contact hosting support' ?>
  </p>
<?php else: ?>
  <p>Cannot detect (not Apache or no access). Test by opening any URL like /challenges.</p>
<?php endif; ?>

<h2>URL Info</h2>
<table>
<?php
$info = [
    'REQUEST_URI'   => $_SERVER['REQUEST_URI'] ?? '-',
    'SCRIPT_NAME'   => $_SERVER['SCRIPT_NAME'] ?? '-',
    'HTTP_HOST'     => $_SERVER['HTTP_HOST'] ?? '-',
    'HTTPS'         => $_SERVER['HTTPS'] ?? 'off',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? '-',
    'Script Path'   => __FILE__,
    'Base DIR'      => rtrim(dirname($_SERVER['SCRIPT_NAME']??'/'),'/\\'),
];
foreach ($info as $k=>$v): ?>
<tr><td><?= $k ?></td><td><?= htmlspecialchars((string)$v) ?></td></tr>
<?php endforeach; ?>
</table>

<h2>Database Test</h2>
<?php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/database/muntalaq.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p class="ok">SQLite connected. Tables: ' . implode(', ', $tables) . '</p>';
    $users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo '<p class="ok">Users count: ' . $users . '</p>';
} catch (Exception $e) {
    echo '<p class="fail">DB Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

<h2>GD / Image Test</h2>
<?php
if (extension_loaded('gd')) {
    $info = gd_info();
    echo '<p class="ok">GD loaded. JPEG: '.($info['JPEG Support']?'':'').' PNG: '.($info['PNG Support']?'':'').' FreeType: '.($info['FreeType Support']?'':'').'</p>';
    // Test font
    $fontPath = __DIR__ . '/public/fonts/FreeSerifBold.ttf';
    echo '<p>Font file: ' . (file_exists($fontPath)?'<span class="ok">Found</span>':'<span class="fail">Missing ('.htmlspecialchars($fontPath).')</span>') . '</p>';
} else {
    echo '<p class="fail">GD not loaded — PDF certificates won\'t work. Enable gd in PHP settings.</p>';
}
?>

<p style="margin-top:32px;color:#999">Generated: <?= date('Y-m-d H:i:s') ?> | <strong>Delete this file after use!</strong></p>
</body></html>
