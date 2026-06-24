<?php
/**
 * 设备已安装配置文件列表（ProfileList 同步）
 */

require_once __DIR__ . '/db.php';

class MdmDeviceProfile
{
    public const PAYLOAD_TYPE_DNS = 'com.apple.dnsSettings.managed';
    public const PAYLOAD_TYPE_GLOBAL = 'com.apple.proxy.http.global';
    public const PAYLOAD_TYPE_FUNC = 'com.apple.applicationaccess';

    public const DEFAULT_ID_DNS = 'com.cidumh.mdm.dnsdl';
    public const DEFAULT_ID_GLOBAL = 'com.cidumh.mdm.qjdl';
    public const DEFAULT_ID_FUNC = 'com.cidumh.mdm.gnxz';

    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `mdm_device_profiles` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `serial_number` VARCHAR(64) NOT NULL,
            `udid` VARCHAR(64) NOT NULL DEFAULT '',
            `payload_display_name` VARCHAR(255) NOT NULL DEFAULT '',
            `payload_identifier` VARCHAR(255) NOT NULL,
            `payload_uuid` VARCHAR(64) NOT NULL DEFAULT '',
            `payload_types` TEXT,
            `payload_types_text` VARCHAR(512) NOT NULL DEFAULT '',
            `is_managed` TINYINT NOT NULL DEFAULT 0,
            `payload_removal_disallowed` TINYINT NOT NULL DEFAULT 0,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_serial_identifier` (`serial_number`, `payload_identifier`(127)),
            KEY `idx_serial` (`serial_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function syncFromProfileList(string $serial, string $udid, array $profiles): int
    {
        self::ensureTables();
        $serial = trim($serial);
        if ($serial === '') {
            return 0;
        }

        DB::execute('DELETE FROM mdm_device_profiles WHERE serial_number = ?', [$serial]);

        $count = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($profiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $identifier = trim((string) ($profile['PayloadIdentifier'] ?? ''));
            if ($identifier === '') {
                continue;
            }

            $types = self::extractPayloadTypes($profile);
            DB::execute(
                'INSERT INTO mdm_device_profiles (
                    serial_number, udid, payload_display_name, payload_identifier, payload_uuid,
                    payload_types, payload_types_text, is_managed, payload_removal_disallowed, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $serial,
                    trim($udid),
                    trim((string) ($profile['PayloadDisplayName'] ?? '')),
                    $identifier,
                    trim((string) ($profile['PayloadUUID'] ?? '')),
                    json_encode($types, JSON_UNESCAPED_UNICODE),
                    implode(', ', $types),
                    self::boolValue($profile['IsManaged'] ?? false) ? 1 : 0,
                    self::boolValue($profile['PayloadRemovalDisallowed'] ?? false) ? 1 : 0,
                    $now,
                ]
            );
            $count++;
        }

        return $count;
    }

    public static function listBySerial(string $serial): array
    {
        self::ensureTables();
        $rows = DB::fetchAll(
            'SELECT payload_display_name, payload_identifier, payload_uuid, payload_types_text,
                    is_managed, payload_removal_disallowed, updated_at
             FROM mdm_device_profiles
             WHERE serial_number = ?
             ORDER BY payload_display_name ASC, payload_identifier ASC',
            [trim($serial)]
        );

        return array_map(static function (array $row): array {
            return [
                'payload_display_name'       => $row['payload_display_name'] ?? '',
                'payload_identifier'         => $row['payload_identifier'] ?? '',
                'payload_uuid'               => $row['payload_uuid'] ?? '',
                'payload_types_text'         => $row['payload_types_text'] ?? '',
                'is_managed'                 => (int) ($row['is_managed'] ?? 0),
                'payload_removal_disallowed' => (int) ($row['payload_removal_disallowed'] ?? 0),
                'updated_at'                 => $row['updated_at'] ?? '',
            ];
        }, $rows ?: []);
    }

    public static function findIdentifierByPayloadType(string $serial, string $payloadType): string
    {
        self::ensureTables();
        $serial = trim($serial);
        $payloadType = trim($payloadType);
        if ($serial === '' || $payloadType === '') {
            return '';
        }

        $rows = DB::fetchAll(
            'SELECT payload_identifier, payload_types FROM mdm_device_profiles WHERE serial_number = ?',
            [$serial]
        );

        foreach ($rows ?: [] as $row) {
            $types = json_decode((string) ($row['payload_types'] ?? ''), true);
            if (!is_array($types)) {
                continue;
            }
            if (in_array($payloadType, $types, true)) {
                return trim((string) ($row['payload_identifier'] ?? ''));
            }
        }

        return '';
    }

    public static function defaultIdentifier(string $payloadType): string
    {
        switch ($payloadType) {
            case self::PAYLOAD_TYPE_DNS:
                return self::DEFAULT_ID_DNS;
            case self::PAYLOAD_TYPE_GLOBAL:
                return self::DEFAULT_ID_GLOBAL;
            case self::PAYLOAD_TYPE_FUNC:
                return self::DEFAULT_ID_FUNC;
            default:
                return '';
        }
    }

    public static function resolveIdentifier(string $serial, string $payloadType): string
    {
        $existing = self::findIdentifierByPayloadType($serial, $payloadType);
        if ($existing !== '') {
            return $existing;
        }
        return self::defaultIdentifier($payloadType);
    }

    public static function extractPayloadTypes(array $profile): array
    {
        $types = [];
        $content = $profile['PayloadContent'] ?? [];
        if (!is_array($content)) {
            return $types;
        }

        foreach ($content as $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = trim((string) ($item['PayloadType'] ?? ''));
            if ($type !== '') {
                $types[] = $type;
            }
        }

        return array_values(array_unique($types));
    }

    private static function boolValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $text = strtolower(trim((string) $value));
        return $text === '1' || $text === 'true';
    }
}
