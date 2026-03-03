<?php
// Simple thumbnail generator for category images
require_once dirname(__DIR__, 3) . '/config/config.php';

$file = isset($_GET['f']) ? basename((string)$_GET['f']) : '';
$size = isset($_GET['s']) ? (int)$_GET['s'] : 100;
if ($size <= 0) $size = 100;
if ($size > 300) $size = 300;

$baseDir = dirname(__DIR__, 3) . '/upload/category/';
$path = $file ? $baseDir . $file : '';

if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit();
}

$info = getimagesize($path);
if ($info === false) {
    http_response_code(415);
    exit();
}

[$w, $h] = $info;
$mime = $info['mime'] ?? '';
if ($w <= 0 || $h <= 0) {
    http_response_code(415);
    exit();
}

$canResize = function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled');
$loader = null;
$saver = null;

switch ($mime) {
    case 'image/jpeg':
        $loader = 'imagecreatefromjpeg';
        $saver = 'imagejpeg';
        break;
    case 'image/png':
        $loader = 'imagecreatefrompng';
        $saver = 'imagepng';
        break;
    case 'image/gif':
        $loader = 'imagecreatefromgif';
        $saver = 'imagegif';
        break;
    case 'image/webp':
        $loader = 'imagecreatefromwebp';
        $saver = 'imagewebp';
        break;
    default:
        http_response_code(415);
        exit();
}

// Fallback: if GD or specific codec is unavailable, return the original image.
if (
    !$canResize ||
    !function_exists($loader) ||
    !function_exists($saver)
) {
    header('Cache-Control: public, max-age=86400');
    header('Content-Type: ' . $mime);
    readfile($path);
    exit();
}

$scale = min($size / $w, $size / $h, 1);
$newW = (int)floor($w * $scale);
$newH = (int)floor($h * $scale);
$src = $loader($path);

if (!$src) {
    http_response_code(500);
    exit();
}

$dst = imagecreatetruecolor($newW, $newH);
if ($mime === 'image/png' || $mime === 'image/gif') {
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
}

imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

header('Cache-Control: public, max-age=86400');
header('Content-Type: ' . $mime);

switch ($mime) {
    case 'image/jpeg':
        $saver($dst, null, 80);
        break;
    case 'image/png':
        $saver($dst, null, 6);
        break;
    case 'image/gif':
        $saver($dst);
        break;
    case 'image/webp':
        $saver($dst, null, 80);
        break;
}

imagedestroy($src);
imagedestroy($dst);
