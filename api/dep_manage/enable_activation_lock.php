<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/dep_profile.php';
require_once __DIR__ . '/../../includes/dep_activation_lock.php';
require_once __DIR__ . '/../../includes/mdm_device.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
DepActivationLock::ensureTables();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$serial      = trim($input['serial'] ?? '');
$lostMessage = trim($input['lost_message'] ?? '');

if ($serial === '') {
    jsonResponse(1, '请填写设备序列号');
}
if ($lostMessage === '') {
    jsonResponse(1, '请填写丢失提示内容');
}
if (mb_strlen($lostMessage) > 500) {
    jsonResponse(1, '丢失提示内容不能超过 500 字');
}

$generated = depGenerateActivationLockBypass();
$bypassCode = $generated['bypass_code'];
$escrowKey  = $generated['escrow_key'];

$logDetail = DepActivationLock::formatLogDetail(
    $serial,
    $bypassCode,
    $escrowKey,
    'lost_message=' . $lostMessage
);

$result = DepClient::enableActivationLock($serial, $escrowKey, $lostMessage);

if (!$result['ok']) {
    Logger::system('开启激活锁', '操作失败', $logDetail . ' error=' . $result['msg'], (int)$session['user_id'], $session['username'] ?? '');
    jsonResponse(1, $result['msg']);
}

$data = $result['data'];
$status = strtoupper((string)($data['response_status'] ?? ''));

if ($status !== 'SUCCESS') {
    $responseJson = json_encode($data, JSON_UNESCAPED_UNICODE);
    Logger::system(
        '开启激活锁',
        '操作失败',
        $logDetail . ' response=' . $responseJson,
        (int)$session['user_id'],
        $session['username'] ?? ''
    );
    $msg = $status !== '' ? ('DEP 返回状态：' . $status) : 'DEP 服务器响应异常';
    jsonResponse(1, $msg);
}

try {
    DepActivationLock::save($serial, $bypassCode, $escrowKey, $lostMessage, (int)$session['user_id']);
    MdmDevice::setActivationLockStatus($serial, true);
} catch (Exception $e) {
    Logger::system(
        '开启激活锁',
        '操作失败',
        $logDetail . ' error=' . $e->getMessage(),
        (int)$session['user_id'],
        $session['username'] ?? ''
    );
    jsonResponse(1, '激活锁已开启，但本地绕过码保存失败：' . $e->getMessage());
}

Logger::system(
    '开启激活锁',
    '操作成功',
    DepActivationLock::formatLogDetail($serial, $bypassCode, $escrowKey, 'status=' . $status),
    (int)$session['user_id'],
    $session['username'] ?? ''
);

jsonResponse(0, '设备 ' . $serial . ' 激活锁已成功开启', [
    'serial_number'     => $data['serial_number'] ?? $serial,
    'response_status'   => $status,
]);
