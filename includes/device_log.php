<?php

/**

 * 设备日志 - 结构化记录

 */



require_once __DIR__ . '/db.php';

require_once __DIR__ . '/log_query.php';



class DeviceLog

{

    public static function ensureTables(): void

    {

        DB::execute("CREATE TABLE IF NOT EXISTS `device_logs` (

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

            `status` VARCHAR(32) NOT NULL DEFAULT '',

            `confirmed_at` DATETIME DEFAULT NULL,

            `action` VARCHAR(128) NOT NULL DEFAULT '',

            `detail` TEXT,

            `ip` VARCHAR(45) DEFAULT NULL,

            `created_at` DATETIME NOT NULL,

            PRIMARY KEY (`id`),

            KEY `idx_created` (`created_at`),

            KEY `idx_udid` (`device_udid`),

            KEY `idx_serial` (`serial_number`),

            KEY `idx_operation` (`operation_type`)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



        self::migrateColumns();

    }



    private static function migrateColumns(): void

    {

        $columns = [

            'device_remark'  => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `device_udid`",

            'serial_number'  => "VARCHAR(64) NOT NULL DEFAULT '' AFTER `device_remark`",

            'operation_type' => "VARCHAR(64) NOT NULL DEFAULT '' AFTER `serial_number`",

            'comm_id'        => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `operation_type`",

            'push_id'        => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `comm_id`",

            'command_type'   => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `push_id`",

            'request_url'    => "VARCHAR(500) NOT NULL DEFAULT '' AFTER `command_type`",

            'status'         => "VARCHAR(32) NOT NULL DEFAULT '' AFTER `request_url`",

            'confirmed_at'   => 'DATETIME DEFAULT NULL AFTER `status`',

        ];



        foreach ($columns as $name => $definition) {

            if (!DB::tableHasColumn('device_logs', $name)) {

                DB::execute("ALTER TABLE `device_logs` ADD COLUMN `{$name}` {$definition}");

            }

        }



        if (DB::tableHasColumn('device_logs', 'topic_type') && !DB::tableHasColumn('device_logs', 'command_type')) {

            DB::execute("ALTER TABLE `device_logs` CHANGE COLUMN `topic_type` `command_type` VARCHAR(128) NOT NULL DEFAULT ''");

        } else        if (DB::tableHasColumn('device_logs', 'topic_type') && DB::tableHasColumn('device_logs', 'command_type')) {

            DB::execute("UPDATE `device_logs` SET `command_type` = `topic_type` WHERE `command_type` = '' AND `topic_type` <> ''");

        }

        if (DB::tableHasColumn('device_logs', 'status')) {
            DB::execute("ALTER TABLE `device_logs` MODIFY COLUMN `status` VARCHAR(128) NOT NULL DEFAULT ''");
        }

        if (!self::hasIndex('device_logs', 'idx_comm_id')) {
            DB::execute('ALTER TABLE `device_logs` ADD KEY `idx_comm_id` (`comm_id`)');
        }

    }

    private static function hasIndex(string $table, string $indexName): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table) || !preg_match('/^[a-z0-9_]+$/', $indexName)) {
            return false;
        }
        $rows = DB::fetchAll("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($rows);
    }

    public static function findByCommId(string $commId): ?array
    {
        self::ensureTables();
        if ($commId === '') {
            return null;
        }
        return DB::fetchOne(
            'SELECT * FROM device_logs WHERE comm_id = ? ORDER BY id DESC LIMIT 1',
            [$commId]
        );
    }

    public static function updateResponseByCommId(string $commId, array $data, string $serial = ''): bool
    {
        self::ensureTables();
        $row = self::findByCommId($commId);
        if (!$row) {
            return false;
        }

        if ($serial === '') {
            $serial = $row['serial_number'] ?? '';
        }

        DB::execute(
            'UPDATE device_logs SET status = ?, confirmed_at = ? WHERE comm_id = ?',
            [
                $data['status'] ?? $row['status'],
                $data['confirmed_at'] ?? null,
                $commId,
            ]
        );

        if ($serial !== '') {
            require_once __DIR__ . '/device_per_log.php';
            DevicePerLog::updateResponseByCommId($serial, $commId, [
                'command_status' => $data['status'] ?? $row['status'],
                'confirmed_at'   => $data['confirmed_at'] ?? null,
            ]);
        }

        return true;
    }



    public static function insert(array $data): void
    {
        self::ensureTables();

        $createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');

        $sql = 'INSERT INTO device_logs (
            user_id, device_udid, device_remark, serial_number, operation_type,
            comm_id, push_id, command_type, request_url, status, confirmed_at,
            action, detail, ip, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        DB::execute($sql, [
            $data['user_id'] ?? null,
            $data['device_udid'] ?? '',
            $data['device_remark'] ?? '',
            $data['serial_number'] ?? '',
            $data['operation_type'] ?? '',
            $data['comm_id'] ?? '',
            $data['push_id'] ?? '',
            $data['command_type'] ?? ($data['topic_type'] ?? ''),
            $data['request_url'] ?? '',
            $data['status'] ?? '',
            $data['confirmed_at'] ?? null,
            $data['action'] ?? ($data['operation_type'] ?? ''),
            $data['detail'] ?? '',
            $data['ip'] ?? clientIp(),
            $createdAt,
        ]);
    }

    /**
     * 写入全局设备日志 + 单设备日志表
     */
    public static function logDeviceEvent(string $serial, array $data): void
    {
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }

        self::insert($data);

        require_once __DIR__ . '/device_per_log.php';
        DevicePerLog::insert($serial, [
            'serial_number'  => $data['serial_number'] ?? $serial,
            'udid'           => $data['device_udid'] ?? '',
            'device_remark'  => $data['device_remark'] ?? '',
            'comm_id'        => $data['comm_id'] ?? '',
            'push_id'        => $data['push_id'] ?? '',
            'operation_type' => $data['operation_type'] ?? '',
            'command_type'   => $data['command_type'] ?? '',
            'command_status' => $data['status'] ?? '',
            'created_at'     => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'confirmed_at'   => $data['confirmed_at'] ?? null,
        ]);
    }



    public static function list(array $input): array

    {

        self::ensureTables();

        $params = LogQuery::params($input);

        $bind = [];

        $where = LogQuery::dateWhere('created_at', $params['date_from'], $params['date_to'], $bind);



        if ($params['search_keyword'] !== '') {
            $scopeMap = [
                'udid'           => 'device_udid',
                'serial_number'  => 'serial_number',
                'operation_type' => 'operation_type',
                'comm_id'        => 'comm_id',
                'push_id'        => 'push_id',
                'command_type'   => 'command_type',
                'status'         => 'status',
            ];
            $where .= LogQuery::buildSearch($scopeMap, $params['search_scope'], $params['search_keyword'], $bind);
        }



        $meta = LogQuery::paginate('device_logs', $where, $bind, $params['page'], $params['per_page']);

        $offset = ($meta['page'] - 1) * $params['per_page'];



        $sql = "SELECT id, device_udid, device_remark, serial_number, operation_type,

                       comm_id, push_id, command_type, request_url, status, confirmed_at, created_at

                FROM device_logs WHERE 1=1 {$where}

                ORDER BY id DESC LIMIT {$params['per_page']} OFFSET {$offset}";

        $list = DB::fetchAll($sql, $bind);



        return array_merge($meta, ['list' => $list]);

    }

}

