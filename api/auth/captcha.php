<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isInstalled()) {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}

$code = Auth::generateCaptcha();

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$width  = 110;
$height = 40;
$img    = imagecreatetruecolor($width, $height);

$bg     = imagecolorallocate($img, 245, 245, 247);
$colors = [
    imagecolorallocate($img, 0, 113, 227),
    imagecolorallocate($img, 52, 120, 246),
    imagecolorallocate($img, 29, 29, 31),
    imagecolorallocate($img, 134, 134, 139),
];

imagefill($img, 0, 0, $bg);

// 干扰线
for ($i = 0; $i < 6; $i++) {
    $c = $colors[array_rand($colors)];
    imageline(
        $img,
        random_int(0, $width),
        random_int(0, $height),
        random_int(0, $width),
        random_int(0, $height),
        $c
    );
}

// 干扰点
for ($i = 0; $i < 40; $i++) {
    imagesetpixel($img, random_int(0, $width - 1), random_int(0, $height - 1), $colors[array_rand($colors)]);
}

// 验证码文字
$len   = strlen($code);
$slot  = (int) floor($width / ($len + 1));
$font  = 5;

for ($i = 0; $i < $len; $i++) {
    $char = $code[$i];
    $x    = $slot * ($i + 1) - 4;
    $y    = random_int(8, 14);
    $c    = $colors[array_rand($colors)];
    imagestring($img, $font, $x, $y, $char, $c);
}

imagepng($img);
imagedestroy($img);
