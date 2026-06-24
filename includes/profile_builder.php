<?php
/**
 * 根据描述文件配置生成 Apple mobileconfig (plist XML)
 */

require_once __DIR__ . '/profile.php';

class ProfileBuilder
{
    public static function isReady(array $config): bool
    {
        if (trim($config['mdm_server_url'] ?? '') === '') {
            return false;
        }
        if (trim($config['apns_topic_id'] ?? '') === '') {
            return false;
        }
        if (!empty($config['user_agreement_enabled']) && trim($config['user_agreement_content'] ?? '') === '') {
            return false;
        }
        if (!empty($config['scep_enabled']) && trim($config['scep_url'] ?? '') === '') {
            return false;
        }
        return true;
    }

    public static function readinessMessage(array $config): string
    {
        if (trim($config['mdm_server_url'] ?? '') === '') {
            return '请先在后台配置 MDM ServerURL';
        }
        if (trim($config['apns_topic_id'] ?? '') === '') {
            return '请先在后台配置 APNS Topic ID';
        }
        if (!empty($config['user_agreement_enabled']) && trim($config['user_agreement_content'] ?? '') === '') {
            return '已开启用户协议但未填写协议内容';
        }
        if (!empty($config['scep_enabled']) && trim($config['scep_url'] ?? '') === '') {
            return '已开启 SCEP 但未填写 SCEP URL 地址';
        }
        return '';
    }

    public static function build(array $config): string
    {
        if (!self::isReady($config)) {
            throw new InvalidArgumentException(self::readinessMessage($config) ?: '描述文件配置不完整');
        }

        $uuids = self::generateUniqueUuids(!empty($config['scep_enabled']) ? 3 : 2);
        $profileUuid = $uuids[0];
        $mdmUuid = $uuids[1];
        $scepUuid = !empty($config['scep_enabled']) ? $uuids[2] : null;

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $lines[] = '<plist version="1.0">';
        $lines[] = '<dict>';

        if (!empty($config['user_agreement_enabled'])) {
            $lines[] = self::buildConsentText($config['user_agreement_content']);
        }

        $lines[] = self::buildPayloadContent($config, $mdmUuid, $scepUuid);
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>" . self::escape($config['profile_description']) . '</string>';
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>" . self::escape($config['profile_name']) . '</string>';
        $lines[] = "\t<key>PayloadIdentifier</key>";
        $lines[] = "\t<string>" . self::escape($config['profile_identifier']) . '</string>';
        $lines[] = "\t<key>PayloadOrganization</key>";
        $lines[] = "\t<string>" . self::escape($config['org_name']) . '</string>';
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

    private static function buildConsentText(string $content): string
    {
        return "\t<key>ConsentText</key>\n"
            . "\t<dict>\n"
            . "\t\t<key>default</key>\n"
            . "\t\t<string>\n"
            . self::escape($content) . "\n"
            . "\t\t</string>\n"
            . "\t</dict>";
    }

    private static function buildPayloadContent(array $config, string $mdmUuid, ?string $scepUuid): string
    {
        $items = [];
        if (!empty($config['scep_enabled']) && $scepUuid !== null) {
            $items[] = self::buildScepPayload($config, $scepUuid);
        }
        $items[] = self::buildMdmPayload($config, $mdmUuid, $scepUuid);

        return "\t<key>PayloadContent</key>\n\t<array>\n"
            . implode("\n", $items) . "\n"
            . "\t</array>";
    }

    private static function buildScepPayload(array $config, string $scepUuid): string
    {
        $lines = [];
        $lines[] = "\t\t<dict>";
        $lines[] = "\t\t\t<key>PayloadContent</key>";
        $lines[] = "\t\t\t<dict>";
        $lines[] = "\t\t\t\t<key>Key Type</key>";
        $lines[] = "\t\t\t\t<string>RSA</string>";

        $challenge = trim($config['scep_challenge'] ?? '');
        if ($challenge !== '') {
            $lines[] = "\t\t\t\t<key>Challenge</key>";
            $lines[] = "\t\t\t\t<string>" . self::escape($challenge) . '</string>';
        }

        $lines[] = "\t\t\t\t<key>Key Usage</key>";
        $lines[] = "\t\t\t\t<integer>5</integer>";
        $lines[] = "\t\t\t\t<key>Keysize</key>";
        $lines[] = "\t\t\t\t<integer>2048</integer>";
        $lines[] = "\t\t\t\t<key>URL</key>";
        $lines[] = "\t\t\t\t<string>" . self::escape($config['scep_url']) . '</string>';
        $lines[] = "\t\t\t</dict>";
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>" . self::escape($config['scep_identifier']) . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.security.scep</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $scepUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t</dict>";

        return implode("\n", $lines);
    }

    private static function buildMdmPayload(array $config, string $mdmUuid, ?string $scepUuid): string
    {
        $lines = [];
        $lines[] = "\t\t<dict>";
        $lines[] = "\t\t\t<key>AccessRights</key>";
        $lines[] = "\t\t\t<integer>8191</integer>";
        $lines[] = "\t\t\t<key>CheckOutWhenRemoved</key>";
        $lines[] = "\t\t\t<true/>";

        if ($scepUuid !== null) {
            $lines[] = "\t\t\t<key>IdentityCertificateUUID</key>";
            $lines[] = "\t\t\t<string>" . $scepUuid . '</string>';
        }

        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>" . self::escape($config['mdm_payload_identifier']) . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>com.apple.mdm</string>";
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $mdmUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t\t<key>ServerCapabilities</key>";
        $lines[] = "\t\t\t<array>";
        $lines[] = "\t\t\t\t<string>com.apple.mdm.per-user-connections</string>";
        $lines[] = "\t\t\t\t<string>com.apple.mdm.bootstraptoken</string>";
        $lines[] = "\t\t\t\t<string>com.apple.mdm.token</string>";
        $lines[] = "\t\t\t</array>";
        $lines[] = "\t\t\t<key>ServerURL</key>";
        $lines[] = "\t\t\t<string>" . self::escape($config['mdm_server_url']) . '</string>';
        $lines[] = "\t\t\t<key>SignMessage</key>";
        $lines[] = "\t\t\t<true/>";
        $lines[] = "\t\t\t<key>Topic</key>";
        $lines[] = "\t\t\t<string>" . self::escape($config['apns_topic_id']) . '</string>';
        $lines[] = "\t\t</dict>";

        return implode("\n", $lines);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function generateUniqueUuids(int $count): array
    {
        $uuids = [];
        while (count($uuids) < $count) {
            $uuid = self::generateUuid();
            if (!in_array($uuid, $uuids, true)) {
                $uuids[] = $uuid;
            }
        }
        return $uuids;
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
