<?php
/**
 * 设备详细信息（QueryResponses + 设备表字段）
 */

require_once __DIR__ . '/mdm_plist.php';
require_once __DIR__ . '/dep_activation_lock.php';
require_once __DIR__ . '/dep.php';
require_once __DIR__ . '/profile.php';

class DeviceDetail
{
    public static function build(array $device): array
    {
        $xml = trim((string) ($device['xml_config'] ?? ''));
        $query = $xml !== '' ? MdmPlist::parseQueryResponses($xml) : [];

        $details = [];
        $details[] = self::editableField('备注', $device['remark'] ?? '', 'remark');
        $details[] = self::editableField('联系号码', $device['contact_phone'] ?? '', 'phone');
        $details[] = self::editableField('设备名字', self::xmlVal($query, 'DeviceName'), 'device_name');
        $details[] = self::field('设备类型', self::xmlVal($query, 'ModelName'));
        $details[] = self::field('设备序列号', self::firstNonEmpty($device['serial_number'] ?? '', self::xmlVal($query, 'SerialNumber')));
        $details[] = self::field('设备 UDID', self::firstNonEmpty($device['udid'] ?? '', self::xmlVal($query, 'UDID')));
        $details[] = self::field('设备型号标识', self::xmlVal($query, 'ProductName'));
        $details[] = self::field('系统版本', self::firstNonEmpty(self::xmlVal($query, 'OSVersion'), $device['os_version'] ?? ''));
        $details[] = self::field('总容量', self::formatCapacityGb(self::xmlVal($query, 'DeviceCapacity')));
        $details[] = self::field('剩余容量', self::formatCapacityGb(self::xmlVal($query, 'AvailableDeviceCapacity'), true));
        $details[] = self::field('剩余电量', self::formatBattery(self::xmlVal($query, 'BatteryLevel')));

        $simCards = self::buildSimCards($query);
        foreach ($simCards as $card) {
            if (empty($card['fields'])) {
                continue;
            }
            $details[] = [
                'label'  => $card['title'],
                'type'   => 'sim_group',
                'fields' => $card['fields'],
            ];
        }

        $details[] = self::field('设备注册时间', $device['registered_at'] ?? '');
        $details[] = self::lastCommField($device['last_comm_at'] ?? '');
        $details[] = self::field('信息更新时间', $device['updated_at'] ?? '');
        $details[] = self::field('最近通讯事件 ID', $device['last_comm_event_id'] ?? '');
        $details[] = self::field('Topic', $device['topic'] ?? '');
        $details[] = self::tokenField($device['token'] ?? '');

        ProfileConfig::ensureTables();
        DepConfig::ensureTables();

        return [
            'serial_number'              => $device['serial_number'] ?? '',
            'udid'                       => $device['udid'] ?? '',
            'remark'                     => $device['remark'] ?? '',
            'contact_phone'              => $device['contact_phone'] ?? '',
            'topic'                      => trim((string) ($device['topic'] ?? '')),
            'activation_lock_status'     => (int) ($device['activation_lock_status'] ?? 0),
            'lost_status'                => (int) ($device['lost_status'] ?? 0),
            'supervision_status'         => (int) ($device['supervision_status'] ?? 0),
            'supervision_lock_status'    => (int) ($device['supervision_lock_status'] ?? 0),
            'has_xml'                    => $xml !== '' && !empty($query),
            'details'                    => $details,
            'status_badges'              => self::buildStatusBadges($device),
            'sim_cards'                  => $simCards,
            'token_synced'               => trim((string) ($device['token'] ?? '')) !== '',
            'has_bypass_code'            => DepActivationLock::getBySerial((string) ($device['serial_number'] ?? '')) !== null,
            'org_name_default'           => ProfileConfig::orgName(ProfileConfig::get('org_name')),
            'dep_configured'             => DepConfig::isConfigured(),
        ];
    }

    public static function extractUnlockIdentifiers(array $device): array
    {
        $xml = trim((string) ($device['xml_config'] ?? ''));
        $query = $xml !== '' ? MdmPlist::parseQueryResponses($xml) : [];

        $productType = self::xmlVal($query, 'ProductName');
        if ($productType === '') {
            $productType = trim((string) ($device['device_model'] ?? ''));
        }

        $imei1 = '';
        $imei2 = '';
        $meid = '';
        $subs = $query['ServiceSubscriptions'] ?? null;
        if (is_array($subs)) {
            foreach ($subs as $sim) {
                if (!is_array($sim)) {
                    continue;
                }
                if (self::hasVal($sim, 'IMEI')) {
                    $imei = self::stripSpaces($sim['IMEI']);
                    if ($imei1 === '') {
                        $imei1 = $imei;
                    } elseif ($imei2 === '' && $imei !== $imei1) {
                        $imei2 = $imei;
                    }
                }
                if ($meid === '' && self::hasVal($sim, 'MEID')) {
                    $meid = self::stripSpaces($sim['MEID']);
                }
            }
        }

        return [
            'product_type' => $productType,
            'imei'         => $imei1,
            'imei2'        => $imei2,
            'meid'         => $meid,
        ];
    }

