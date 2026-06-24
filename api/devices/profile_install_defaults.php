<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/device_profile_service.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();

$serial = trim($_GET['serial_number'] ?? $_GET['serial'] ?? '');
if ($serial === '') {
    jsonResponse(1, '设备序列号不能为空');
}

jsonResponse(0, 'ok', DeviceProfileService::getInstallDefaults($serial));
