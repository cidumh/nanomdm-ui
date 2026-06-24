<?php

/**

 * API 通讯日志

 */



require_once __DIR__ . '/db.php';

require_once __DIR__ . '/log_query.php';



class ApiLog

{

    public static function ensureTables(): void

    {

        DB::execute("CREATE TABLE IF NOT EXISTS `api_logs` (

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

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



        self::migrateColumns();

    }



    private static function migrateColumns(): void

    {

        $columns = [

            'device_udid' => "VARCHAR(64) NOT NULL DEFAULT '' AFTER `id`",

            'topic_type'  => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `device_udid`",

            'topic_id'    => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `topic_type`",

            'comm_id'     => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `topic_id`",

            'push_id'     => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `comm_id`",

            'content'     => 'MEDIUMTEXT AFTER `push_id`',

            'ip'          => "VARCHAR(45) DEFAULT NULL AFTER `content`",

            'transfer'    => "VARCHAR(32) NOT NULL DEFAULT '' AFTER `ip`",

        ];



        foreach ($columns as $name => $definition) {

            if (!DB::tableHasColumn('api_logs', $name)) {

                DB::execute("ALTER TABLE `api_logs` ADD COLUMN `{$name}` {$definition}");

            }

        }



        if (DB::tableHasColumn('api_logs', 'mdm_ip') && !DB::tableHasColumn('api_logs', 'ip')) {

            DB::execute("ALTER TABLE `api_logs` CHANGE COLUMN `mdm_ip` `ip` VARCHAR(45) DEFAULT NULL");

        }

    }



    public static function insert(array $data): void
    {
        self::ensureTables();

        $createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');

        DB::execute(
            'INSERT INTO api_logs (device_udid, topic_type, topic_id, comm_id, push_id, content, ip, transfer, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['device_udid'] ?? '',
                $data['topic_type'] ?? '',
                $data['topic_id'] ?? '',
                $data['comm_id'] ?? '',
                $data['push_id'] ?? '',
                $data['content'] ?? '',
                $data['ip'] ?? ($data['mdm_ip'] ?? ''),
                $data['transfer'] ?? '',
                $createdAt,
            ]
        );
    }

    public static function logReceive(array $params): void
    {
        self::insert([
            'device_udid' => $params['device_udid'] ?? '',
            'topic_type'  => $params['topic_type'] ?? '',
            'topic_id'    => $params['topic_id'] ?? '',
            'comm_id'     => $params['comm_id'] ?? '',
            'push_id'     => '',
            'content'     => $params['content'] ?? '',
            'ip'          => $params['ip'] ?? clientIp(),
            'transfer'    => '接收',
            'created_at'  => $params['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }



    public static function logSendCommand(array $params): void

    {

        self::insert([

            'device_udid' => $params['device_udid'] ?? '',

            'topic_type'  => $params['topic_type'] ?? '自定义',

            'topic_id'    => $params['topic_id'] ?? '',

            'comm_id'     => $params['comm_id'] ?? '',

            'push_id'     => $params['push_id'] ?? '',

            'content'     => $params['content'] ?? '',

            'ip'          => $params['ip'] ?? clientIp(),

            'transfer'    => $params['transfer'] ?? '发送',

        ]);

    }

    public static function countToday(): int
    {
        self::ensureTables();

        $today = date('Y-m-d');
        $from = $today . ' 00:00:00';
        $to = $today . ' 23:59:59';

        $row = DB::fetchOne(
            'SELECT COUNT(*) AS cnt FROM api_logs WHERE created_at >= ? AND created_at <= ?',
            [$from, $to]
        );

        return (int) ($row['cnt'] ?? 0);
    }



    public static function list(array $input): array

    {

        self::ensureTables();

        $params = LogQuery::params($input);

        $bind = [];

        $where = LogQuery::dateWhere('created_at', $params['date_from'], $params['date_to'], $bind);



        $scopeMap = [
            'udid'       => 'device_udid',
            'topic_type' => 'topic_type',
            'topic_id'   => 'topic_id',
            'comm_id'    => 'comm_id',
            'push_id'    => 'push_id',
            'content'    => 'content',
            'ip'         => 'ip',
        ];

        $where .= LogQuery::buildSearch($scopeMap, $params['search_scope'], $params['search_keyword'], $bind);



        $meta = LogQuery::paginate('api_logs', $where, $bind, $params['page'], $params['per_page']);

        $offset = ($meta['page'] - 1) * $params['per_page'];



        $sql = "SELECT id, device_udid, topic_type, topic_id, comm_id, push_id, content, ip, transfer, created_at

                FROM api_logs WHERE 1=1 {$where}

                ORDER BY id DESC LIMIT {$params['per_page']} OFFSET {$offset}";

        $list = DB::fetchAll($sql, $bind);



        return array_merge($meta, ['list' => $list]);

    }

}

