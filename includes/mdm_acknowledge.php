<?php
/**
 * MDM acknowledge 事件处理（Connect 设备连接 / 执行响应）
 */

require_once __DIR__ . '/mdm_plist.php';
require_once __DIR__ . '/mdm_device.php';
require_once __DIR__ . '/mdm_device_profile.php';
require_once __DIR__ . '/device_log.php';

class MdmAcknowledge
{
    public const TOPIC_CONNECT = 'mdm.Connect';

    public static function handleConnect(array $payload, array $event, string $ip): array
    {
        $eventId = trim((string) ($payload['event_id'] ?? ''));
        $udid = trim((string) ($event['udid'] ?? ''));
        $status = trim((string) ($event['status'] ?? ''));
        $commandUuid = trim((string) ($event['command_uuid'] ?? ''));
        $eventTime = MdmPlist::parseCreatedAt($payload['created_at'] ?? null);

        if ($udid === '') {
            throw new InvalidArgumentException('无法解析设备 UDID');
        }

        $device = MdmDevice::findByUdid($udid);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在，请先完成设备注册');
        }

        $serial = $device['serial_number'];
        $remark = $device['remark'] ?? '';

        $commInfo = MdmDevice::recordCommunication($udid, $eventId, $eventTime);

        if ($commandUuid === '') {
            $result = self::handleDeviceConnect($serial, $udid, $remark, $eventId, $status, $eventTime, $ip);
        } else {
            $result = self::handleExecutionResponse($serial, $udid, $remark, $commandUuid, $status, $eventTime, $event, $ip);
        }

        return array_merge($result, [
            'comm_count'   => $commInfo['comm_count'],
            'last_comm_at' => $commInfo['last_comm_at'],
        ]);
    }

    private static function handleDeviceConnect(
        string $serial,
        string $udid,
        string $remark,
        string $eventId,
        string $status,
        string $eventTime,
        string $ip
    ): array {
        DeviceLog::logDeviceEvent($serial, [
            'device_udid'    => $udid,
            'device_remark'  => $remark,
            'serial_number'  => $serial,
            'operation_type' => '设备连接',
            'comm_id'        => $eventId,
            'push_id'        => '',
            'command_type'   => 'Connect',
            'status'         => '完成:' . $status,
            'confirmed_at'   => $eventTime,
            'created_at'     => $eventTime,
            'ip'             => $ip,
        ]);

        return [
            'type'       => 'connect',
            'udid'       => $udid,
            'serial_number' => $serial,
            'status'     => $status,
            'event_id'   => $eventId,
            'event_time' => $eventTime,
        ];
    }

    private static function handleExecutionResponse(
        string $serial,
        string $udid,
        string $remark,
        string $commandUuid,
        string $status,
        string $eventTime,
        array $event,
        string $ip
    ): array {
        $statusText = '完成:' . $status;
        $existing = DeviceLog::findByCommId($commandUuid);

        if ($existing) {
            DeviceLog::updateResponseByCommId($commandUuid, [
                'status'       => $statusText,
                'confirmed_at' => $eventTime,
            ], $serial);
        } else {
            DeviceLog::logDeviceEvent($serial, [
                'device_udid'    => $udid,
                'device_remark'  => $remark,
                'serial_number'  => $serial,
                'operation_type' => '执行完成',
                'comm_id'        => $commandUuid,
                'push_id'        => '',
                'command_type'   => $status,
                'status'         => $statusText . '(未发起通讯)',
                'confirmed_at'   => $eventTime,
                'created_at'     => $eventTime,
                'ip'             => $ip,
            ]);
        }

        $xmlUpdated = false;
        $profileListUpdated = false;
        $profileCount = 0;
        $statusUpdated = false;
        $statusFields = [];
        $rawXml = MdmPlist::decodeRawPayloadXml($event['raw_payload'] ?? '');
        if ($rawXml !== '' && MdmPlist::hasQueryResponses($rawXml)) {
            MdmDevice::updateXmlConfig($udid, $rawXml);
            $xmlUpdated = true;

            $queryResponses = MdmPlist::parseQueryResponses($rawXml);
            $updateResult = MdmDevice::updateFromQueryResponses($udid, $queryResponses);
            if (!empty($updateResult['updated'])) {
                $statusUpdated = true;
                if (array_key_exists('supervision_lock_status', $updateResult)) {
                    $statusFields['supervision_lock_status'] = $updateResult['supervision_lock_status'];
                }
                if (array_key_exists('lost_status', $updateResult)) {
                    $statusFields['lost_status'] = $updateResult['lost_status'];
                }
            }
        } elseif ($rawXml !== '' && MdmPlist::hasProfileList($rawXml)) {
            $profiles = MdmPlist::parseProfileList($rawXml);
            $profileCount = MdmDeviceProfile::syncFromProfileList($serial, $udid, $profiles);
            $profileListUpdated = $profileCount > 0;
        }

        return [
            'type'                 => 'execution_response',
            'udid'                 => $udid,
            'serial_number'        => $serial,
            'command_uuid'         => $commandUuid,
            'status'               => $status,
            'log_updated'          => $existing !== null,
            'xml_updated'          => $xmlUpdated,
            'profile_list_updated' => $profileListUpdated,
            'profile_count'        => $profileCount,
            'status_updated'       => $statusUpdated,
            'status_fields'        => $statusFields,
            'event_time'           => $eventTime,
        ];
    }
}
