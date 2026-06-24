<?php
/**
 * 接收 MDM 服务器转发的设备通讯事件（无需登录）
 * 请求方式：POST，Content-Type: application/json
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/mdm_webhook.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || trim($rawBody) === '') {
    jsonResponse(400, '请求体不能为空');
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    jsonResponse(400, 'JSON 格式无效');
}

try {
    $result = MdmWebhook::handle($payload, $rawBody, clientIp());
    jsonResponse(0, 'ok', $result);
} catch (InvalidArgumentException $e) {
    jsonResponse(400, $e->getMessage());
} catch (Exception $e) {
    jsonResponse(500, '处理失败');
}
