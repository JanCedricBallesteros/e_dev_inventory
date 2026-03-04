<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/call_func/phpqrcode/qrlib.php';

$val = trim((string)($_GET['v'] ?? ''));

if ($val === '') {
    http_response_code(400);
    exit('Missing v');
}

header('Content-Type: image/png');
QRcode::png($val, false, QR_ECLEVEL_H, 6, 2);
