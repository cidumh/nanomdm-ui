<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_once __DIR__ . '/../../includes/db.php';

require_once __DIR__ . '/../../includes/auth.php';

require_once __DIR__ . '/../../includes/mdm.php';

require_once __DIR__ . '/../../includes/mdm_client.php';

require_once __DIR__ . '/../../includes/api_log.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonResponse(405, '请求方式不允许');

}



if (!isInstalled()) {

    jsonResponse(503, '系统尚未安装');

}



$session = Auth::requireLogin();

MdmConfig::ensureTables();



if (!MdmConfig::isConfigured()) {

    jsonResponse(1, '请先在 MDM 配置中填写 MDM Server URL');

}



$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {

    $input = $_POST;

}



$udid = trim($input['device_udid'] ?? '');

$commandContent = $input['command_content'] ?? '';



if ($udid === '') {

    jsonResponse(1, '请填写设备 UDID');

}

if (trim($commandContent) === '') {

    jsonResponse(1, '请填写指令内容');

}



$result = MdmClient::enqueueCommand($udid, $commandContent);

$responseForLog = $result['raw_response'] ?? ['msg' => $result['msg'] ?? ''];
if (is_array($responseForLog)) {
    $responseContent = json_encode($responseForLog, JSON_UNESCAPED_UNICODE);
} else {
    $responseContent = (string) $responseForLog;
}

$commId = $result['ok']
    ? ($result['command_uuid'] ?? ($result['replaced_command_uuid'] ?? ''))
    : $responseContent;

ApiLog::logSendCommand([
    'device_udid' => $udid,
    'topic_type'  => '自定义',
    'topic_id'    => '',
    'comm_id'     => $commId,
    'push_id'     => is_scalar($result['push_result'] ?? '') ? (string) ($result['push_result'] ?? '') : json_encode($result['push_result'], JSON_UNESCAPED_UNICODE),
    'content'     => $result['sent_content'] ?? $commandContent,
    'ip'          => clientIp(),
    'transfer'    => '发送',
]);



if ($result['ok']) {

    jsonResponse(0, $result['msg'], [

        'push_result'             => $result['push_result'] ?? '',

        'command_uuid'            => $result['command_uuid'] ?? '',

        'request_type'            => $result['request_type'] ?? '',

        'replaced_command_uuid'   => $result['replaced_command_uuid'] ?? '',

        'request_url'             => $result['request_url'] ?? '',

        'raw_response'            => $result['raw_response'] ?? null,

    ]);

}



jsonResponse(1, $result['msg'], [

    'command_uuid'          => $result['command_uuid'] ?? '',

    'request_type'          => $result['request_type'] ?? '',

    'error_type'            => $result['error_type'] ?? '',

    'replaced_command_uuid' => $result['replaced_command_uuid'] ?? '',

    'request_url'           => $result['request_url'] ?? '',

    'raw_response'          => $result['raw_response'] ?? null,

]);


