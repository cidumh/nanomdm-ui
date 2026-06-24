<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dep.php';
require_once __DIR__ . '/../../includes/dep_profile.php';
require_once __DIR__ . '/../../includes/dep_activation_lock.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

Auth::requireLogin();
DepProfile::ensureTables();
DepActivationLock::ensureTables();

jsonResponse(0, 'ok', [
    'dep_enabled'    => DepConfig::isConfigured(),
    'skip_defaults'  => DepProfile::defaultSkipSetup(),
    'skip_options'   => DepProfile::skipSetupOptions(),
]);
