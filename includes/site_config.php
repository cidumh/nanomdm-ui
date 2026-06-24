<?php
/**
 * 站点配置读取
 */

require_once __DIR__ . '/db.php';

class SiteConfig
{
    private static $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            self::loadAll();
        }
        return self::$cache[$key] ?? $default;
    }

    public static function siteName(): string
    {
        return self::get('site_name', '瓷都名汇-MDM管理系统');
    }

    public static function footerDefaults(): array
    {
        return [
            'footer_icp_text' => '粤ICP备2024204088号',
            'footer_icp_url'  => 'https://beian.miit.gov.cn/',
            'footer_ga_text'  => '粤公网安备44510302000351号',
            'footer_ga_url'   => 'http://www.beian.gov.cn/portal/registerSystemInfo',
        ];
    }

    public static function ensureFooterDefaults(): void
    {
        if (!isInstalled()) {
            return;
        }
        foreach (self::footerDefaults() as $key => $value) {
            $exists = DB::fetchOne('SELECT id FROM site_config WHERE cfg_key = ? LIMIT 1', [$key]);
            if (!$exists) {
                DB::execute('INSERT INTO site_config (cfg_key, cfg_value) VALUES (?, ?)', [$key, $value]);
            }
        }
        self::$cache = null;
    }

    public static function footerIcpText(): string
    {
        return self::get('footer_icp_text', self::footerDefaults()['footer_icp_text']);
    }

    public static function footerIcpUrl(): string
    {
        return self::get('footer_icp_url', self::footerDefaults()['footer_icp_url']);
    }

    public static function footerGaText(): string
    {
        return self::get('footer_ga_text', self::footerDefaults()['footer_ga_text']);
    }

    public static function footerGaUrl(): string
    {
        return self::get('footer_ga_url', self::footerDefaults()['footer_ga_url']);
    }

    /**
     * 页脚备案信息（未安装时使用默认值，不访问数据库）
     */
    public static function footerForDisplay(): array
    {
        if (isInstalled()) {
            self::ensureFooterDefaults();
        }
        $defaults = self::footerDefaults();
        return [
            'icp_text' => self::get('footer_icp_text', $defaults['footer_icp_text']),
            'icp_url'  => self::get('footer_icp_url', $defaults['footer_icp_url']),
            'ga_text'  => self::get('footer_ga_text', $defaults['footer_ga_text']),
            'ga_url'   => self::get('footer_ga_url', $defaults['footer_ga_url']),
        ];
    }

    public static function set(string $key, string $value): void
    {
        $exists = DB::fetchOne('SELECT id FROM site_config WHERE cfg_key = ? LIMIT 1', [$key]);
        if ($exists) {
            DB::execute('UPDATE site_config SET cfg_value = ? WHERE cfg_key = ?', [$value, $key]);
        } else {
            DB::execute('INSERT INTO site_config (cfg_key, cfg_value) VALUES (?, ?)', [$key, $value]);
        }
        self::$cache = null;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    private static function loadAll(): void
    {
        self::$cache = [];
        if (!isInstalled()) {
            return;
        }
        try {
            $rows = DB::fetchAll('SELECT cfg_key, cfg_value FROM site_config');
            foreach ($rows as $row) {
                self::$cache[$row['cfg_key']] = $row['cfg_value'];
            }
        } catch (Exception $e) {
            // 安装阶段可能还没有表
        }
    }
}
