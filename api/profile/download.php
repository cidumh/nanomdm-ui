<?php
/**
 * 公开下载 MDM 描述文件（无需登录，供设备安装）
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/profile.php';
require_once __DIR__ . '/../../includes/profile_builder.php';

if (!isInstalled()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo '系统尚未安装';
    exit;
}

ProfileConfig::ensureTables();
$config = ProfileConfig::getAll();

if (!ProfileBuilder::isReady($config)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo ProfileBuilder::readinessMessage($config) ?: '描述文件配置不完整';
    exit;
}

try {
    $xml = ProfileBuilder::build($config);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit;
}

header('Content-Type: application/x-apple-aspen-config');
header('Content-Disposition: attachment; filename=mdm.mobileconfig');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo $xml;
exit;
