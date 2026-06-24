<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/device_per_log.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();

$input = array_merge($_GET, json_decode(file_get_contents('php://input'), true) ?: []);
$serial = trim($input['serial_number'] ?? $input['serial'] ?? '');

if ($serial === '') {
    jsonResponse(1, '设备序列号不能为空');
}

try {
    jsonResponse(0, 'ok', DevicePerLog::list($serial, $input));
} catch (InvalidArgumentException $e) {
    jsonResponse(1, $e->getMessage());
} catch (Exception $e) {
    jsonResponse(1, '加载日志失败：' . $e->getMessage());
}
