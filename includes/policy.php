<?php
/**
 * 策略配置读写
 */

require_once __DIR__ . '/db.php';

class PolicyConfig
{
    const ORG_NAME_DEFAULT = '瓷都名汇';
    const DNS_SERVER_URL_DEFAULT = 'https://dns.alidns.com/dns-query';
    const DNS_ADDRESS_1_DEFAULT = '223.5.5.5';
    const DNS_ADDRESS_2_DEFAULT = '223.6.6.6';

    private static $cache = null;

    public static function orgName(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::ORG_NAME_DEFAULT;
    }

    public static function dnsServerUrl(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::DNS_SERVER_URL_DEFAULT;
    }

    public static function dnsAddress1(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::DNS_ADDRESS_1_DEFAULT;
    }

    public static function dnsAddress2(string $stored = ''): string
    {
        $stored = trim($stored);
        return $stored !== '' ? $stored : self::DNS_ADDRESS_2_DEFAULT;
    }

    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `policy_config` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cfg_key` VARCHAR(64) NOT NULL,
            `cfg_value` TEXT,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_cfg_key` (`cfg_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        DB::execute("CREATE TABLE IF NOT EXISTS `dep_config` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cfg_key` VARCHAR(64) NOT NULL,
            `cfg_value` TEXT,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_cfg_key` (`cfg_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::removeDeprecatedKeys();
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
        $exists = DB::fetchOne('SELECT id FROM policy_config WHERE cfg_key = ? LIMIT 1', [$key]);
        if ($exists) {
            DB::execute('UPDATE policy_config SET cfg_value = ? WHERE cfg_key = ?', [$value, $key]);
        } else {
            DB::execute('INSERT INTO policy_config (cfg_key, cfg_value) VALUES (?, ?)', [$key, $value]);
        }
        self::$cache = null;
    }

    public static function removeKeys(array $keys): void
    {
        foreach ($keys as $key) {
            DB::execute('DELETE FROM policy_config WHERE cfg_key = ?', [trim($key)]);
        }
        self::$cache = null;
    }

    public static function removeDeprecatedKeys(): void
    {
        self::removeKeys(['app_restriction', 'app_restriction_ids']);
    }

    public static function funcRestrictionDefaults(): array
    {
        $defaults = [];
        foreach (self::funcRestrictionKeys() as $item) {
            $defaults[$item['key']] = $item['default'];
        }
        $defaults['camera_whitelist_enabled'] = false;
        $defaults['allowedCameraRestrictionBundleIDs'] = '';
        return $defaults;
    }

