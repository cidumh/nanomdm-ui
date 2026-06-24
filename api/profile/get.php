<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/profile.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();
ProfileConfig::ensureTables();

jsonResponse(0, 'ok', ProfileConfig::getAll());
