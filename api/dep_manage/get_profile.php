<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/dep_profile.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
DepProfile::ensureTables();

$profileUuid = trim($_GET['profile_uuid'] ?? '');
if ($profileUuid === '') {
    jsonResponse(1, '缺少 profile_uuid 参数');
}

$result = DepClient::getProfile($profileUuid);

if (!$result['ok']) {
    jsonResponse(1, $result['msg']);
}

jsonResponse(0, 'ok', $result['data']);
