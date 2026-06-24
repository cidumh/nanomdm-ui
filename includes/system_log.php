<?php
/**
 * 系统日志
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/log_query.php';

class SystemLog
{
    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `system_logs` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::migrateColumns();
    }

    private static function migrateColumns(): void
    {
        $columns = [
            'username'         => "VARCHAR(64) NOT NULL DEFAULT '' AFTER `user_id`",
            'operation_type'   => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `username`",
            'operation_result' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `operation_type`",
        ];

        foreach ($columns as $name => $definition) {
            if (!DB::tableHasColumn('system_logs', $name)) {
                DB::execute("ALTER TABLE `system_logs` ADD COLUMN `{$name}` {$definition}");
            }
        }

        if (DB::tableHasColumn('system_logs', 'action')) {
            DB::execute("UPDATE `system_logs` SET operation_type = action WHERE operation_type = '' AND action <> ''");
            DB::execute("UPDATE `system_logs` SET operation_result = LEFT(detail, 255) WHERE operation_result = '' AND detail <> ''");
        }
    }

    public static function insert(array $data): void
    {
        self::ensureTables();

        $username = self::resolveUsername($data['user_id'] ?? null, $data['username'] ?? null);

        DB::execute(
            'INSERT INTO system_logs (user_id, username, operation_type, operation_result, detail, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['user_id'] ?? null,
                $username,
                $data['operation_type'] ?? '',
                $data['operation_result'] ?? '',
                $data['detail'] ?? '',
                $data['ip'] ?? clientIp(),
            ]
        );
    }

    public static function list(array $input): array
    {
        self::ensureTables();
        $params = LogQuery::params($input);
        $bind = [];
        $where = LogQuery::dateWhere('created_at', $params['date_from'], $params['date_to'], $bind);

        if ($params['search_keyword'] !== '') {
            $like = LogQuery::like($params['search_keyword']);
            $scopeMap = [
                'username'         => 'username',
                'operation_type'   => 'operation_type',
                'operation_result' => 'operation_result',
                'ip'               => 'ip',
            ];
            if ($params['search_scope'] === 'all') {
                $where .= ' AND (username LIKE ? OR operation_type LIKE ? OR operation_result LIKE ? OR detail LIKE ? OR ip LIKE ?)';
                array_push($bind, $like, $like, $like, $like, $like);
            } elseif (isset($scopeMap[$params['search_scope']])) {
                $col = $scopeMap[$params['search_scope']];
                $where .= " AND `{$col}` LIKE ?";
                $bind[] = $like;
            }
        }

        $meta = LogQuery::paginate('system_logs', $where, $bind, $params['page'], $params['per_page']);
        $offset = ($meta['page'] - 1) * $params['per_page'];

        $sql = "SELECT id, username, operation_type, operation_result, detail, ip, created_at
                FROM system_logs WHERE 1=1 {$where}
                ORDER BY id DESC LIMIT {$params['per_page']} OFFSET {$offset}";
        $list = DB::fetchAll($sql, $bind);

        return array_merge($meta, ['list' => $list]);
    }

    private static function resolveUsername($userId, $username): string
    {
        if ($username !== null && $username !== '') {
            return (string) $username;
        }
        if ($userId) {
            $user = DB::fetchOne('SELECT username FROM users WHERE id = ? LIMIT 1', [$userId]);
            return $user['username'] ?? '';
        }
        return '';
    }
}
