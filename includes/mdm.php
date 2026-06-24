<?php
/**
 * MDM 配置读写
 */

require_once __DIR__ . '/db.php';

class MdmConfig
{
    private static $cache = null;

    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `mdm_config` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cfg_key` VARCHAR(64) NOT NULL,
            `cfg_value` TEXT,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_cfg_key` (`cfg_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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
        $exists = DB::fetchOne('SELECT id FROM mdm_config WHERE cfg_key = ? LIMIT 1', [$key]);
        if ($exists) {
            DB::execute('UPDATE mdm_config SET cfg_value = ? WHERE cfg_key = ?', [$value, $key]);
        } else {
            DB::execute('INSERT INTO mdm_config (cfg_key, cfg_value) VALUES (?, ?)', [$key, $value]);
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
        $password = self::get('mdm_api_password');
        return [
            'mdm_server_url'   => self::get('mdm_server_url'),
            'mdm_api_username' => self::get('mdm_api_username'),
            'mdm_api_password' => $password,
            'has_password'     => $password !== '',
            'mdm_configured'   => self::isConfigured(),
        ];
    }

    public static function serverUrlBase(): string
    {
        return rtrim(trim(self::get('mdm_server_url')), '/');
    }

    private static function loadAll(): void
    {
        self::$cache = [];
        try {
            $rows = DB::fetchAll('SELECT cfg_key, cfg_value FROM mdm_config');
            foreach ($rows as $row) {
                self::$cache[$row['cfg_key']] = $row['cfg_value'];
            }
        } catch (Exception $e) {
        }
    }
}

function mdmIsUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function mdmGenerateCommandUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return strtolower(vsprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        str_split(bin2hex($data), 4)
    ));
}

function mdmReplaceCommandUuid(string $content): array
{
    $uuid = mdmGenerateCommandUuid();
    $replaced = str_replace(['_CommandUUID_', '(CommandUUID)'], $uuid, $content);
    return [
        'content'      => $replaced,
        'command_uuid' => $uuid,
    ];
}
