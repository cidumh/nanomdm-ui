<?php
/**
 * MDM 受管设备
 */

require_once __DIR__ . '/db.php';

class MdmDevice
{
    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `mdm_devices` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::migrateColumns();
    }

    private static function migrateColumns(): void
    {
        if (DB::tableHasColumn('mdm_devices', 'token')) {
            DB::execute('ALTER TABLE `mdm_devices` MODIFY COLUMN `token` MEDIUMTEXT');
        }
    }

    public static function findByUdid(string $udid): ?array
    {
        self::ensureTables();
        if ($udid === '') {
            return null;
        }
        return DB::fetchOne('SELECT * FROM mdm_devices WHERE udid = ? LIMIT 1', [$udid]);
    }

    public static function findBySerial(string $serial): ?array
    {
        self::ensureTables();
        if ($serial === '') {
            return null;
        }
        return DB::fetchOne('SELECT * FROM mdm_devices WHERE serial_number = ? LIMIT 1', [$serial]);
    }

    /**
     * 处理设备注册（Authenticate）
     */
    public static function registerAuthenticate(array $info): array
    {
        self::ensureTables();

        $serial = trim((string) ($info['serial_number'] ?? ''));
        $udid = trim((string) ($info['udid'] ?? ''));
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }
        if ($udid === '') {
            throw new InvalidArgumentException('设备 UDID 不能为空');
        }

        $now = trim((string) ($info['registered_at'] ?? ''));
        if ($now === '') {
            $now = date('Y-m-d H:i:s');
        }

        $existing = self::findBySerial($serial);
        if (!$existing) {
            DB::execute(
                'INSERT INTO mdm_devices (
                    serial_number, udid, remark, contact_phone, device_type, device_model,
                    supervision_status, supervision_lock_status, activation_lock_status, lost_status,
                    xml_config, topic, token, token_update_count, comm_count,
                    registered_at, last_comm_at, last_comm_event_id,
                    build_version, imei, os_version, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, 0, 0, 0, ?, ?, ?, 0, 1, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $serial,
                    $udid,
                    '',
                    '',
                    trim((string) ($info['device_type'] ?? '')),
                    trim((string) ($info['device_model'] ?? '')),
                    '',
                    trim((string) ($info['topic'] ?? '')),
                    '',
                    $now,
                    $now,
                    trim((string) ($info['event_id'] ?? '')),
                    trim((string) ($info['build_version'] ?? '')),
                    trim((string) ($info['imei'] ?? '')),
                    trim((string) ($info['os_version'] ?? '')),
                    $now,
                    $now,
                ]
            );

            return [
                'serial_number' => $serial,
                'udid'          => $udid,
                'is_new'        => true,
            ];
        }

        DB::execute(
            'UPDATE mdm_devices SET
                udid = ?,
                device_type = ?,
                device_model = ?,
                supervision_status = 1,
                topic = ?,
                build_version = ?,
                imei = ?,
                os_version = ?,
                last_comm_at = ?,
                last_comm_event_id = ?,
                comm_count = comm_count + 1,
                updated_at = ?
             WHERE serial_number = ?',
            [
                $udid,
                trim((string) ($info['device_type'] ?? '')),
                trim((string) ($info['device_model'] ?? '')),
                trim((string) ($info['topic'] ?? '')),
                trim((string) ($info['build_version'] ?? '')),
                trim((string) ($info['imei'] ?? '')),
                trim((string) ($info['os_version'] ?? '')),
                $now,
                trim((string) ($info['event_id'] ?? '')),
                $now,
                $serial,
            ]
        );

        return [
            'serial_number' => $serial,
            'udid'          => $udid,
            'is_new'        => false,
        ];
    }

    /**
     * 处理设备令牌更新（TokenUpdate）
     */
    public static function updateTokenUpdate(array $info): array
    {
        self::ensureTables();

        $udid = trim((string) ($info['udid'] ?? ''));
        if ($udid === '') {
            throw new InvalidArgumentException('设备 UDID 不能为空');
        }

        $device = self::findByUdid($udid);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在，请先完成设备注册');
        }

        $serial = $device['serial_number'];
        $now = trim((string) ($info['event_time'] ?? ''));
        if ($now === '') {
            $now = date('Y-m-d H:i:s');
        }

        $tokenUpdateCount = max(0, (int) ($info['token_update_count'] ?? 0));

        DB::execute(
            'UPDATE mdm_devices SET
                udid = ?,
                topic = ?,
                token = ?,
                token_update_count = ?,
                last_comm_at = ?,
                last_comm_event_id = ?,
                comm_count = comm_count + 1,
                updated_at = ?
             WHERE serial_number = ?',
            [
                $udid,
                trim((string) ($info['topic'] ?? '')),
                trim((string) ($info['token'] ?? '')),
                $tokenUpdateCount,
                $now,
                trim((string) ($info['event_id'] ?? '')),
                $now,
                $serial,
            ]
        );

        return [
            'serial_number'       => $serial,
            'udid'                => $udid,
            'token_update_count'  => $tokenUpdateCount,
        ];
    }

    /**
     * 处理设备退出监管（CheckOut）
     */
    public static function updateCheckOut(array $info): array
    {
        self::ensureTables();

        $udid = trim((string) ($info['udid'] ?? ''));
        if ($udid === '') {
            throw new InvalidArgumentException('设备 UDID 不能为空');
        }

        $device = self::findByUdid($udid);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $serial = $device['serial_number'];

        DB::execute(
            'UPDATE mdm_devices SET supervision_status = 0 WHERE serial_number = ?',
            [$serial]
        );

        return [
            'serial_number'      => $serial,
            'udid'               => $udid,
            'supervision_status' => 0,
        ];
    }

    public static function updateXmlConfig(string $udid, string $xml): void
    {
        self::ensureTables();
        if ($udid === '' || trim($xml) === '') {
            return;
        }
        DB::execute(
            'UPDATE mdm_devices SET xml_config = ?, updated_at = NOW() WHERE udid = ?',
            [$xml, $udid]
        );
    }

    public static function updateFromQueryResponses(string $udid, array $queryResponses): array
    {
        self::ensureTables();
        if ($udid === '' || empty($queryResponses)) {
            return ['updated' => false];
        }

        $fields = [];
        $params = [];
        $result = ['updated' => false];

        if (array_key_exists('IsSupervised', $queryResponses)) {
            $fields[] = 'supervision_lock_status = ?';
            $params[] = self::plistBoolToStatus($queryResponses['IsSupervised']);
            $result['supervision_lock_status'] = self::plistBoolToStatus($queryResponses['IsSupervised']);
        }

        if (array_key_exists('IsMDMLostModeEnabled', $queryResponses)) {
            $fields[] = 'lost_status = ?';
            $params[] = self::plistBoolToStatus($queryResponses['IsMDMLostModeEnabled']);
            $result['lost_status'] = self::plistBoolToStatus($queryResponses['IsMDMLostModeEnabled']);
        }

        if (empty($fields)) {
            return $result;
        }

        $fields[] = 'updated_at = NOW()';
        $params[] = $udid;

        DB::execute(
            'UPDATE mdm_devices SET ' . implode(', ', $fields) . ' WHERE udid = ?',
            $params
        );

        $result['updated'] = true;

        return $result;
    }

    private static function plistBoolToStatus($value): int
    {
        if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
            return 1;
        }

        return 0;
    }

    public static function setActivationLockStatus(string $serial, bool $enabled): void
    {
        self::ensureTables();
        if ($serial === '') {
            return;
        }
        DB::execute(
            'UPDATE mdm_devices SET activation_lock_status = ?, updated_at = NOW() WHERE serial_number = ?',
            [$enabled ? 1 : 0, $serial]
        );
    }

    /**
     * 记录设备通讯（mdm.Connect）
     */
    public static function recordCommunication(string $udid, string $eventId, string $eventTime): array
    {
        self::ensureTables();

        $udid = trim($udid);
        if ($udid === '') {
            throw new InvalidArgumentException('设备 UDID 不能为空');
        }

        $commAt = trim($eventTime);
        if ($commAt === '') {
            $commAt = date('Y-m-d H:i:s');
        }

        DB::execute(
            'UPDATE mdm_devices SET
                last_comm_at = ?,
                last_comm_event_id = ?,
                comm_count = comm_count + 1,
                updated_at = ?
             WHERE udid = ?',
            [$commAt, trim($eventId), $commAt, $udid]
        );

        $device = self::findByUdid($udid);

        return [
            'serial_number' => $device['serial_number'] ?? '',
            'comm_count'    => (int) ($device['comm_count'] ?? 0),
            'last_comm_at'  => $device['last_comm_at'] ?? $commAt,
        ];
    }

    public static function list(array $input): array
    {
        self::ensureTables();

        $page = max(1, (int) ($input['page'] ?? 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        $keyword = trim((string) ($input['keyword'] ?? ''));

        $where = '1=1';
        $params = [];
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $where .= ' AND (serial_number LIKE ? OR remark LIKE ? OR contact_phone LIKE ? OR udid LIKE ?)';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $countRow = DB::fetchOne("SELECT COUNT(*) AS cnt FROM mdm_devices WHERE {$where}", $params);
        $total = (int) ($countRow['cnt'] ?? 0);

        $rows = DB::fetchAll(
            "SELECT serial_number, udid, remark, contact_phone, device_type, device_model, os_version,
                    supervision_status, supervision_lock_status, activation_lock_status, lost_status,
                    comm_count, last_comm_at, registered_at
             FROM mdm_devices
             WHERE {$where}
             ORDER BY COALESCE(registered_at, created_at) ASC, id ASC
             LIMIT {$pageSize} OFFSET {$offset}",
            $params
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = self::formatListItem($row);
        }

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => max(1, (int) ceil($total / $pageSize)),
        ];
    }

    public static function updateRemark(string $serial, string $remark): array
    {
        self::ensureTables();
        $serial = trim($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }

        $device = self::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $remark = trim($remark);
        if (mb_strlen($remark) > 255) {
            throw new InvalidArgumentException('备注不能超过 255 字');
        }

        DB::execute(
            'UPDATE mdm_devices SET remark = ?, updated_at = NOW() WHERE serial_number = ?',
            [$remark, $serial]
        );

        return ['serial_number' => $serial, 'remark' => $remark];
    }

    public static function updateContactPhone(string $serial, string $phone): array
    {
        self::ensureTables();
        $serial = trim($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }

        $device = self::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        $phone = trim($phone);
        if (mb_strlen($phone) > 32) {
            throw new InvalidArgumentException('联系号码不能超过 32 字');
        }

        DB::execute(
            'UPDATE mdm_devices SET contact_phone = ?, updated_at = NOW() WHERE serial_number = ?',
            [$phone, $serial]
        );

        return ['serial_number' => $serial, 'contact_phone' => $phone];
    }

    public static function deleteBySerial(string $serial): array
    {
        self::ensureTables();
        $serial = trim($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('设备序列号不能为空');
        }

        $device = self::findBySerial($serial);
        if (!$device) {
            throw new InvalidArgumentException('设备不存在');
        }

        DB::execute('DELETE FROM mdm_devices WHERE serial_number = ?', [$serial]);

        return [
            'serial_number' => $serial,
            'udid'          => $device['udid'] ?? '',
        ];
    }

    public static function dashboardStats(): array
    {
        self::ensureTables();

        $today = date('Y-m-d');
        $from = $today . ' 00:00:00';
        $to = $today . ' 23:59:59';

        $totalRow = DB::fetchOne('SELECT COUNT(*) AS cnt FROM mdm_devices');
        $todayRow = DB::fetchOne(
            'SELECT COUNT(*) AS cnt FROM mdm_devices WHERE last_comm_at >= ? AND last_comm_at <= ?',
            [$from, $to]
        );

        return [
            'device_total' => (int) ($totalRow['cnt'] ?? 0),
            'today_active' => (int) ($todayRow['cnt'] ?? 0),
        ];
    }

    private static function formatListItem(array $row): array
    {
        $model = trim((string) ($row['device_type'] ?? ''));
        if ($model === '') {
            $model = trim((string) ($row['device_model'] ?? ''));
        }

        return [
            'serial_number'           => $row['serial_number'] ?? '',
            'remark'                  => $row['remark'] ?? '',
            'contact_phone'           => $row['contact_phone'] ?? '',
            'udid'                    => $row['udid'] ?? '',
            'device_model'            => $model,
            'os_version'              => $row['os_version'] ?? '',
            'supervision_status'      => (int) ($row['supervision_status'] ?? 0),
            'supervision_status_text' => self::statusText((int) ($row['supervision_status'] ?? 0), ['未监管', '监管中']),
            'supervision_lock_status' => (int) ($row['supervision_lock_status'] ?? 0),
            'supervision_lock_text'   => self::statusText((int) ($row['supervision_lock_status'] ?? 0), ['关闭', '开启']),
            'activation_lock_status'  => (int) ($row['activation_lock_status'] ?? 0),
            'activation_lock_text'    => self::statusText((int) ($row['activation_lock_status'] ?? 0), ['关闭', '开启']),
            'lost_status'             => (int) ($row['lost_status'] ?? 0),
            'lost_status_text'        => self::statusText((int) ($row['lost_status'] ?? 0), ['关闭', '开启']),
            'comm_count'              => (int) ($row['comm_count'] ?? 0),
            'last_comm_at'            => $row['last_comm_at'] ?? '',
            'registered_at'           => $row['registered_at'] ?? '',
        ];
    }

    private static function statusText(int $value, array $labels): string
    {
        return $labels[$value] ?? (string) $value;
    }
}
