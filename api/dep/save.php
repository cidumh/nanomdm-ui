<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
DepConfig::ensureTables();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$enabled  = !empty($input['dep_enabled']);
$api      = trim($input['dep_api'] ?? '');
$name     = trim($input['dep_api_name'] ?? '');
$username = trim($input['dep_api_username'] ?? '');
$password = $input['dep_api_password'] ?? '';

if ($enabled && $api === '') {
    jsonResponse(1, '请填写 DEP API');
}

try {
    DepConfig::set('dep_enabled', $enabled ? '1' : '0');
    DepConfig::set('dep_api', $api);
    DepConfig::set('dep_api_name', $name);
    DepConfig::set('dep_api_username', $username);

    if ($password !== '') {
        DepConfig::set('dep_api_password', $password);
    } elseif (!$enabled) {
        DepConfig::set('dep_api_password', '');
    }

    DepConfig::set('dep_ssl_verify', !empty($input['dep_ssl_verify']) ? '1' : '0');
    DepConfig::set('dep_configured', DepConfig::isConfigured() ? '1' : '0');

    $detail = $enabled ? 'DEP 已启用' : 'DEP 已关闭';
    Logger::system('保存 DEP 配置', $enabled ? '保存成功' : '已关闭 DEP', $detail, (int)$session['user_id'], $session['username'] ?? '');

    jsonResponse(0, 'DEP 配置已保存', [
        'dep_configured' => DepConfig::isConfigured(),
    ]);
} catch (Exception $e) {
    jsonResponse(1, '保存失败：' . $e->getMessage());
}
