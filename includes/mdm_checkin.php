<?php
/**
 * MDM checkin 事件处理
 */

require_once __DIR__ . '/mdm_plist.php';
require_once __DIR__ . '/mdm_device.php';
require_once __DIR__ . '/device_log.php';
require_once __DIR__ . '/device_per_log.php';

class MdmCheckin
{
    public const TOPIC_AUTHENTICATE = 'mdm.Authenticate';
    public const TOPIC_TOKEN_UPDATE = 'mdm.TokenUpdate';
    public const TOPIC_CHECK_OUT = 'mdm.CheckOut';

    /**
     * 设备注册 Authenticate
     */
    public static function handleAuthenticate(array $payload, array $event, string $ip): array
    {
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $udid = trim((string) ($event['udid'] ?? ''));
        $plist = MdmPlist::decodeRawPayload($event['raw_payload'] ?? '');
        $serial = trim((string) ($plist['SerialNumber'] ?? ''));

        if ($serial === '') {
            throw new InvalidArgumentException('无法解析设备序列号');
        }
        if ($udid === '') {
            $udid = trim((string) ($plist['UDID'] ?? ''));
        }
        if ($udid === '') {
            throw new InvalidArgumentException('无法解析设备 UDID');
        }

        $registeredAt = MdmPlist::parseCreatedAt($payload['created_at'] ?? null);

        $deviceResult = MdmDevice::registerAuthenticate([
            'serial_number' => $serial,
            'udid'          => $udid,
            'device_type'   => $plist['ProductName'] ?? '',
            'device_model'  => $plist['OSVersion'] ?? '',
            'topic'         => $plist['Topic'] ?? '',
            'build_version' => $plist['BuildVersion'] ?? '',
            'imei'          => $plist['IMEI'] ?? '',
            'os_version'    => $plist['OSVersion'] ?? '',
            'event_id'      => $eventId,
            'registered_at' => $registeredAt,
        ]);

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => '',
            'serial_number'  => $serial,
            'operation_type' => '设备注册',
            'comm_id'        => $eventId,
            'push_id'        => '',
            'command_type'   => 'Authenticate',
            'status'         => '完成',
            'confirmed_at'   => $registeredAt,
            'created_at'     => $registeredAt,
            'ip'             => $ip,
        ]);

        require_once __DIR__ . '/policy_register.php';
        $policyResults = PolicyRegister::applyOnRegister([
            'serial_number' => $serial,
            'device_udid'   => $udid,
            'event_id'      => $eventId,
            'event_time'    => $registeredAt,
            'ip'            => $ip,
        ]);

        return array_merge($deviceResult, [
            'event_id'      => $eventId,
            'command_type'  => 'Authenticate',
            'registered_at' => $registeredAt,
            'policy'        => $policyResults,
        ]);
    }

    /**
     * 设备令牌更新 TokenUpdate
     */
    public static function handleTokenUpdate(array $payload, array $event, string $ip): array
    {
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $udid = trim((string) ($event['udid'] ?? ''));
        $plist = MdmPlist::decodeRawPayload($event['raw_payload'] ?? '');

        if ($udid === '') {
            $udid = trim((string) ($plist['UDID'] ?? ''));
        }
        if ($udid === '') {
            throw new InvalidArgumentException('无法解析设备 UDID');
        }

        $token = trim((string) ($plist['UnlockToken'] ?? ''));
        if ($token === '') {
            throw new InvalidArgumentException('无法解析设备 Token');
        }

        $eventTime = MdmPlist::parseCreatedAt($payload['created_at'] ?? null);
        $tokenUpdateTally = (int) ($event['token_update_tally'] ?? 0);

        $deviceResult = MdmDevice::updateTokenUpdate([
            'udid'               => $udid,
            'topic'              => $plist['Topic'] ?? '',
            'token'              => $token,
            'token_update_count' => $tokenUpdateTally,
            'event_id'           => $eventId,
            'event_time'         => $eventTime,
        ]);

        $serial = $deviceResult['serial_number'];
        $existing = MdmDevice::findBySerial($serial);

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $existing['remark'] ?? '',
            'serial_number'  => $serial,
            'operation_type' => '令牌更新',
            'comm_id'        => $eventId,
            'push_id'        => '',
            'command_type'   => 'TokenUpdate',
            'status'         => '完成',
            'confirmed_at'   => $eventTime,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);

        return array_merge($deviceResult, [
            'event_id'           => $eventId,
            'command_type'       => 'TokenUpdate',
            'token_update_tally' => $tokenUpdateTally,
            'event_time'         => $eventTime,
        ]);
    }

    /**
     * 设备退出监管 CheckOut
     */
    public static function handleCheckOut(array $payload, array $event, string $ip): array
    {
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $udid = trim((string) ($event['udid'] ?? ''));

        if ($udid === '') {
            throw new InvalidArgumentException('无法解析设备 UDID');
        }

        $eventTime = MdmPlist::parseCreatedAt($payload['created_at'] ?? null);

        $deviceResult = MdmDevice::updateCheckOut([
            'udid' => $udid,
        ]);

        $serial = $deviceResult['serial_number'];
        $existing = MdmDevice::findBySerial($serial);

        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $existing['remark'] ?? '',
            'serial_number'  => $serial,
            'operation_type' => '设备退出监管',
            'comm_id'        => $eventId,
            'push_id'        => '',
            'command_type'   => 'CheckOut',
            'status'         => '完成',
            'confirmed_at'   => $eventTime,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);

        return array_merge($deviceResult, [
            'event_id'     => $eventId,
            'command_type' => 'CheckOut',
            'event_time'   => $eventTime,
        ]);
    }
}
