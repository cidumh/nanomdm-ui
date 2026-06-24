<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::check();
if ($session) {
    Auth::logout();
    Logger::system('用户登出', '登出成功', '用户: ' . $session['username'], (int)$session['user_id'], $session['username']);
}

jsonResponse(0, '已退出登录');
