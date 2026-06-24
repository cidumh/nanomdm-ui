<?php
/**
 * 应用引导 - 所有入口统一加载
 */

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_FILE', ROOT_PATH . '/config/dsajijiji_ewqhfhds.php');
define('INSTALL_FILE', ROOT_PATH . '/install.php');
define('INSTALL_API_FILE', ROOT_PATH . '/api/install/setup.php');

// 时区
date_default_timezone_set('Asia/Shanghai');

// 会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 判断是否已完成安装
 */
function isInstalled(): bool
{
    return file_exists(CONFIG_FILE);
}

/**
 * 安装入口是否可用（未安装且 install.php 存在）
 */
function canRunInstall(): bool
{
    return !isInstalled() && file_exists(INSTALL_FILE);
}

/**
 * 安装完成后移除安装入口文件
 */
function removeInstallFiles(): bool
{
    $ok = true;
    if (file_exists(INSTALL_FILE)) {
        $ok = @unlink(INSTALL_FILE) && $ok;
    }
    if (file_exists(INSTALL_API_FILE)) {
        $ok = @unlink(INSTALL_API_FILE) && $ok;
    }
    return $ok;
}

/**
 * 加载数据库配置
 */
function loadDbConfig(): ?array
{
    if (!isInstalled()) {
        return null;
    }
    return require CONFIG_FILE;
}

/**
 * JSON 响应
 */
function jsonResponse(int $code, string $msg, $data = null): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取客户端 IP
 */
function clientIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * 获取 User-Agent
 */
function clientAgent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
}
