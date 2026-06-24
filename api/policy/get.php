<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/policy.php';
require_once __DIR__ . '/../../includes/dep.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();
PolicyConfig::ensureTables();

$data = PolicyConfig::getAll();
$data['dep_configured'] = DepConfig::isConfigured();

jsonResponse(0, 'ok', $data);
