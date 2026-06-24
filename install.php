<?php
/**
 * 独立安装入口 - 仅首次部署时使用，安装完成后请删除此文件
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (isInstalled()) {
    include __DIR__ . '/includes/install_blocked.php';
    exit;
}

include __DIR__ . '/includes/install_page.php';
