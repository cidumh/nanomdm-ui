<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mdm_device.php';
require_once __DIR__ . '/../../includes/api_log.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();

MdmDevice::ensureTables();
ApiLog::ensureTables();

$deviceStats = MdmDevice::dashboardStats();

jsonResponse(0, 'ok', [
    'device_total' => $deviceStats['device_total'],
    'today_active' => $deviceStats['today_active'],
    'today_comm'   => ApiLog::countToday(),
]);
