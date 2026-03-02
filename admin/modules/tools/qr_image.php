<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in image output

try {
    require_once dirname(__DIR__, 3) . '/call_func/phpqrcode/qrlib.php';

    $value = $_GET['v'] ?? '';
    if (!$value) {
        header('HTTP/1.1 400 Bad Request');
        exit;
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    QRcode::png($value, false, QR_ECLEVEL_L, 4);
} catch (Exception $e) {
    // Log error and return a blank image
    error_log('QR Code generation error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}