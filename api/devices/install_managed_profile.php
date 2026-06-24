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
$type = trim($input['type'] ?? '');

try {
    switch ($type) {
        case 'dns':
            $result = DeviceProfileService::installDnsProfile($serial, $input);
            break;
        case 'global':
            $result = DeviceProfileService::installGlobalProxyProfile($serial, $input);
            break;
        case 'func':
            $result = DeviceProfileService::installFuncRestrictionProfile($serial, $input);
            break;
        default:
            jsonResponse(1, '未知的配置类型');
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
