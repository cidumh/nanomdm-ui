<?php
/**
 * 配置项完成状态（用于侧边栏/控制台标题颜色）
 */

require_once __DIR__ . '/dep.php';
require_once __DIR__ . '/profile.php';
require_once __DIR__ . '/mdm.php';

class ConfigStatus
{
    private static $status = null;

    public static function load(): array
    {
        if (self::$status !== null) {
            return self::$status;
        }

        self::$status = [
            'dep'     => DepConfig::isConfigured(),
            'profile' => ProfileConfig::isConfigured(),
            'mdm'     => MdmConfig::isConfigured(),
        ];

        return self::$status;
    }

    public static function isOk(string $key): bool
    {
        $status = self::load();
        return !empty($status[$key]);
    }

    public static function titleClass(string $key): string
    {
        return self::isOk($key) ? 'config-title-ok' : 'config-title-missing';
    }
}
