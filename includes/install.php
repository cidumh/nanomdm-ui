<?php
/**
 * 安装逻辑 - 建库建表
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

class Installer
{
    /**
     * 执行完整安装
     */
    public static function run(array $input): array
    {
        $host     = trim($input['db_host'] ?? '127.0.0.1');
        $port     = (int)($input['db_port'] ?? 3306);
        $dbname   = trim($input['db_name'] ?? 'nanomdm_ui');
        $dbUser   = trim($input['db_user'] ?? 'cdmh');
        $dbPass   = $input['db_pass'] ?? '';
        $siteName = trim($input['site_name'] ?? '瓷都名汇-MDM管理系统');
        $adminUser = trim($input['admin_user'] ?? 'cdmh');
        $adminPass = $input['admin_pass'] ?? '';

        if ($adminPass === '') {
            return ['ok' => false, 'msg' => '请设置管理员密码'];
        }
        if (strlen($adminPass) < 6) {
            return ['ok' => false, 'msg' => '管理员密码至少6位'];
        }

        try {
            $pdo = DB::connectRaw($host, $port, $dbUser, $dbPass);

            // 建库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `{$dbname}`");

            // 建表
            self::createTables($pdo);

            // 写入管理员
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, status, created_at) VALUES (?, ?, 1, NOW())');
            $stmt->execute([$adminUser, $hash]);

            // 站点配置
            $configs = [
                'site_name' => $siteName,
                'installed' => '1',
                'installed_at' => date('Y-m-d H:i:s'),
                'footer_icp_text' => '粤ICP备2024204088号',
                'footer_icp_url'  => 'https://beian.miit.gov.cn/',
                'footer_ga_text'  => '粤公网安备44510302000351号',
                'footer_ga_url'   => 'http://www.beian.gov.cn/portal/registerSystemInfo',
            ];
            $cfgStmt = $pdo->prepare('INSERT INTO site_config (cfg_key, cfg_value) VALUES (?, ?)');
            foreach ($configs as $k => $v) {
                $cfgStmt->execute([$k, $v]);
            }

            self::writeConfigFile($host, $port, $dbname, $dbUser, $dbPass);

            return ['ok' => true, 'msg' => '安装成功'];
        } catch (PDOException $e) {
            return ['ok' => false, 'msg' => '数据库连接失败：' . $e->getMessage()];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => '安装出错：' . $e->getMessage()];
        }
    }

    private static function createTables(PDO $pdo): void
    {
        $sqls = [
            // 用户表
            "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(64) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0禁用',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // 登录会话
            "CREATE TABLE IF NOT EXISTS `user_sessions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `token` VARCHAR(64) NOT NULL,
                `ip` VARCHAR(45) DEFAULT NULL,
                `user_agent` VARCHAR(500) DEFAULT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_token` (`token`),
                KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // 站点配置
            "CREATE TABLE IF NOT EXISTS `site_config` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cfg_key` VARCHAR(64) NOT NULL,
                `cfg_value` TEXT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_cfg_key` (`cfg_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // 系统日志
            "CREATE TABLE IF NOT EXISTS `system_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED DEFAULT NULL,
                `username` VARCHAR(64) NOT NULL DEFAULT '',
                `operation_type` VARCHAR(128) NOT NULL DEFAULT '',
                `operation_result` VARCHAR(255) NOT NULL DEFAULT '',
                `detail` MEDIUMTEXT,
                `ip` VARCHAR(45) DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_created` (`created_at`),
                KEY `idx_username` (`username`),
                KEY `idx_operation_type` (`operation_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // 设备日志
            "CREATE TABLE IF NOT EXISTS `device_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED DEFAULT NULL,
                `device_udid` VARCHAR(64) NOT NULL DEFAULT '',
                `device_remark` VARCHAR(255) NOT NULL DEFAULT '',
                `serial_number` VARCHAR(64) NOT NULL DEFAULT '',
                `operation_type` VARCHAR(64) NOT NULL DEFAULT '',
                `comm_id` VARCHAR(128) NOT NULL DEFAULT '',
                `push_id` VARCHAR(128) NOT NULL DEFAULT '',
                `command_type` VARCHAR(128) NOT NULL DEFAULT '',
                `request_url` VARCHAR(500) NOT NULL DEFAULT '',
                `status` VARCHAR(128) NOT NULL DEFAULT '',
                `confirmed_at` DATETIME DEFAULT NULL,
                `action` VARCHAR(128) NOT NULL DEFAULT '',
                `detail` TEXT,
                `ip` VARCHAR(45) DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_created` (`created_at`),
                KEY `idx_udid` (`device_udid`),
                KEY `idx_serial` (`serial_number`),
                KEY `idx_operation` (`operation_type`),
                KEY `idx_comm_id` (`comm_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // API 日志
            "CREATE TABLE IF NOT EXISTS `api_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `device_udid` VARCHAR(64) NOT NULL DEFAULT '',
                `topic_type` VARCHAR(128) NOT NULL DEFAULT '',
                `topic_id` VARCHAR(128) NOT NULL DEFAULT '',
                `comm_id` VARCHAR(128) NOT NULL DEFAULT '',
                `push_id` VARCHAR(128) NOT NULL DEFAULT '',
                `content` MEDIUMTEXT,
                `ip` VARCHAR(45) DEFAULT NULL,
                `transfer` VARCHAR(32) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_created` (`created_at`),
                KEY `idx_udid` (`device_udid`),
                KEY `idx_topic_type` (`topic_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // MDM 受管设备
            "CREATE TABLE IF NOT EXISTS `mdm_devices` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `serial_number` VARCHAR(64) NOT NULL DEFAULT '',
                `udid` VARCHAR(64) NOT NULL DEFAULT '',
                `remark` VARCHAR(255) NOT NULL DEFAULT '',
                `contact_phone` VARCHAR(32) NOT NULL DEFAULT '',
                `device_type` VARCHAR(64) NOT NULL DEFAULT '',
                `device_model` VARCHAR(128) NOT NULL DEFAULT '',
                `supervision_status` TINYINT NOT NULL DEFAULT 0 COMMENT '0关闭 1监管中',
                `supervision_lock_status` TINYINT NOT NULL DEFAULT 0 COMMENT '0关闭 1开启',
                `activation_lock_status` TINYINT NOT NULL DEFAULT 0 COMMENT '0关闭 1开启',
                `lost_status` TINYINT NOT NULL DEFAULT 0 COMMENT '0关闭 1开启',
                `xml_config` MEDIUMTEXT,
                `topic` VARCHAR(255) NOT NULL DEFAULT '',
                `token` MEDIUMTEXT,
                `token_update_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `comm_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `registered_at` DATETIME DEFAULT NULL,
                `last_comm_at` DATETIME DEFAULT NULL,
                `last_comm_event_id` VARCHAR(128) NOT NULL DEFAULT '',
                `build_version` VARCHAR(64) NOT NULL DEFAULT '',
                `imei` VARCHAR(32) NOT NULL DEFAULT '',
                `os_version` VARCHAR(32) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_serial` (`serial_number`),
                KEY `idx_udid` (`udid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // 策略配置
            "CREATE TABLE IF NOT EXISTS `policy_config` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cfg_key` VARCHAR(64) NOT NULL,
                `cfg_value` TEXT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_cfg_key` (`cfg_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // DEP 配置
            "CREATE TABLE IF NOT EXISTS `dep_config` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cfg_key` VARCHAR(64) NOT NULL,
                `cfg_value` TEXT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_cfg_key` (`cfg_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // DEP 配置文件
            "CREATE TABLE IF NOT EXISTS `dep_profiles` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `profile_uuid` VARCHAR(64) NOT NULL,
                `profile_name` VARCHAR(255) NOT NULL,
                `mdm_url` VARCHAR(500) NOT NULL,
                `web_url` VARCHAR(500) NOT NULL,
                `department` VARCHAR(255) NOT NULL,
                `org_magic` VARCHAR(255) DEFAULT '',
                `is_supervised` TINYINT NOT NULL DEFAULT 1,
                `await_device_configured` TINYINT NOT NULL DEFAULT 0,
                `is_mandatory` TINYINT NOT NULL DEFAULT 1,
                `is_mdm_removable` TINYINT NOT NULL DEFAULT 0,
                `language` VARCHAR(16) NOT NULL DEFAULT 'zh',
                `region` VARCHAR(16) NOT NULL DEFAULT 'CN',
                `support_email` VARCHAR(255) DEFAULT '',
                `support_phone` VARCHAR(64) DEFAULT '',
                `skip_setup_enabled` TINYINT NOT NULL DEFAULT 0,
                `skip_setup_items` TEXT,
                `device_serials` TEXT,
                `payload_json` TEXT,
                `user_id` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_profile_uuid` (`profile_uuid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // DEP 激活锁绕过码（每设备仅保留最新一组）
            "CREATE TABLE IF NOT EXISTS `dep_activation_lock` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `serial_number` VARCHAR(64) NOT NULL,
                `bypass_code` VARCHAR(64) NOT NULL,
                `escrow_key` VARCHAR(64) NOT NULL,
                `lost_message` VARCHAR(500) DEFAULT '',
                `user_id` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_serial` (`serial_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // APNS 推送证书
            "CREATE TABLE IF NOT EXISTS `apns_certificates` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cert_remark` VARCHAR(255) NOT NULL DEFAULT '',
                `pem_cert` TEXT,
                `pem_private_key` TEXT,
                `topic` VARCHAR(255) NOT NULL DEFAULT '',
                `subject` VARCHAR(500) DEFAULT '',
                `issuer` VARCHAR(500) DEFAULT '',
                `serial_number` VARCHAR(128) DEFAULT '',
                `not_before` DATETIME DEFAULT NULL,
                `not_after` DATETIME DEFAULT NULL,
                `fingerprint` VARCHAR(128) DEFAULT '',
                `user_id` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // 描述文件配置
            "CREATE TABLE IF NOT EXISTS `profile_config` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cfg_key` VARCHAR(64) NOT NULL,
                `cfg_value` TEXT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_cfg_key` (`cfg_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // MDM 配置
            "CREATE TABLE IF NOT EXISTS `mdm_config` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cfg_key` VARCHAR(64) NOT NULL,
                `cfg_value` TEXT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_cfg_key` (`cfg_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($sqls as $sql) {
            $pdo->exec($sql);
        }
    }

    private static function writeConfigFile(string $host, int $port, string $dbname, string $user, string $pass): void
    {
        require_once __DIR__ . '/config_helper.php';
        writeDbConfigFile($host, $port, $dbname, $user, $pass);
    }
}