    public static function funcRestrictionKeys(): array
    {
        return [
            ['key' => 'allowAccountModification', 'label' => '允许修改 Apple ID', 'default' => true, 'security' => false],
            ['key' => 'allowAppInstallation', 'label' => '允许使用 App Store', 'default' => true, 'security' => false],
            ['key' => 'allowUIAppInstallation', 'label' => '不隐藏 App Store', 'default' => true, 'security' => false],
            ['key' => 'allowAppRemoval', 'label' => '允许卸载应用', 'default' => true, 'security' => false],
            ['key' => 'allowSystemAppRemoval', 'label' => '允许卸载系统应用', 'default' => true, 'security' => false],
            ['key' => 'allowMarketplaceAppInstallation', 'label' => '允许安装第三方应用', 'default' => true, 'security' => false],
            ['key' => 'allowWebDistributionAppInstallation', 'label' => '允许侧载安装应用', 'default' => true, 'security' => false],
            ['key' => 'allowCamera', 'label' => '允许使用相机', 'default' => true, 'security' => false],
            ['key' => 'allowScreenShot', 'label' => '允许使用截图录屏', 'default' => true, 'security' => false],
            ['key' => 'allowCallRecording', 'label' => '允许通话录音', 'default' => true, 'security' => false],
            ['key' => 'allowAirDrop', 'label' => '允许隔空投送', 'default' => true, 'security' => false],
            ['key' => 'allowNFC', 'label' => '允许 NFC 功能', 'default' => true, 'security' => false],
            ['key' => 'allowUSBRestrictedMode', 'label' => '启用 USB 受限模式', 'default' => true, 'security' => false],
            ['key' => 'allowFilesUSBDriveAccess', 'label' => '允许文件访问外 U 盘', 'default' => true, 'security' => false],
            ['key' => 'forceWiFiPowerOn', 'label' => '强制 WiFi 开启', 'default' => true, 'security' => false],
            ['key' => 'allowVPNCreation', 'label' => '允许创建 VPN', 'default' => false, 'security' => true],
            ['key' => 'allowEraseContentAndSettings', 'label' => '允许抹掉所有内容和设置', 'default' => true, 'security' => false],
            ['key' => 'allowEnablingRestrictions', 'label' => '允许屏幕使用时间', 'default' => true, 'security' => false],
            ['key' => 'allowHostPairing', 'label' => '允许与电脑配对', 'default' => false, 'security' => true],
            ['key' => 'allowSafari', 'label' => '允许使用 Safari 浏览器', 'default' => true, 'security' => false],
            ['key' => 'allowFilesNetworkDriveAccess', 'label' => '允许文件 APP 访问网络驱动', 'default' => true, 'security' => false],
            ['key' => 'allowCloudBackup', 'label' => '允许 iCloud 整机备份', 'default' => true, 'security' => false],
            ['key' => 'allowFindMyDevice', 'label' => '允许开启查找设备', 'default' => true, 'security' => false],
            ['key' => 'allowEnterpriseAppTrust', 'label' => '允许信任企业开发者', 'default' => true, 'security' => false],
            ['key' => 'allowUIConfigurationProfileInstallation', 'label' => '允许安装配置描述文件', 'default' => false, 'security' => true],
            ['key' => 'allowUnpairedExternalBootToRecovery', 'label' => '允许未配对外部设备引导进入恢复', 'default' => false, 'security' => true],
            ['key' => 'allowWallpaperModification', 'label' => '允许修改壁纸', 'default' => true, 'security' => false],
            ['key' => 'forceAutomaticDateAndTime', 'label' => '禁用自动设置日期和时间', 'default' => true, 'security' => false],
        ];
    }

    public static function getFuncRestrictions(): array
    {
        $raw = self::get('func_restrictions', '');
        $data = $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return self::funcRestrictionDefaults();
        }
        return array_merge(self::funcRestrictionDefaults(), $data);
    }

    public static function setFuncRestrictions(array $data): void
    {
        self::set('func_restrictions', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function getAll(): array
    {
        return [
            'activation_lock' => self::getBool('activation_lock'),
            'dns_proxy' => self::getBool('dns_proxy'),
            'dns_org_name' => self::orgName(self::get('dns_org_name')),
            'dns_identifier' => self::get('dns_identifier'),
            'dns_server_url' => self::dnsServerUrl(self::get('dns_server_url')),
            'dns_address_1' => self::dnsAddress1(self::get('dns_address_1')),
            'dns_address_2' => self::dnsAddress2(self::get('dns_address_2')),
            'global_proxy' => self::getBool('global_proxy'),
            'proxy_org_name' => self::orgName(self::get('proxy_org_name')),
            'proxy_identifier' => self::get('proxy_identifier'),
            'proxy_pac_url' => self::get('proxy_pac_url'),
            'func_restriction' => self::getBool('func_restriction'),
            'func_org_name' => self::orgName(self::get('func_org_name')),
            'func_identifier' => self::get('func_identifier'),
            'func_restrictions' => self::getFuncRestrictions(),
        ];
    }

    private static function loadAll(): void
    {
        self::$cache = [];
        try {
            $rows = DB::fetchAll('SELECT cfg_key, cfg_value FROM policy_config');
            foreach ($rows as $row) {
                self::$cache[$row['cfg_key']] = $row['cfg_value'];
            }
        } catch (Exception $e) {
        }
    }
}

function policyIsIpv4(string $ip): bool
{
    return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

function policyIsUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function policyParseLines(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $result[] = $line;
        }
    }
    return $result;
}
