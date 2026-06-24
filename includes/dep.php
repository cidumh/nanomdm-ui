<?php
/**
 * DEP 配置读写
 */

require_once __DIR__ . '/db.php';

class DepConfig
{
    private static $cache = null;

    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `dep_config` (
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
        $exists = DB::fetchOne('SELECT id FROM dep_config WHERE cfg_key = ? LIMIT 1', [$key]);
        if ($exists) {
            DB::execute('UPDATE dep_config SET cfg_value = ? WHERE cfg_key = ?', [$value, $key]);
        } else {
            DB::execute('INSERT INTO dep_config (cfg_key, cfg_value) VALUES (?, ?)', [$key, $value]);
        }
        self::$cache = null;
    }

    public static function isConfigured(): bool
    {
        try {
            self::ensureTables();
            return self::getBool('dep_enabled') && trim(self::get('dep_api')) !== '';
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getAll(): array
    {
        return [
            'dep_enabled'      => self::getBool('dep_enabled'),
            'dep_api'          => self::get('dep_api'),
            'dep_api_name'     => self::get('dep_api_name'),
            'dep_api_username' => self::get('dep_api_username'),
            'dep_api_password' => self::get('dep_api_password'),
            'dep_ssl_verify'   => self::getBool('dep_ssl_verify'),
            'dep_configured'   => self::isConfigured(),
        ];
    }

    private static function loadAll(): void
    {
        self::$cache = [];
        try {
            $rows = DB::fetchAll('SELECT cfg_key, cfg_value FROM dep_config');
            foreach ($rows as $row) {
                self::$cache[$row['cfg_key']] = $row['cfg_value'];
            }
        } catch (Exception $e) {
        }
    }
}
