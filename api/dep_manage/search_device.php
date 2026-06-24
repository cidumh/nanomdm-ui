<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/dep_profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$serial = trim($input['serial'] ?? '');
if ($serial === '') {
    jsonResponse(1, '请填写设备序列号');
}

$result = DepClient::searchDevices([$serial]);

if (!$result['ok']) {
    jsonResponse(1, $result['msg']);
}

$devices = depParseDevicesResponse($result['data']);

if (empty($devices)) {
    jsonResponse(1, '未找到该设备');
}

jsonResponse(0, 'ok', ['devices' => $devices]);
