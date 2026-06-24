<?php
/**
 * 单设备日志表 device_logs_{序列号}
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/log_query.php';

class DevicePerLog
{
    public static function tableNameForSerial(string $serial): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $serial);
        $safe = trim($safe, '_');
        if ($safe === '') {
            throw new InvalidArgumentException('无效的设备序列号');
        }
        return 'device_logs_' . $safe;
    }

    public static function ensureTable(string $serial): string
    {
        $table = self::tableNameForSerial($serial);
        if (!preg_match('/^device_logs_[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('无效的设备日志表名');
        }

        DB::execute("CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `serial_number` VARCHAR(64) NOT NULL DEFAULT '',
            `udid` VARCHAR(64) NOT NULL DEFAULT '',
            `device_remark` VARCHAR(255) NOT NULL DEFAULT '',
            `comm_id` VARCHAR(128) NOT NULL DEFAULT '',
            `push_id` VARCHAR(128) NOT NULL DEFAULT '',
            `operation_type` VARCHAR(64) NOT NULL DEFAULT '',
            `command_type` VARCHAR(128) NOT NULL DEFAULT '',
            `command_status` VARCHAR(128) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL,
            `confirmed_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_created` (`created_at`),
            KEY `idx_operation` (`operation_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return $table;
    }

    public static function insert(string $serial, array $data): void
    {
        $table = self::ensureTable($serial);
        $createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');

        DB::execute(
            "INSERT INTO `{$table}` (
                serial_number, udid, device_remark, comm_id, push_id,
                operation_type, command_type, command_status, created_at, confirmed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['serial_number'] ?? $serial,
                $data['udid'] ?? '',
                $data['device_remark'] ?? '',
                $data['comm_id'] ?? '',
                $data['push_id'] ?? '',
                $data['operation_type'] ?? '',
                $data['command_type'] ?? '',
                $data['command_status'] ?? '',
                $createdAt,
                $data['confirmed_at'] ?? null,
            ]
        );
    }

    public static function updateResponseByCommId(string $serial, string $commId, array $data): bool
    {
        if ($commId === '') {
            return false;
        }

        $table = self::ensureTable($serial);
        $row = DB::fetchOne(
            "SELECT id FROM `{$table}` WHERE comm_id = ? ORDER BY id DESC LIMIT 1",
            [$commId]
        );
        if (!$row) {
            return false;
        }

        DB::execute(
            "UPDATE `{$table}` SET command_status = ?, confirmed_at = ? WHERE comm_id = ?",
            [
                $data['command_status'] ?? '',
                $data['confirmed_at'] ?? null,
                $commId,
            ]
        );

        return true;
    }

    public static function tableExists(string $serial): bool
    {
        try {
            $table = self::tableNameForSerial($serial);
            $row = DB::fetchOne(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$table]
            );
            return !empty($row);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function list(string $serial, array $input): array
    {
        if (!self::tableExists($serial)) {
            return [
                'total'       => 0,
                'page'        => 1,
                'per_page'    => LogQuery::PER_PAGE,
                'total_pages' => 1,
                'list'        => [],
            ];
        }

        $table = self::tableNameForSerial($serial);
        $params = LogQuery::params($input);
        $bind = [];
        $where = LogQuery::dateWhere('created_at', $params['date_from'], $params['date_to'], $bind);

        if ($params['search_keyword'] !== '') {
            $scopeMap = [
                'serial_number'  => 'serial_number',
                'operation_type' => 'operation_type',
                'comm_id'        => 'comm_id',
                'push_id'        => 'push_id',
                'command_type'   => 'command_type',
                'status'         => 'command_status',
            ];
            $where .= LogQuery::buildSearch($scopeMap, $params['search_scope'], $params['search_keyword'], $bind);
        }

        $meta = LogQuery::paginate($table, $where, $bind, $params['page'], $params['per_page']);
        $offset = ($meta['page'] - 1) * $params['per_page'];

        $sql = "SELECT id, serial_number, udid, device_remark, comm_id, push_id,
                       operation_type, command_type, command_status, created_at, confirmed_at
                FROM `{$table}` WHERE 1=1 {$where}
                ORDER BY id DESC LIMIT {$params['per_page']} OFFSET {$offset}";
        $list = DB::fetchAll($sql, $bind);

        return array_merge($meta, ['list' => $list]);
    }
}
