<?php
/**
 * MDM 服务器 Webhook 事件解析
 */

require_once __DIR__ . '/api_log.php';

class MdmWebhook
{
    private const EVENT_KEYS = ['acknowledge_event', 'checkin_event'];

    public static function extractEvent(array $payload): ?array
    {
        foreach (self::EVENT_KEYS as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }
        return null;
    }

    public static function parseTopicType(string $topic, string $status = ''): string
    {
        $type = trim($topic);
        if (strpos($type, 'mdm.') === 0) {
            $type = substr($type, 4);
        }
        $status = trim($status);
        if ($status !== '') {
            return $type . ':' . $status;
        }
        return $type;
    }

    public static function parseCreatedAt(?string $iso): string
    {
        if ($iso === null || trim($iso) === '') {
            return date('Y-m-d H:i:s');
        }
        try {
            $dt = new DateTime($iso);
            $dt->setTimezone(new DateTimeZone('Asia/Shanghai'));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * 处理 MDM 推送事件并写入 API 通讯日志
     */
    public static function handle(array $payload, string $rawBody, string $ip): array
    {
        $event = self::extractEvent($payload) ?? [];
        $topic = trim((string) ($payload['topic'] ?? ''));
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $status = trim((string) ($event['status'] ?? ''));
        $udid = trim((string) ($event['udid'] ?? ''));
        $commId = trim((string) ($event['command_uuid'] ?? ''));
        $topicType = self::parseTopicType($topic, $status);

        ApiLog::logReceive([
            'device_udid' => $udid,
            'topic_type'  => $topicType,
            'topic_id'    => $eventId,
            'comm_id'     => $commId,
            'content'     => $rawBody,
            'ip'          => $ip,
            'created_at'  => self::parseCreatedAt($payload['created_at'] ?? null),
        ]);

        $result = [
            'device_udid' => $udid,
            'topic_type'  => $topicType,
            'topic_id'    => $eventId,
            'comm_id'     => $commId,
            'event_key'   => self::detectEventKey($payload),
        ];

        if ($topic === 'mdm.Authenticate' && isset($payload['checkin_event']) && is_array($payload['checkin_event'])) {
            require_once __DIR__ . '/mdm_checkin.php';
            $result['device_register'] = MdmCheckin::handleAuthenticate($payload, $payload['checkin_event'], $ip);
        }

        if ($topic === 'mdm.TokenUpdate' && isset($payload['checkin_event']) && is_array($payload['checkin_event'])) {
            require_once __DIR__ . '/mdm_checkin.php';
            $result['device_token_update'] = MdmCheckin::handleTokenUpdate($payload, $payload['checkin_event'], $ip);
        }

        if ($topic === 'mdm.CheckOut' && isset($payload['checkin_event']) && is_array($payload['checkin_event'])) {
            require_once __DIR__ . '/mdm_checkin.php';
            $result['device_checkout'] = MdmCheckin::handleCheckOut($payload, $payload['checkin_event'], $ip);
        }

        if ($topic === 'mdm.Connect' && isset($payload['acknowledge_event']) && is_array($payload['acknowledge_event'])) {
            require_once __DIR__ . '/mdm_acknowledge.php';
            $result['device_connect'] = MdmAcknowledge::handleConnect($payload, $payload['acknowledge_event'], $ip);
        }

        return $result;
    }

    private static function detectEventKey(array $payload): string
    {
        foreach (self::EVENT_KEYS as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $key;
            }
        }
        return '';
    }
}
