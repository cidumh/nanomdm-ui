<?php
/**
 * NanoMDM API 客户端
 */

require_once __DIR__ . '/mdm.php';

class MdmClient
{
    public static function enqueueCommand(string $udid, string $commandContent): array
    {
        if (!MdmConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 MDM 配置中填写 MDM Server URL'];
        }

        $udid = trim($udid);
        if ($udid === '') {
            return ['ok' => false, 'msg' => '请填写设备 UDID'];
        }
        if (trim($commandContent) === '') {
            return ['ok' => false, 'msg' => '请填写指令内容'];
        }

        $replaced = mdmReplaceCommandUuid($commandContent);
        $url = MdmConfig::serverUrlBase() . '/v1/enqueue/' . rawurlencode($udid);

        $result = self::request('POST', $url, $replaced['content']);
        $result['request_url'] = $url;
        $result['sent_content'] = $replaced['content'];
        $result['replaced_command_uuid'] = $replaced['command_uuid'];

        if (!$result['ok']) {
            return $result;
        }

        $parsed = self::parseEnqueueResponse($result['data'], $udid);
        $parsed['request_url'] = $url;
        $parsed['sent_content'] = $replaced['content'];
        $parsed['replaced_command_uuid'] = $replaced['command_uuid'];
        $parsed['raw_response'] = $result['data'];

        return $parsed;
    }

    public static function pushDevice(string $udid): array
    {
        if (!MdmConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 MDM 配置中填写 MDM Server URL'];
        }

        $udid = trim($udid);
        if ($udid === '') {
            return ['ok' => false, 'msg' => '请填写设备 UDID'];
        }

        $url = MdmConfig::serverUrlBase() . '/v1/push/' . rawurlencode($udid);
        $result = self::request('POST', $url, '', null);
        $result['request_url'] = $url;

        if (!$result['ok']) {
            return $result;
        }

        $parsed = self::parsePushResponse($result['data'], $udid);
        $parsed['request_url'] = $url;
        $parsed['raw_response'] = $result['data'];

        return $parsed;
    }

    private static function parsePushResponse(array $data, string $udid): array
    {
        $status = $data['status'][$udid] ?? null;
        if (!is_array($status)) {
            return [
                'ok'         => false,
                'msg'        => '响应中未找到该设备的状态信息',
                'error_type' => 'missing_status',
            ];
        }

        if (!empty($status['push_result'])) {
            return [
                'ok'          => true,
                'msg'         => '设备连接成功',
                'push_result' => $status['push_result'],
            ];
        }

        if (!empty($status['push_error'])) {
            return [
                'ok'         => false,
                'msg'        => $status['push_error'],
                'error_type' => 'push_error',
            ];
        }

        return [
            'ok'         => false,
            'msg'        => 'MDM 服务器返回未知状态',
            'error_type' => 'unknown',
        ];
    }

    private static function parseEnqueueResponse(array $data, string $udid): array
    {
        $commandUuid = $data['command_uuid'] ?? '';
        $requestType = $data['request_type'] ?? '';

        if (!empty($data['command_error'])) {
            return [
                'ok'           => false,
                'msg'          => $data['command_error'],
                'command_uuid' => $commandUuid,
                'request_type' => $requestType,
                'error_type'   => 'command_error',
            ];
        }

        $status = $data['status'][$udid] ?? null;
        if (!is_array($status)) {
            return [
                'ok'           => false,
                'msg'          => '响应中未找到该设备的状态信息',
                'command_uuid' => $commandUuid,
                'request_type' => $requestType,
                'error_type'   => 'missing_status',
            ];
        }

        if (!empty($status['push_result'])) {
            return [
                'ok'           => true,
                'msg'          => '指令推送成功',
                'push_result'  => $status['push_result'],
                'command_uuid' => $commandUuid,
                'request_type' => $requestType,
            ];
        }

        if (!empty($status['push_error'])) {
            return [
                'ok'           => false,
                'msg'          => $status['push_error'],
                'command_uuid' => $commandUuid,
                'request_type' => $requestType,
                'error_type'   => 'push_error',
            ];
        }

        return [
            'ok'           => false,
            'msg'          => 'MDM 服务器返回未知状态',
            'command_uuid' => $commandUuid,
            'request_type' => $requestType,
            'error_type'   => 'unknown',
        ];
    }

    private static function request(string $method, string $url, string $body, ?string $contentType = 'application/xml; charset=UTF-8'): array
    {
        $user = MdmConfig::get('mdm_api_username');
        $pass = MdmConfig::get('mdm_api_password');
        $method = strtoupper($method);

        $headers = [];
        if ($contentType !== null && $contentType !== '') {
            $headers[] = 'Content-Type: ' . $contentType;
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
        ];
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        if ($user !== '' || $pass !== '') {
            $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $opts[CURLOPT_USERPWD]  = $user . ':' . $pass;
        }

        curl_setopt_array($ch, $opts);
        self::applyCurlSsl($ch);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'msg' => 'MDM 请求失败：' . $error];
        }

        $data = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = is_array($data)
                ? ($data['command_error'] ?? $data['error'] ?? $data['message'] ?? $response)
                : $response;
            return ['ok' => false, 'msg' => 'MDM 服务器返回错误（HTTP ' . $httpCode . '）：' . $msg];
        }

        if (!is_array($data)) {
            return ['ok' => false, 'msg' => 'MDM 服务器响应格式异常'];
        }

        return ['ok' => true, 'data' => $data];
    }

    private static function applyCurlSsl($ch): void
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
}
