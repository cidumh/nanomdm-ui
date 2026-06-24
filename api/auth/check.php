<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site_config.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::check();
if (!$session) {
    jsonResponse(401, '未登录或登录已过期');
}

jsonResponse(0, 'ok', [
    'username'  => $session['username'],
    'site_name' => SiteConfig::siteName(),
]);
