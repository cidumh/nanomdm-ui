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

$cursor = null;
if (isset($input['cursor']) && trim((string)$input['cursor']) !== '') {
    $cursor = trim((string)$input['cursor']);
}
$limit  = (int)($input['limit'] ?? 100);
if ($limit <= 0 || $limit > 100) {
    $limit = 100;
}

$result = DepClient::listDevices($cursor, $limit);

if (!$result['ok']) {
    jsonResponse(1, $result['msg']);
}

$data = $result['data'];
$devices = depParseDevicesResponse($data);

jsonResponse(0, 'ok', [
    'devices'        => $devices,
    'more_to_follow' => !empty($data['more_to_follow']),
    'cursor'         => $data['cursor'] ?? '',
    'fetched_until'  => depFormatBeijingTime($data['fetched_until'] ?? ''),
]);
