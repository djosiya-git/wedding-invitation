<?php
require __DIR__.'/lib.php';

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '' || !preg_match('/^[\x20-\x7E]{1,120}$/', $code)) {
    http_response_code(400);
    exit('Kode barcode tidak valid');
}

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

echo code128_svg($code);
