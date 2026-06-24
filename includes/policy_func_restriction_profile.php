<?php
/**
 * 策略配置：功能限制描述文件
 */

require_once __DIR__ . '/policy.php';

class PolicyFuncRestrictionProfile
{
    public static function validateConfig(): array
    {
        $identifier = trim(PolicyConfig::get('func_identifier'));
        $restrictions = PolicyConfig::getFuncRestrictions();

        if ($identifier === '') {
            return ['ok' => false, 'msg' => '功能限制配置标识未设置'];
        }

        if (!empty($restrictions['camera_whitelist_enabled'])
            && trim((string) ($restrictions['allowedCameraRestrictionBundleIDs'] ?? '')) === '') {
            return ['ok' => false, 'msg' => '功能限制已开启相机白名单但未填写应用 ID'];
        }

        return ['ok' => true];
    }

    public static function buildProfileFrom(array $config): string
    {
        $check = self::validateConfigFrom($config);
        if (!$check['ok']) {
            throw new InvalidArgumentException($check['msg']);
        }

        $org = PolicyConfig::orgName(trim((string) ($config['func_org_name'] ?? '')));
        $identifier = trim((string) ($config['func_identifier'] ?? ''));
        $restrictions = self::resolveRestrictions($config);

        $accessUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $accessUuid) {
            $profileUuid = self::generateUuid();
        }

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = "\t<key>PayloadContent</key>";
        $lines[] = "\t<array>";
        $lines[] = "\t\t<dict>";
        $lines[] = "\t\t\t<key>PayloadDescription</key>";
        $lines[] = "\t\t\t<string>配置访问限制</string>";
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>访问限制</string>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>com.apple.applicationaccess." . $accessUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.applicationaccess</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $accessUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";

        foreach (PolicyConfig::funcRestrictionKeys() as $item) {
            $key = $item['key'];
            $lines[] = self::boolLine($key, !empty($restrictions[$key]));

            if ($key === 'allowCamera' && !empty($restrictions['camera_whitelist_enabled'])) {
                $lines[] = self::buildCameraWhitelistLines(
                    (string) ($restrictions['allowedCameraRestrictionBundleIDs'] ?? '')
                );
            }
        }

        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>功能限制</string>";
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>设备功能限制</string>";
        $lines[] = "\t<key>PayloadIdentifier</key>";
        $lines[] = "\t<string>" . self::escape($identifier) . '</string>';
        $lines[] = "\t<key>PayloadOrganization</key>";
        $lines[] = "\t<string>" . self::escape($org) . '</string>';
        $lines[] = "\t<key>PayloadType</key>";
        $lines[] = "\t<string>Configuration</string>";
        $lines[] = "\t<key>PayloadUUID</key>";
        $lines[] = "\t<string>" . $profileUuid . '</string>';
        $lines[] = "\t<key>PayloadVersion</key>";
        $lines[] = "\t<integer>1</integer>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    public static function validateConfigFrom(array $config): array
    {
        $identifier = trim((string) ($config['func_identifier'] ?? ''));
        $restrictions = self::resolveRestrictions($config);

        if ($identifier === '') {
            return ['ok' => false, 'msg' => '功能限制配置标识未设置'];
        }

        if (!empty($restrictions['camera_whitelist_enabled'])
            && trim((string) ($restrictions['allowedCameraRestrictionBundleIDs'] ?? '')) === '') {
            return ['ok' => false, 'msg' => '功能限制已开启相机白名单但未填写应用 ID'];
        }

        return ['ok' => true];
    }

    private static function resolveRestrictions(array $config): array
    {
        if (isset($config['func_restrictions']) && is_array($config['func_restrictions'])) {
            return $config['func_restrictions'];
        }
        return PolicyConfig::getFuncRestrictions();
    }

    public static function buildProfile(): string
    {
        $check = self::validateConfig();
        if (!$check['ok']) {
            throw new InvalidArgumentException($check['msg']);
        }

        $org = PolicyConfig::orgName(PolicyConfig::get('func_org_name'));
        $identifier = trim(PolicyConfig::get('func_identifier'));
        $restrictions = PolicyConfig::getFuncRestrictions();

        $accessUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $accessUuid) {
            $profileUuid = self::generateUuid();
        }

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = "\t<key>PayloadContent</key>";
        $lines[] = "\t<array>";
        $lines[] = "\t\t<dict>";
        $lines[] = "\t\t\t<key>PayloadDescription</key>";
        $lines[] = "\t\t\t<string>配置访问限制</string>";
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>访问限制</string>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>com.apple.applicationaccess." . $accessUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.applicationaccess</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $accessUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";

        foreach (PolicyConfig::funcRestrictionKeys() as $item) {
            $key = $item['key'];
            $lines[] = self::boolLine($key, !empty($restrictions[$key]));

            if ($key === 'allowCamera' && !empty($restrictions['camera_whitelist_enabled'])) {
                $lines[] = self::buildCameraWhitelistLines(
                    (string) ($restrictions['allowedCameraRestrictionBundleIDs'] ?? '')
                );
            }
        }

        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>功能限制</string>";
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>设备功能限制</string>";
        $lines[] = "\t<key>PayloadIdentifier</key>";
        $lines[] = "\t<string>" . self::escape($identifier) . '</string>';
        $lines[] = "\t<key>PayloadOrganization</key>";
        $lines[] = "\t<string>" . self::escape($org) . '</string>';
        $lines[] = "\t<key>PayloadType</key>";
        $lines[] = "\t<string>Configuration</string>";
        $lines[] = "\t<key>PayloadUUID</key>";
        $lines[] = "\t<string>" . $profileUuid . '</string>';
        $lines[] = "\t<key>PayloadVersion</key>";
        $lines[] = "\t<integer>1</integer>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function buildCameraWhitelistLines(string $bundleIdsText): string
    {
        $bundleIds = policyParseLines($bundleIdsText);
        if (empty($bundleIds)) {
            return '';
        }

        $lines = [];
        $lines[] = "\t\t\t<key>allowedCameraRestrictionBundleIDs</key>";
        $lines[] = "\t\t\t<array>";
        foreach ($bundleIds as $bundleId) {
            $lines[] = "\t\t\t\t<string>" . self::escape($bundleId) . '</string>';
        }
        $lines[] = "\t\t\t</array>";

        return implode("\n", $lines);
    }

    private static function boolLine(string $key, bool $value): string
    {
        return "\t\t\t<key>" . $key . "</key>\n\t\t\t" . ($value ? '<true/>' : '<false/>');
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return strtoupper(vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        ));
    }
}
