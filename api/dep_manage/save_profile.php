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
DepProfile::ensureTables();

if (!DepConfig::isConfigured()) {
    jsonResponse(1, '请先在 DEP 配置中开启 DEP 开关并填写 DEP API');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$profileName = trim($input['profile_name'] ?? '');
$mdmUrl      = trim($input['mdm_url'] ?? '');
$webUrl      = trim($input['web_url'] ?? '');
$department  = trim($input['department'] ?? '');

if ($profileName === '') {
    jsonResponse(1, '请填写配置文件名称');
}
if ($mdmUrl === '') {
    jsonResponse(1, '请填写 MDM 服务器地址');
}
if ($webUrl === '') {
    jsonResponse(1, '请填写 WEB URL 地址');
}
if ($department === '') {
    jsonResponse(1, '请填写组织名称');
}

$payload = DepClient::buildPayload($input);
$result  = DepClient::submitProfile($payload);

if (!$result['ok']) {
    Logger::system('保存 DEP 配置文件', '保存失败', $result['msg'], (int)$session['user_id'], $session['username'] ?? '');
    jsonResponse(1, $result['msg']);
}

$profileUuid = $result['profile_uuid'];
$saveData = [
    'profile_name'           => $profileName,
    'mdm_url'                => $mdmUrl,
    'web_url'                => $webUrl,
    'department'             => $department,
    'org_magic'              => trim($input['org_magic'] ?? ''),
    'is_supervised'          => !empty($input['is_supervised']),
    'await_device_configured'  => !empty($input['await_device_configured']),
    'is_mandatory'           => !empty($input['is_mandatory']),
    'is_mdm_removable'       => !empty($input['is_mdm_removable']),
    'language'               => trim($input['language'] ?? 'zh') ?: 'zh',
    'region'                 => trim($input['region'] ?? 'CN') ?: 'CN',
    'support_email'          => trim($input['support_email'] ?? ''),
    'support_phone'          => trim($input['support_phone'] ?? ''),
    'skip_setup_enabled'     => !empty($input['skip_setup_enabled']),
    'skip_setup_items'       => $payload['skip_setup_items'],
    'devices'                => $payload['devices'],
    'payload'                => $payload,
];

try {
    DepProfile::saveFromResponse($profileUuid, $saveData, (int)$session['user_id']);
    Logger::system('保存 DEP 配置文件', '保存成功', 'profile_uuid: ' . $profileUuid . '，名称: ' . $profileName, (int)$session['user_id'], $session['username'] ?? '');

    jsonResponse(0, '配置已保存到 DEP 服务器', [
        'profile_uuid' => $profileUuid,
    ]);
} catch (Exception $e) {
    jsonResponse(1, '本地保存失败：' . $e->getMessage());
}
