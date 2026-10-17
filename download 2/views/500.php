<?php
http_response_code(500);
$code = '500'; $icon = ''; $title = 'خطأ في الخادم'; $msg = 'حدث خطأ غير متوقع في الخادم. يرجى المحاولة مرة أخرى لاحقا.';
require __DIR__ . '/error_template.php';
