<?php
http_response_code(503);
$code = '503'; $icon = ''; $title = 'الخدمة غير متاحة'; $msg = 'الموقع قيد الصيانة مؤقتا. سنعود قريبا إن شاء الله.';
require __DIR__ . '/error_template.php';
