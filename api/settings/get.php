<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site_config.php';
require_once __DIR__ . '/../../includes/config_helper.php';

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
SiteConfig::ensureFooterDefaults();
$dbCfg = loadDbConfig();

jsonResponse(0, 'ok', [
    'site_name' => SiteConfig::siteName(),
    'username'  => $session['username'],
    'footer_icp_text' => SiteConfig::footerIcpText(),
    'footer_icp_url'  => SiteConfig::footerIcpUrl(),
    'footer_ga_text'  => SiteConfig::footerGaText(),
    'footer_ga_url'   => SiteConfig::footerGaUrl(),
    'db_host'   => $dbCfg['host'] ?? '',
    'db_port'   => $dbCfg['port'] ?? 3306,
    'db_name'   => $dbCfg['dbname'] ?? '',
    'db_user'   => $dbCfg['user'] ?? '',
]);
