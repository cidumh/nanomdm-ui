<?php
/**
 * API 公共入口
 */

require_once __DIR__ . '/../includes/bootstrap.php';

function apiInit(): void
{
    if (!isInstalled()) {
        jsonResponse(503, '系统尚未安装');
    }
    require_once __DIR__ . '/../includes/db.php';
}
