<?php
/**
 * DEP 配置文件与 DEP 服务器通讯
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/dep.php';
require_once __DIR__ . '/logger.php';

class DepProfile
{
    public static function ensureTables(): void
    {
        DepConfig::ensureTables();
        DB::execute("CREATE TABLE IF NOT EXISTS `dep_profiles` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function skipSetupOptions(): array
    {
        return [
            ['key' => 'AppleID', 'label' => 'AppleID', 'default' => false],
            ['key' => 'Biometric', 'label' => '指纹面容', 'default' => false],
            ['key' => 'Diagnostics', 'label' => '应用数据共享', 'default' => true],
            ['key' => 'Location', 'label' => '定位设置', 'default' => true],
            ['key' => 'Passcode', 'label' => '屏幕密码', 'default' => false],
            ['key' => 'Payment', 'label' => 'Apple Pay', 'default' => true],
            ['key' => 'Privacy', 'label' => '隐私权限设置', 'default' => true],
            ['key' => 'ScreenTime', 'label' => '屏幕使用时间', 'default' => true],
            ['key' => 'Siri', 'label' => 'Siri设置', 'default' => true],
            ['key' => 'SoftwareUpdate', 'label' => '系统更新', 'default' => true],
            ['key' => 'TOS', 'label' => '服务条款', 'default' => true],
            ['key' => 'Welcome', 'label' => '欢迎首页', 'default' => true],
            ['key' => 'Android', 'label' => '从安卓迁移', 'default' => true],
            ['key' => 'Appearance', 'label' => '外观选择', 'default' => true],
            ['key' => 'DeviceToDeviceMigration', 'label' => '设备迁移', 'default' => true],
            ['key' => 'iMessageAndFaceTime', 'label' => 'iMessage设置', 'default' => true],
            ['key' => 'Intelligence', 'label' => '智能功能设置', 'default' => true],
            ['key' => 'Keyboard', 'label' => '键盘设置', 'default' => true],
            ['key' => 'MessagingActivationUsingPhoneNumber', 'label' => 'iMessage 激活', 'default' => true],
            ['key' => 'Safety', 'label' => '安全设置', 'default' => true],
        ];
    }

    public static function defaultSkipSetup(): array
    {
        $result = [];
        foreach (self::skipSetupOptions() as $item) {
            $result[$item['key']] = $item['default'];
        }
        return $result;
    }

    public static function listAll(): array
    {
        return DB::fetchAll(
            'SELECT id, profile_uuid, profile_name, mdm_url, web_url, department,
                    language, region, device_serials, created_at, updated_at
             FROM dep_profiles ORDER BY updated_at DESC, id DESC'
        );
    }

    public static function saveFromResponse(string $profileUuid, array $data, int $userId): void
    {
        $exists = DB::fetchOne('SELECT id FROM dep_profiles WHERE profile_uuid = ? LIMIT 1', [$profileUuid]);

        $serials = $data['devices'] ?? [];
        $skipItems = $data['skip_setup_items'] ?? [];

        $fields = [
            $data['profile_name'],
            $data['mdm_url'],
            $data['web_url'],
            $data['department'],
            $data['org_magic'] ?? '',
            !empty($data['is_supervised']) ? 1 : 0,
            !empty($data['await_device_configured']) ? 1 : 0,
            !empty($data['is_mandatory']) ? 1 : 0,
            !empty($data['is_mdm_removable']) ? 1 : 0,
            $data['language'] ?? 'zh',
            $data['region'] ?? 'CN',
            $data['support_email'] ?? '',
            $data['support_phone'] ?? '',
            !empty($data['skip_setup_enabled']) ? 1 : 0,
            json_encode($skipItems, JSON_UNESCAPED_UNICODE),
            json_encode($serials, JSON_UNESCAPED_UNICODE),
            json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            $userId,
        ];

        if ($exists) {
            $fields[] = $profileUuid;
            DB::execute(
                'UPDATE dep_profiles SET profile_name=?, mdm_url=?, web_url=?, department=?, org_magic=?,
                 is_supervised=?, await_device_configured=?, is_mandatory=?, is_mdm_removable=?,
                 language=?, region=?, support_email=?, support_phone=?, skip_setup_enabled=?,
                 skip_setup_items=?, device_serials=?, payload_json=?, user_id=?, updated_at=NOW()
                 WHERE profile_uuid=?',
                $fields
            );
        } else {
            array_unshift($fields, $profileUuid);
            DB::execute(
                'INSERT INTO dep_profiles (profile_uuid, profile_name, mdm_url, web_url, department, org_magic,
                 is_supervised, await_device_configured, is_mandatory, is_mdm_removable, language, region,
                 support_email, support_phone, skip_setup_enabled, skip_setup_items, device_serials,
                 payload_json, user_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                $fields
            );
        }
    }
}

class DepClient
{
    public static function buildProxyUrl(string $path): string
    {
        $api  = DepConfig::get('dep_api');
        $name = DepConfig::get('dep_api_name');
        $base = rtrim(trim($api), '/');
        $name = trim($name);
        $path = ltrim($path, '/');

        if ($name !== '') {
            return $base . '/proxy/' . rawurlencode($name) . '/' . $path;
        }
        return $base . '/proxy/' . $path;
    }

    public static function buildProfileUrl(string $api, string $name): string
    {
        $base = rtrim(trim($api), '/');
        $name = trim($name);
        if ($name !== '') {
            return $base . '/proxy/' . rawurlencode($name) . '/profile';
        }
        return $base . '/proxy/profile';
    }

    public static function getProfile(string $profileUuid): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $profileUuid = trim($profileUuid);
        if ($profileUuid === '') {
            return ['ok' => false, 'msg' => '缺少 profile_uuid'];
        }

        $api  = DepConfig::get('dep_api');
        $name = DepConfig::get('dep_api_name');
        $url  = self::buildProfileUrl($api, $name) . '?profile_uuid=' . rawurlencode($profileUuid);

        return self::request('GET', $url);
    }

    public static function submitProfile(array $payload): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $api  = DepConfig::get('dep_api');
        $name = DepConfig::get('dep_api_name');
        $url  = self::buildProfileUrl($api, $name);
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $result = self::request('POST', $url, $body);
        if (!$result['ok']) {
            return $result;
        }

        $data = $result['data'];
        if (empty($data['profile_uuid'])) {
            return ['ok' => false, 'msg' => 'DEP 服务器响应异常，未返回 profile_uuid'];
        }

        return ['ok' => true, 'profile_uuid' => $data['profile_uuid'], 'response' => $data];
    }

    public static function listDevices(?string $cursor = null, int $limit = 100): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $url = self::buildProxyUrl('server/devices');
        $payload = ['limit' => $limit];
        if ($cursor !== null && trim($cursor) !== '') {
            $payload['cursor'] = trim($cursor);
        }
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        return self::request('POST', $url, $body);
    }

    public static function searchDevices(array $serials): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $serials = array_values(array_filter(array_map('trim', $serials)));
        if (empty($serials)) {
            return ['ok' => false, 'msg' => '请填写设备序列号'];
        }

        $url = self::buildProxyUrl('devices');
        $body = json_encode(['devices' => $serials], JSON_UNESCAPED_UNICODE);

        return self::request('POST', $url, $body);
    }

    public static function bindProfileDevices(string $profileUuid, array $serials): array
    {
        return self::profileDevicesRequest('POST', $profileUuid, $serials);
    }

    public static function removeProfileDevices(string $profileUuid, array $serials): array
    {
        return self::profileDevicesRequest('DELETE', $profileUuid, $serials);
    }

    public static function disownDevices(array $serials): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $serials = array_values(array_filter(array_map('trim', $serials)));
        if (empty($serials)) {
            return ['ok' => false, 'msg' => '缺少设备序列号'];
        }

        $url = self::buildProxyUrl('devices/disown');
        $body = json_encode(['devices' => $serials], JSON_UNESCAPED_UNICODE);

        return self::request('POST', $url, $body);
    }

    public static function enableActivationLock(string $serial, string $escrowKey, string $lostMessage): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $serial = trim($serial);
        $escrowKey = trim($escrowKey);
        $lostMessage = trim($lostMessage);

        if ($serial === '') {
            return ['ok' => false, 'msg' => '缺少设备序列号'];
        }
        if ($escrowKey === '') {
            return ['ok' => false, 'msg' => '缺少 escrow_key'];
        }

        $url = self::buildProxyUrl('device/activationlock');
        $body = json_encode([
            'device'       => $serial,
            'escrow_key'   => $escrowKey,
            'lost_message' => $lostMessage,
        ], JSON_UNESCAPED_UNICODE);

        return self::request('POST', $url, $body);
    }

    private static function profileDevicesRequest(string $method, string $profileUuid, array $serials): array
    {
        if (!DepConfig::isConfigured()) {
            return ['ok' => false, 'msg' => '请先在 DEP 配置中开启 DEP 开关并填写 DEP API'];
        }

        $profileUuid = trim($profileUuid);
        $serials = array_values(array_filter(array_map('trim', $serials)));

        if ($profileUuid === '') {
            return ['ok' => false, 'msg' => '请选择配置文件'];
        }
        if (empty($serials)) {
            return ['ok' => false, 'msg' => '缺少设备序列号'];
        }

        $url = self::buildProxyUrl('profile/devices');
        $body = json_encode([
            'devices'      => $serials,
            'profile_uuid' => $profileUuid,
        ], JSON_UNESCAPED_UNICODE);

        return self::request($method, $url, $body);
    }

    private static function request(string $method, string $url, ?string $body = null): array
    {
        $user = DepConfig::get('dep_api_username');
        $pass = DepConfig::get('dep_api_password');
        $method = strtoupper($method);

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ];

        if ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
        } else {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json;charset=UTF8'];
        }

        if ($user !== '' || $pass !== '') {
            $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $opts[CURLOPT_USERPWD]  = $user . ':' . $pass;
        }

        curl_setopt_array($ch, $opts);
        self::applyCurlSsl($ch);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'msg' => 'DEP 请求失败：' . $error];
        }

        $data = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = is_array($data) ? ($data['error'] ?? $data['message'] ?? $response) : $response;
            return ['ok' => false, 'msg' => 'DEP 服务器返回错误（HTTP ' . $httpCode . '）：' . $msg];
        }

        if (!is_array($data)) {
            return ['ok' => false, 'msg' => 'DEP 服务器响应格式异常'];
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * SSL 选项 - Windows 环境常缺 CA 根证书，默认跳过验证
     */
    private static function applyCurlSsl($ch): void
    {
        $verify = DepConfig::getBool('dep_ssl_verify');

        if ($verify) {
            $caFile = dirname(__DIR__) . '/config/cacert.pem';
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (file_exists($caFile)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caFile);
            }
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
    }

    public static function buildPayload(array $input): array
    {
        $skipEnabled = !empty($input['skip_setup_enabled']);
        $skipItems = [];
        if ($skipEnabled && !empty($input['skip_setup']) && is_array($input['skip_setup'])) {
            foreach ($input['skip_setup'] as $key => $val) {
                if ($val) {
                    $skipItems[] = $key;
                }
            }
        }

        $devices = depParseSerialLines($input['device_serials'] ?? '');

        return [
            'profile_name'              => trim($input['profile_name'] ?? ''),
            'url'                       => trim($input['mdm_url'] ?? ''),
            'allow_pairing'             => true,
            'auto_advance_setup'        => false,
            'await_device_configured'   => !empty($input['await_device_configured']),
            'configuration_web_url'     => trim($input['web_url'] ?? ''),
            'department'                => trim($input['department'] ?? ''),
            'is_supervised'             => !empty($input['is_supervised']),
            'is_multi_user'             => false,
            'is_mandatory'              => !empty($input['is_mandatory']),
            'is_mdm_removable'          => !empty($input['is_mdm_removable']),
            'language'                  => trim($input['language'] ?? 'zh') ?: 'zh',
            'org_magic'                 => trim($input['org_magic'] ?? ''),
            'region'                    => trim($input['region'] ?? 'CN') ?: 'CN',
            'support_phone_number'      => trim($input['support_phone'] ?? ''),
            'support_email_address'     => trim($input['support_email'] ?? ''),
            'anchor_certs'              => [],
            'supervising_host_certs'    => [],
            'skip_setup_items'          => $skipItems,
            'devices'                   => $devices,
        ];
    }
}

