<?php
/**
 * 策略配置：DNS 代理描述文件与 InstallProfile 指令
 */

require_once __DIR__ . '/policy.php';

class PolicyDnsProfile
{
    public static function validateConfig(): array
    {
        $identifier = trim(PolicyConfig::get('dns_identifier'));
        $serverUrl = PolicyConfig::dnsServerUrl(PolicyConfig::get('dns_server_url'));
        $addr1 = trim(PolicyConfig::get('dns_address_1'));
        $addr2 = trim(PolicyConfig::get('dns_address_2'));

        if ($identifier === '') {
            return ['ok' => false, 'msg' => 'DNS 代理配置标识未设置'];
        }
        if (!policyIsUrl($serverUrl)) {
            return ['ok' => false, 'msg' => 'DNS 代理 ServerURL 无效'];
        }
        if ($addr1 !== '' && !policyIsIpv4($addr1)) {
            return ['ok' => false, 'msg' => 'DNS 代理 ServerAddresses 第 1 个地址必须是 IPv4'];
        }
        if ($addr2 !== '' && !policyIsIpv4($addr2)) {
            return ['ok' => false, 'msg' => 'DNS 代理 ServerAddresses 第 2 个地址必须是 IPv4'];
        }

        return ['ok' => true];
    }

    public static function buildProfileFrom(array $config): string
    {
        $check = self::validateConfigFrom($config);
        if (!$check['ok']) {
            throw new InvalidArgumentException($check['msg']);
        }

        $org = PolicyConfig::orgName(trim((string) ($config['dns_org_name'] ?? '')));
        $identifier = trim((string) ($config['dns_identifier'] ?? ''));
        $serverUrl = PolicyConfig::dnsServerUrl(trim((string) ($config['dns_server_url'] ?? '')));
        $addresses = self::collectAddressesFrom($config);

        $dnsUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $dnsUuid) {
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
        $lines[] = "\t\t\t<string>DNS代理</string>";
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>DNS代理</string>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>com.apple.dnsSettings.managed." . $dnsUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.dnsSettings.managed</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $dnsUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t\t<key>ProhibitDisablement</key>";
        $lines[] = "\t\t\t<true/>";
        $lines[] = "\t\t\t<key>DNSSettings</key>";
        $lines[] = "\t\t\t<dict>";
        $lines[] = "\t\t\t\t<key>DNSProtocol</key>";
        $lines[] = "\t\t\t\t<string>HTTPS</string>";
        $lines[] = "\t\t\t\t<key>ServerURL</key>";
        $lines[] = "\t\t\t\t<string>" . self::escape($serverUrl) . '</string>';
        if (!empty($addresses)) {
            $lines[] = "\t\t\t\t<key>ServerAddresses</key>";
            $lines[] = "\t\t\t\t<array>";
            foreach ($addresses as $address) {
                $lines[] = "\t\t\t\t\t<string>" . self::escape($address) . '</string>';
            }
            $lines[] = "\t\t\t\t</array>";
        }
        $lines[] = "\t\t\t</dict>";
        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>DNS代理</string>";
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>DNS代理</string>";
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
        $lines[] = "\t<key>PayloadRemovalDisallowed</key>";
        $lines[] = "\t<true/>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    public static function validateConfigFrom(array $config): array
    {
        $identifier = trim((string) ($config['dns_identifier'] ?? ''));
        $serverUrl = PolicyConfig::dnsServerUrl(trim((string) ($config['dns_server_url'] ?? '')));
        $addr1 = trim((string) ($config['dns_address_1'] ?? ''));
        $addr2 = trim((string) ($config['dns_address_2'] ?? ''));

        if ($identifier === '') {
            return ['ok' => false, 'msg' => 'DNS 代理配置标识未设置'];
        }
        if (!policyIsUrl($serverUrl)) {
            return ['ok' => false, 'msg' => 'DNS 代理 ServerURL 无效'];
        }
        if ($addr1 !== '' && !policyIsIpv4($addr1)) {
            return ['ok' => false, 'msg' => 'DNS 代理 ServerAddresses 第 1 个地址必须是 IPv4'];
        }
        if ($addr2 !== '' && !policyIsIpv4($addr2)) {
            return ['ok' => false, 'msg' => 'DNS 代理 ServerAddresses 第 2 个地址必须是 IPv4'];
        }

        return ['ok' => true];
    }

    public static function buildProfile(): string
    {
        $check = self::validateConfig();
        if (!$check['ok']) {
            throw new InvalidArgumentException($check['msg']);
        }

        $org = PolicyConfig::orgName(PolicyConfig::get('dns_org_name'));
        $identifier = trim(PolicyConfig::get('dns_identifier'));
        $serverUrl = PolicyConfig::dnsServerUrl(PolicyConfig::get('dns_server_url'));
        $addresses = self::collectAddresses();

        $dnsUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $dnsUuid) {
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
        $lines[] = "\t\t\t<string>DNS代理</string>";
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>DNS代理</string>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>com.apple.dnsSettings.managed." . $dnsUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.dnsSettings.managed</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $dnsUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t\t<key>ProhibitDisablement</key>";
        $lines[] = "\t\t\t<true/>";
        $lines[] = "\t\t\t<key>DNSSettings</key>";
        $lines[] = "\t\t\t<dict>";
        $lines[] = "\t\t\t\t<key>DNSProtocol</key>";
        $lines[] = "\t\t\t\t<string>HTTPS</string>";
        $lines[] = "\t\t\t\t<key>ServerURL</key>";
        $lines[] = "\t\t\t\t<string>" . self::escape($serverUrl) . '</string>';
        if (!empty($addresses)) {
            $lines[] = "\t\t\t\t<key>ServerAddresses</key>";
            $lines[] = "\t\t\t\t<array>";
            foreach ($addresses as $address) {
                $lines[] = "\t\t\t\t\t<string>" . self::escape($address) . '</string>';
            }
            $lines[] = "\t\t\t\t</array>";
        }
        $lines[] = "\t\t\t</dict>";
        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>DNS代理</string>";
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>DNS代理</string>";
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
        $lines[] = "\t<key>PayloadRemovalDisallowed</key>";
        $lines[] = "\t<true/>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    public static function buildInstallCommand(string $profileXml): string
    {
        $payload = base64_encode($profileXml);

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';
        $lines[] = "\t<key>Command</key>";
        $lines[] = "\t<dict>";
        $lines[] = "\t\t<key>Payload</key>";
        $lines[] = "\t\t<data>" . $payload . '</data>';
        $lines[] = "\t\t<key>RequestType</key>";
        $lines[] = "\t\t<string>InstallProfile</string>";
        $lines[] = "\t</dict>";
        $lines[] = "\t<key>CommandUUID</key>";
        $lines[] = "\t<string>(CommandUUID)</string>";
        $lines[] = '</dict>';
        $lines[] = '</plist>';

        return implode("\n", $lines) . "\n";
    }

    private static function collectAddresses(): array
    {
        return self::collectAddressesFrom([]);
    }

    private static function collectAddressesFrom(array $config): array
    {
        $addresses = [];
        $addr1 = trim((string) ($config['dns_address_1'] ?? PolicyConfig::get('dns_address_1')));
        $addr2 = trim((string) ($config['dns_address_2'] ?? PolicyConfig::get('dns_address_2')));

        if ($addr1 !== '') {
            $addresses[] = $addr1;
        }
        if ($addr2 !== '') {
            $addresses[] = $addr2;
        }

        return $addresses;
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
