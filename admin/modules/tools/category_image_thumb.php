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

$scale = min($size / $w, $size / $h, 1);
$newW = (int)floor($w * $scale);
$newH = (int)floor($h * $scale);

switch ($mime) {
    case 'image/jpeg':
        $src = imagecreatefromjpeg($path);
        break;
    case 'image/png':
        $src = imagecreatefrompng($path);
        break;
    case 'image/gif':
        $src = imagecreatefromgif($path);
        break;
    case 'image/webp':
        if (function_exists('imagecreatefromwebp')) {
            $src = imagecreatefromwebp($path);
            break;
        }
        http_response_code(415);
        exit();
    default:
        http_response_code(415);
        exit();
}

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
        imagejpeg($dst, null, 80);
        break;
    case 'image/png':
        imagepng($dst, null, 6);
        break;
    case 'image/gif':
        imagegif($dst);
        break;
    case 'image/webp':
        if (function_exists('imagewebp')) {
            imagewebp($dst, null, 80);
        }
        break;
}

imagedestroy($src);
imagedestroy($dst);
