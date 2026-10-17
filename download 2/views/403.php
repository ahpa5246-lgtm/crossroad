<?php
http_response_code(403);
$code = '403'; $icon = ''; $title = 'غير مصرح بالوصول'; $msg = 'ليس لديك صلاحية للوصول إلى هذه الصفحة.';
require __DIR__ . '/error_template.php';