    private static function buildStatusBadges(array $device): array
    {
        $supervision = (int) ($device['supervision_status'] ?? 0);
        $supervisionLock = (int) ($device['supervision_lock_status'] ?? 0);
        $activationLock = (int) ($device['activation_lock_status'] ?? 0);
        $lost = (int) ($device['lost_status'] ?? 0);

        return [
            self::statusBadge('监管状态', self::supervisionText($supervision), $supervision === 1 ? 'green' : 'red'),
            self::statusBadge('监管锁状态', self::onOffText($supervisionLock), $supervisionLock === 1 ? 'green' : 'red'),
            self::statusBadge('激活锁状态', self::onOffText($activationLock), $activationLock === 1 ? 'green' : 'red'),
            self::statusBadge('丢失锁机状态', self::onOffText($lost), $lost === 1 ? 'red' : 'green'),
        ];
    }

    private static function statusBadge(string $label, string $text, string $tone): array
    {
        return [
            'label' => $label,
            'text'  => $text,
            'tone'  => $tone,
        ];
    }

    private static function buildSimCards(array $query): array
    {
        $subs = $query['ServiceSubscriptions'] ?? null;
        if (!is_array($subs) || empty($subs)) {
            return [];
        }

        $cards = [];
        $index = 1;
        foreach ($subs as $sim) {
            if (!is_array($sim)) {
                continue;
            }
            $cards[] = [
                'title'  => 'SIM卡' . $index . '信息',
                'fields' => self::simFields($sim),
            ];
            $index++;
        }

        return $cards;
    }

    private static function simFields(array $sim): array
    {
        $fields = [];
        if (self::hasVal($sim, 'CurrentCarrierNetwork')) {
            $fields[] = self::field('运营商', $sim['CurrentCarrierNetwork']);
        }
        if (self::hasVal($sim, 'ICCID')) {
            $fields[] = self::field('ICCID', self::stripSpaces($sim['ICCID']));
        }
        if (self::hasVal($sim, 'IMEI')) {
            $fields[] = self::field('IMEI', self::stripSpaces($sim['IMEI']));
        }
        if (self::hasVal($sim, 'PhoneNumber')) {
            $fields[] = self::field('手机号', $sim['PhoneNumber']);
        }
        if (self::hasVal($sim, 'Label')) {
            $fields[] = self::field('号码标签', $sim['Label']);
        }
        if (array_key_exists('IsDataPreferred', $sim)) {
            $fields[] = self::field('默认数据卡', self::boolText($sim['IsDataPreferred']));
        }
        if (array_key_exists('IsVoicePreferred', $sim)) {
            $fields[] = self::field('默认号码卡', self::boolText($sim['IsVoicePreferred']));
        }

        return $fields;
    }

    private static function editableField(string $label, string $value, string $action): array
    {
        $field = self::field($label, $value);
        $field['type'] = 'editable';
        $field['action'] = $action;
        return $field;
    }

    private static function tokenField($token): array
    {
        $synced = trim((string) $token) !== '';
        return [
            'label' => 'Token',
            'value' => $synced ? '已同步' : '未同步',
            'tone'  => $synced ? 'green' : 'red',
        ];
    }

    private static function lastCommField(string $value): array
    {
        $field = self::field('最近一次通讯时间', $value);
        $field['tone'] = self::lastCommTone($value);
        return $field;
    }

    private static function lastCommTone(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'red';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return 'red';
        }

        $hours = (time() - $ts) / 3600;
        return $hours <= 72 ? 'green' : 'red';
    }

    private static function field(string $label, string $value): array
    {
        $value = trim($value);
        return [
            'label' => $label,
            'value' => $value !== '' ? $value : '-',
        ];
    }

    private static function xmlVal(array $query, string $key): string
    {
        if (!isset($query[$key]) || is_array($query[$key])) {
            return '';
        }
        return trim((string) $query[$key]);
    }

    private static function hasVal(array $data, string $key): bool
    {
        return isset($data[$key]) && !is_array($data[$key]) && trim((string) $data[$key]) !== '';
    }

    private static function firstNonEmpty(string ...$values): string
    {
        foreach ($values as $value) {
            $value = trim($value);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private static function formatCapacityGb(string $value, bool $forceDecimals = false): string
    {
        if ($value === '' || !is_numeric($value)) {
            return '-';
        }
        $gb = (float) $value;
        if ($forceDecimals) {
            return number_format($gb, 2, '.', '') . ' GB';
        }
        return rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.') . ' GB';
    }

    private static function stripSpaces($value): string
    {
        return preg_replace('/\s+/', '', trim((string) $value));
    }

    private static function formatBattery(string $value): string
    {
        if ($value === '' || !is_numeric($value)) {
            return '-';
        }
        $level = (float) $value;
        if ($level <= 1) {
            $level *= 100;
        }
        return round($level) . '%';
    }

    private static function boolText($value): string
    {
        if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
            return '是';
        }
        if ($value === false || $value === 'false' || $value === 0 || $value === '0') {
            return '否';
        }
        return '-';
    }

    private static function onOffText(int $status): string
    {
        return $status === 1 ? '开启' : '关闭';
    }

    private static function supervisionText(int $status): string
    {
        return $status === 1 ? '监管中' : '未监管';
    }
}