function depParseSerialLines(string $text): array
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

function depFormatBeijingTime(?string $iso): string
{
    if ($iso === null || trim($iso) === '') {
        return '-';
    }
    try {
        $dt = new DateTime($iso, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Shanghai'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $iso;
    }
}

function depProfileStatusText(?string $status): string
{
    $map = [
        'pushed'  => '已成功推送',
        'empty'   => '未推送',
        'pending' => '待推送',
    ];
    return $map[$status ?? ''] ?? ($status ?: '-');
}

function depNormalizeDevice(array $device): array
{
    return [
        'serial_number'        => $device['serial_number'] ?? '',
        'description'          => $device['description'] ?? '-',
        'model'                => $device['model'] ?? '-',
        'os'                   => $device['os'] ?? '-',
        'device_family'        => $device['device_family'] ?? '-',
        'color'                => $device['color'] ?? '-',
        'profile_uuid'         => $device['profile_uuid'] ?? '',
        'profile_assign_time'  => depFormatBeijingTime($device['profile_assign_time'] ?? ''),
        'profile_push_time'    => depFormatBeijingTime($device['profile_push_time'] ?? ''),
        'profile_status'       => depProfileStatusText($device['profile_status'] ?? ''),
        'profile_status_raw'   => $device['profile_status'] ?? '',
        'device_assigned_by'   => $device['device_assigned_by'] ?? '-',
        'device_assigned_date' => depFormatBeijingTime($device['device_assigned_date'] ?? ''),
        'response_status'      => $device['response_status'] ?? '',
    ];
}

function depParseDevicesResponse(array $data): array
{
    $devices = [];

    if (isset($data['devices']) && is_array($data['devices'])) {
        if (depIsListArray($data['devices'])) {
            foreach ($data['devices'] as $item) {
                if (is_array($item)) {
                    $devices[] = depNormalizeDevice($item);
                }
            }
        } else {
            foreach ($data['devices'] as $item) {
                if (is_array($item)) {
                    $devices[] = depNormalizeDevice($item);
                }
            }
        }
    }

    return $devices;
}

function depIsListArray(array $arr): bool
{
    if ($arr === []) {
        return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
}
