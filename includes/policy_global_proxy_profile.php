<?php
/**
 * 策略配置：全局 HTTP 代理描述文件
 */

require_once __DIR__ . '/policy.php';

class PolicyGlobalProxyProfile
{
    public static function validateConfig(): array
    {
        $org = PolicyConfig::orgName(PolicyConfig::get('proxy_org_name'));
        $identifier = trim(PolicyConfig::get('proxy_identifier'));
        $pacUrl = trim(PolicyConfig::get('proxy_pac_url'));

        if ($org === '') {
            return ['ok' => false, 'msg' => '全局代理机构名称未设置'];
        }
        if ($identifier === '') {
            return ['ok' => false, 'msg' => '全局代理配置标识未设置'];
        }
        if ($pacUrl === '') {
            return ['ok' => false, 'msg' => '全局代理 ProxyPACURL 未设置'];
        }
        if (!policyIsUrl($pacUrl)) {
            return ['ok' => false, 'msg' => '全局代理 ProxyPACURL 无效'];
        }

        return ['ok' => true];
    }

    public static function buildProfileFrom(array $config): string
    {
        $check = self::validateConfigFrom($config);
        if (!$check['ok']) {
            throw new InvalidArgumentException($check['msg']);
        }

        $org = PolicyConfig::orgName(trim((string) ($config['proxy_org_name'] ?? '')));
        $identifier = trim((string) ($config['proxy_identifier'] ?? ''));
        $pacUrl = trim((string) ($config['proxy_pac_url'] ?? ''));

        $proxyUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $proxyUuid) {
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
        $lines[] = "\t\t\t<string>全局 HTTP 代理</string>";
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>全局 HTTP 代理</string>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>com.apple.proxy.http.global." . $proxyUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.proxy.http.global</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $proxyUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t\t<key>ProxyCaptiveLoginAllowed</key>";
        $lines[] = "\t\t\t<false/>";
        $lines[] = "\t\t\t<key>ProxyPACFallbackAllowed</key>";
        $lines[] = "\t\t\t<false/>";
        $lines[] = "\t\t\t<key>ProxyPACURL</key>";
        $lines[] = "\t\t\t<string>" . self::escape($pacUrl) . '</string>';
        $lines[] = "\t\t\t<key>ProxyType</key>";
        $lines[] = "\t\t\t<string>Auto</string>";
        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>全局 HTTP 代理</string>";
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>全局 HTTP 代理</string>";
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
        $org = PolicyConfig::orgName(trim((string) ($config['proxy_org_name'] ?? '')));
        $identifier = trim((string) ($config['proxy_identifier'] ?? ''));
        $pacUrl = trim((string) ($config['proxy_pac_url'] ?? ''));

        if ($org === '') {
            return ['ok' => false, 'msg' => '全局代理机构名称未设置'];
        }
        if ($identifier === '') {
            return ['ok' => false, 'msg' => '全局代理配置标识未设置'];
        }
        if ($pacUrl === '') {
            return ['ok' => false, 'msg' => '全局代理 ProxyPACURL 未设置'];
        }
        if (!policyIsUrl($pacUrl)) {
            return ['ok' => false, 'msg' => '全局代理 ProxyPACURL 无效'];
        }

        return ['ok' => true];
    }

    public static function buildProfile(): string
    {
        $check = self::validateConfig();
        if (!$check['ok']) {
            throw new InvalidArgumentException($check['msg']);
        }

        $org = PolicyConfig::orgName(PolicyConfig::get('proxy_org_name'));
        $identifier = trim(PolicyConfig::get('proxy_identifier'));
        $pacUrl = trim(PolicyConfig::get('proxy_pac_url'));

        $proxyUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $proxyUuid) {
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
        $lines[] = "\t\t\t<string>全局 HTTP 代理</string>";
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>全局 HTTP 代理</string>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>com.apple.proxy.http.global." . $proxyUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.proxy.http.global</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $proxyUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t\t<key>ProxyCaptiveLoginAllowed</key>";
        $lines[] = "\t\t\t<false/>";
        $lines[] = "\t\t\t<key>ProxyPACFallbackAllowed</key>";
        $lines[] = "\t\t\t<false/>";
        $lines[] = "\t\t\t<key>ProxyPACURL</key>";
        $lines[] = "\t\t\t<string>" . self::escape($pacUrl) . '</string>';
        $lines[] = "\t\t\t<key>ProxyType</key>";
        $lines[] = "\t\t\t<string>Auto</string>";
        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>全局 HTTP 代理</string>";
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>全局 HTTP 代理</string>";
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
