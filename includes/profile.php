<?php
/**
 * 描述文件配置读写
 */

require_once __DIR__ . '/db.php';

class ProfileConfig
{
    const PROFILE_NAME_DEFAULT = 'CDMH-MDM';
    const PROFILE_DESCRIPTION_DEFAULT = '瓷都名汇-MDM管理系统';
    const ORG_NAME_DEFAULT = '瓷都名汇';
    const PROFILE_IDENTIFIER_DEFAULT = 'com.cidumh.mdm.server';
    const MDM_PAYLOAD_IDENTIFIER_DEFAULT = 'com.cidumh.mdm.mdm';
    const SCEP_IDENTIFIER_DEFAULT = 'com.cidumh.mdm.scep';

    private static $cache = null;

    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `profile_config` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cfg_key` VARCHAR(64) NOT NULL,
            `cfg_value` TEXT,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_cfg_key` (`cfg_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function profileName(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::PROFILE_NAME_DEFAULT;
    }

    public static function profileDescription(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::PROFILE_DESCRIPTION_DEFAULT;
    }

    public static function orgName(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::ORG_NAME_DEFAULT;
    }

    public static function profileIdentifier(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::PROFILE_IDENTIFIER_DEFAULT;
    }

    public static function mdmPayloadIdentifier(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::MDM_PAYLOAD_IDENTIFIER_DEFAULT;
    }

    public static function scepIdentifier(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::SCEP_IDENTIFIER_DEFAULT;
    }

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            self::loadAll();
        }
        return self::$cache[$key] ?? $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key, $default ? '1' : '0');
        return $val === '1' || $val === 'true';
    }

    public static function set(string $key, string $value): void
    {
        $exists = DB::fetchOne('SELECT id FROM profile_config WHERE cfg_key = ? LIMIT 1', [$key]);
        if ($exists) {
            DB::execute('UPDATE profile_config SET cfg_value = ? WHERE cfg_key = ?', [$value, $key]);
        } else {
            DB::execute('INSERT INTO profile_config (cfg_key, cfg_value) VALUES (?, ?)', [$key, $value]);
        }
        self::$cache = null;
    }

    public static function isConfigured(): bool
    {
        try {
            self::ensureTables();
            return trim(self::get('mdm_server_url')) !== '';
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getAll(): array
    {
        return [
            'profile_name'              => self::profileName(self::get('profile_name')),
            'profile_description'       => self::profileDescription(self::get('profile_description')),
            'org_name'                  => self::orgName(self::get('org_name')),
            'profile_identifier'        => self::profileIdentifier(self::get('profile_identifier')),
            'mdm_server_url'            => self::get('mdm_server_url'),
            'mdm_checkin_url'           => self::get('mdm_checkin_url'),
            'apns_topic_id'             => self::get('apns_topic_id'),
            'mdm_payload_identifier'    => self::mdmPayloadIdentifier(self::get('mdm_payload_identifier')),
            'user_agreement_enabled'    => self::getBool('user_agreement_enabled'),
            'user_agreement_content'    => self::get('user_agreement_content'),
            'scep_enabled'              => self::getBool('scep_enabled'),
            'scep_url'                  => self::get('scep_url'),
            'scep_challenge'            => self::get('scep_challenge'),
            'scep_identifier'           => self::scepIdentifier(self::get('scep_identifier')),
        ];
    }

    private static function loadAll(): void
    {
        self::$cache = [];
        try {
            $rows = DB::fetchAll('SELECT cfg_key, cfg_value FROM profile_config');
            foreach ($rows as $row) {
                self::$cache[$row['cfg_key']] = $row['cfg_value'];
            }
        } catch (Exception $e) {
        }
    }
}

function profileIsUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}
