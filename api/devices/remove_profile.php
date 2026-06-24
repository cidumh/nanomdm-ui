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
$identifier = trim($input['identifier'] ?? '');
$source = trim($input['source'] ?? 'list');

try {
    if ($source === 'manual') {
        $result = DeviceProfileService::removeProfileByInput($serial, $identifier);
    } else {
        $result = DeviceProfileService::removeProfile($serial, $identifier);
    }
    if (!$result['ok']) {
        jsonResponse(1, $result['msg']);
    }
    jsonResponse(0, $result['msg'], $result);
} catch (InvalidArgumentException $e) {
    jsonResponse(1, $e->getMessage());
} catch (Exception $e) {
    jsonResponse(1, '发送失败：' . $e->getMessage());
}
