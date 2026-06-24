<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mdm.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
MdmConfig::ensureTables();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$serverUrl = trim($input['mdm_server_url'] ?? '');
$username  = trim($input['mdm_api_username'] ?? '');
$password  = $input['mdm_api_password'] ?? '';

if ($serverUrl === '') {
    jsonResponse(1, '请填写 MDM Server URL');
}
if (!mdmIsUrl($serverUrl)) {
    jsonResponse(1, 'MDM Server URL 格式不正确');
}

try {
    MdmConfig::set('mdm_server_url', $serverUrl);
    MdmConfig::set('mdm_api_username', $username);

    if ($password !== '') {
        MdmConfig::set('mdm_api_password', $password);
    }

    Logger::system('保存 MDM 配置', '保存成功', $serverUrl, (int)$session['user_id'], $session['username'] ?? '');

    jsonResponse(0, 'MDM 配置已保存', [
        'mdm_configured' => MdmConfig::isConfigured(),
        'has_password'   => MdmConfig::get('mdm_api_password') !== '',
    ]);
} catch (Exception $e) {
    jsonResponse(1, '保存失败：' . $e->getMessage());
}
