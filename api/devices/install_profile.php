<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/device_profile_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$serial = trim($input['serial_number'] ?? '');
$profileBase64 = trim($input['profile_base64'] ?? '');
$profileFilename = trim($input['profile_filename'] ?? '');

try {
    if ($profileBase64 === '') {
        jsonResponse(1, '请选择配置文件');
    }
    $profileContent = base64_decode($profileBase64, true);
    if ($profileContent === false || $profileContent === '') {
        jsonResponse(1, '配置文件内容无效');
    }

    $result = DeviceProfileService::installProfileFile($serial, $profileContent, $profileFilename);
    if (!$result['ok']) {
        jsonResponse(1, $result['msg']);
    }
    jsonResponse(0, $result['msg'], $result);
} catch (InvalidArgumentException $e) {
    jsonResponse(1, $e->getMessage());
} catch (Exception $e) {
    jsonResponse(1, '发送失败：' . $e->getMessage());
}
