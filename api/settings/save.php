<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site_config.php';
require_once __DIR__ . '/../../includes/config_helper.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
$userId = (int)$session['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$siteName   = trim($input['site_name'] ?? '');
$username   = trim($input['admin_user'] ?? '');
$newPass    = $input['admin_pass'] ?? '';
$newPass2   = $input['admin_pass2'] ?? '';
$footerIcpText = trim($input['footer_icp_text'] ?? '');
$footerIcpUrl  = trim($input['footer_icp_url'] ?? '');
$footerGaText  = trim($input['footer_ga_text'] ?? '');
$footerGaUrl   = trim($input['footer_ga_url'] ?? '');
$dbHost     = trim($input['db_host'] ?? '');
$dbPort     = (int)($input['db_port'] ?? 3306);
$dbName     = trim($input['db_name'] ?? '');
$dbUser     = trim($input['db_user'] ?? '');
$dbPass     = $input['db_pass'] ?? '';

if ($siteName === '') {
    jsonResponse(1, '请填写面板名称');
}
if ($username === '') {
    jsonResponse(1, '请填写管理员用户名');
}
if ($dbHost === '' || $dbName === '' || $dbUser === '') {
    jsonResponse(1, '请完整填写数据库连接信息');
}
if ($footerIcpText !== '' && $footerIcpUrl === '') {
    jsonResponse(1, '填写 ICP 备案号时请同时填写查询链接');
}
if ($footerGaText !== '' && $footerGaUrl === '') {
    jsonResponse(1, '填写公安备案号时请同时填写查询链接');
}

$oldCfg = loadDbConfig();
$finalDbPass = ($dbPass !== '') ? $dbPass : ($oldCfg['pass'] ?? '');

if ($newPass !== '') {
    if (strlen($newPass) < 6) {
        jsonResponse(1, '管理员密码至少6位');
    }
    if ($newPass !== $newPass2) {
        jsonResponse(1, '两次输入的管理员密码不一致');
    }
}

// 测试数据库连接
if (!testDbConnection($dbHost, $dbPort, $dbName, $dbUser, $finalDbPass)) {
    jsonResponse(1, '数据库连接失败，请检查连接配置');
}

$user = DB::fetchOne('SELECT id, username FROM users WHERE id = ? LIMIT 1', [$userId]);
if (!$user) {
    jsonResponse(1, '用户不存在');
}

$usernameChanged = ($username !== $user['username']);
$passwordChanged = ($newPass !== '');
$needRelogin = $usernameChanged || $passwordChanged;

// 检查用户名是否被占用
if ($usernameChanged) {
    $dup = DB::fetchOne('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1', [$username, $userId]);
    if ($dup) {
        jsonResponse(1, '该用户名已被使用');
    }
}

try {
    SiteConfig::set('site_name', $siteName);
    SiteConfig::set('footer_icp_text', $footerIcpText);
    SiteConfig::set('footer_icp_url', $footerIcpUrl);
    SiteConfig::set('footer_ga_text', $footerGaText);
    SiteConfig::set('footer_ga_url', $footerGaUrl);

    if ($usernameChanged) {
        DB::execute('UPDATE users SET username = ?, updated_at = NOW() WHERE id = ?', [$username, $userId]);
    }
    if ($passwordChanged) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        DB::execute('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?', [$hash, $userId]);
    }

    $dbChanged = (
        $dbHost !== ($oldCfg['host'] ?? '') ||
        $dbPort !== (int)($oldCfg['port'] ?? 3306) ||
        $dbName !== ($oldCfg['dbname'] ?? '') ||
        $dbUser !== ($oldCfg['user'] ?? '') ||
        $finalDbPass !== ($oldCfg['pass'] ?? '')
    );

    if ($dbChanged) {
        if (!writeDbConfigFile($dbHost, $dbPort, $dbName, $dbUser, $finalDbPass)) {
            jsonResponse(1, '数据库配置文件写入失败');
        }
        DB::reset();
    }

    $detail = '面板名称已更新';
    if ($usernameChanged) {
        $detail .= '，用户名已修改';
    }
    if ($passwordChanged) {
        $detail .= '，密码已修改';
    }
    if ($dbChanged) {
        $detail .= '，数据库配置已更新';
    }
    Logger::system('修改系统设置', '保存成功', $detail, $userId, $session['username'] ?? $username);

    if ($needRelogin) {
        Auth::revokeUserSessions($userId);
        Auth::logout();
        jsonResponse(0, '账户信息已更新，请重新登录', ['relogin' => true]);
    }

    jsonResponse(0, '设置已保存', ['relogin' => false]);
} catch (Exception $e) {
    jsonResponse(1, '保存失败：' . $e->getMessage());
}
