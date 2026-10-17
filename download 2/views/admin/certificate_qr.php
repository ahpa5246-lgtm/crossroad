<?php
// This page is kept for backwards compatibility - just redirect to PDF
header('Location: ' . BASE_URL . '/certificates/download-pdf?token=' . urlencode($token ?? ''));
exit;
