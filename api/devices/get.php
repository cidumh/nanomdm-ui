<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mdm_device.php';
require_once __DIR__ . '/../../includes/device_detail.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();

$input = array_merge($_GET, json_decode(file_get_contents('php://input'), true) ?: []);
$serial = trim($input['serial_number'] ?? $input['serial'] ?? '');

if ($serial === '') {
    jsonResponse(1, '设备序列号不能为空');
}

$device = MdmDevice::findBySerial($serial);
if (!$device) {
    jsonResponse(1, '设备不存在');
}

jsonResponse(0, 'ok', DeviceDetail::build($device));
