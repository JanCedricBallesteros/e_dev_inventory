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

    $canRenderLocal = class_exists('QRcode') && extension_loaded('gd') && function_exists('imagepng');
    if ($canRenderLocal) {
        QRcode::png($value, false, QR_ECLEVEL_L, 4);
        exit;
    }

    // Fallback for environments where GD is not enabled (common on fresh XAMPP setups).
    $fallbackUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&format=png&data=' . rawurlencode($value);
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 8,
            'ignore_errors' => true
        )
    ));
    $png = @file_get_contents($fallbackUrl, false, $ctx);
    if ($png !== false && strlen($png) > 0) {
        echo $png;
        exit;
    }

    throw new RuntimeException('Unable to render QR code (GD unavailable and fallback failed).');
} catch (Exception $e) {
    // Log error and return a blank image
    error_log('QR Code generation error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}
