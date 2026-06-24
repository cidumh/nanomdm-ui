<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mdm_device.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();

$input = array_merge($_GET, json_decode(file_get_contents('php://input'), true) ?: []);

jsonResponse(0, 'ok', MdmDevice::list($input));
