<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/dep_profile.php';
require_once __DIR__ . '/../../includes/logger.php';

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

$profileUuid = trim($input['profile_uuid'] ?? '');
$serial      = trim($input['serial'] ?? '');

if ($serial === '') {
    jsonResponse(1, '请填写设备序列号');
}
if ($profileUuid === '') {
    jsonResponse(1, '设备未绑定配置文件');
}

$result = DepClient::removeProfileDevices($profileUuid, [$serial]);

if (!$result['ok']) {
    Logger::system('移除配置', '操作失败', $serial . ' error=' . $result['msg'], (int)$session['user_id'], $session['username'] ?? '');
    jsonResponse(1, $result['msg']);
}

$data = $result['data'];
$status = $data['devices'][$serial] ?? '';

Logger::system('移除配置', '操作成功', $serial . ' (' . $status . ')', (int)$session['user_id'], $session['username'] ?? '');
jsonResponse(0, '移除配置成功', ['status' => $status]);
