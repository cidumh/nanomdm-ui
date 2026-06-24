<?php
/**
 * 将本地配置文件或证书规范化为 InstallProfile 可用的描述文件内容
 */

class ProfileInstallPayload
{
    public static function normalize(string $raw, string $filename = ''): string
    {
        if ($raw === '') {
            throw new InvalidArgumentException('请选择配置文件');
        }

        $filename = trim($filename);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (self::isConfigurationProfile($raw, $ext)) {
            return $raw;
        }

        if (self::isPemCertificate($raw) || (in_array($ext, ['pem', 'crt'], true) && self::looksLikePem($raw))) {
            return self::wrapPemCertificate(trim($raw), $filename);
        }

        if (self::isDerCertificate($raw) || self::isLikelyDerCertificate($raw, $ext)) {
            return self::wrapDerCertificate($raw, $filename);
        }

        if (self::looksLikeSignedProfile($raw, $ext)) {
            return $raw;
        }

        throw new InvalidArgumentException('配置文件内容无效，请选择 mobileconfig、plist 或证书文件（cer/pem/crt）');
    }

    private static function isConfigurationProfile(string $raw, string $ext): bool
    {
        if (stripos($raw, '<plist') !== false) {
            return true;
        }

        return in_array($ext, ['mobileconfig', 'plist', 'xml'], true)
            && stripos($raw, '<?xml') !== false;
    }

    private static function isPemCertificate(string $raw): bool
    {
        return (bool) preg_match('/-----BEGIN CERTIFICATE-----/s', $raw);
    }

    private static function looksLikePem(string $raw): bool
    {
        $trimmed = ltrim($raw);
        return strncmp($trimmed, '-----BEGIN', 10) === 0;
    }

    private static function isDerCertificate(string $raw): bool
    {
        if ($raw === '' || ord($raw[0]) !== 0x30) {
            return false;
        }

        if (!function_exists('openssl_x509_read')) {
            return strlen($raw) > 64;
        }

        $pem = self::derToPem($raw);

        return openssl_x509_read($pem) !== false;
    }

    private static function isLikelyDerCertificate(string $raw, string $ext): bool
    {
        if (!in_array($ext, ['cer', 'der', 'crt'], true)) {
            return false;
        }

        return $raw !== '' && ord($raw[0]) === 0x30 && !self::isPemCertificate($raw);
    }

    private static function looksLikeSignedProfile(string $raw, string $ext): bool
    {
        if ($ext === 'mobileconfig') {
            return true;
        }

        return $raw !== '' && ord($raw[0]) === 0x30 && !self::isDerCertificate($raw);
    }

    private static function wrapPemCertificate(string $pem, string $filename): string
    {
        $pem = trim($pem);
        if (!self::isPemCertificate($pem)) {
            throw new InvalidArgumentException('证书内容无效');
        }

        $displayName = self::certificateDisplayName($pem, $filename);
        $identifier = self::certificateIdentifier($pem);
        $fileName = self::certificateFileName($filename, 'pem');

        return self::buildCertificateProfile(
            $displayName,
            $identifier,
            $fileName,
            'com.apple.security.pem',
            $pem
        );
    }

    private static function wrapDerCertificate(string $der, string $filename): string
    {
        if (!self::isDerCertificate($der) && !self::isLikelyDerCertificate($der, strtolower(pathinfo($filename, PATHINFO_EXTENSION)))) {
            throw new InvalidArgumentException('证书内容无效');
        }

        $pem = self::derToPem($der);
        $displayName = self::certificateDisplayName($pem, $filename);
        $identifier = self::certificateIdentifier($der);
        $fileName = self::certificateFileName($filename, 'cer');

        return self::buildCertificateProfile(
            $displayName,
            $identifier,
            $fileName,
            'com.apple.security.root',
            $der
        );
    }

    private static function buildCertificateProfile(
        string $displayName,
        string $identifier,
        string $fileName,
        string $payloadType,
        string $payloadContent
    ): string {
        $payloadUuid = self::generateUuid();
        $profileUuid = self::generateUuid();
        while ($profileUuid === $payloadUuid) {
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
        $lines[] = "\t\t\t<key>PayloadCertificateFileName</key>";
        $lines[] = "\t\t\t<string>" . self::escape($fileName) . '</string>';
        $lines[] = self::dataElement("\t\t\t", 'PayloadContent', $payloadContent);
        $lines[] = "\t\t\t<key>PayloadDescription</key>";
        $lines[] = "\t\t\t<string>" . self::escape($displayName) . '</string>';
        $lines[] = "\t\t\t<key>PayloadDisplayName</key>";
        $lines[] = "\t\t\t<string>" . self::escape($displayName) . '</string>';
        $lines[] = "\t\t\t<key>PayloadIdentifier</key>";
        $lines[] = "\t\t\t<string>" . self::escape($identifier) . '</string>';
        $lines[] = "\t\t\t<key>PayloadType</key>";
        $lines[] = "\t\t\t<string>" . self::escape($payloadType) . '</string>';
        $lines[] = "\t\t\t<key>PayloadUUID</key>";
        $lines[] = "\t\t\t<string>" . $payloadUuid . '</string>';
        $lines[] = "\t\t\t<key>PayloadVersion</key>";
        $lines[] = "\t\t\t<integer>1</integer>";
        $lines[] = "\t\t</dict>";
        $lines[] = "\t</array>";
        $lines[] = "\t<key>PayloadDescription</key>";
        $lines[] = "\t<string>" . self::escape($displayName) . '</string>';
        $lines[] = "\t<key>PayloadDisplayName</key>";
        $lines[] = "\t<string>" . self::escape($displayName) . '</string>';
        $lines[] = "\t<key>PayloadIdentifier</key>";
        $lines[] = "\t<string>" . self::escape($identifier) . '</string>';
        $lines[] = "\t<key>PayloadRemovalDisallowed</key>";
        $lines[] = "\t<false/>";
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

    private static function certificateDisplayName(string $pem, string $filename): string
    {
        if (function_exists('openssl_x509_read') && function_exists('openssl_x509_parse')) {
            $cert = openssl_x509_read($pem);
            if ($cert !== false) {
                $parsed = openssl_x509_parse($cert);
                if (is_array($parsed)) {
                    $cn = trim((string) ($parsed['subject']['CN'] ?? ''));
                    if ($cn !== '') {
                        return $cn;
                    }
                }
            }
        }

        $base = trim(basename($filename));
        if ($base !== '' && $base !== '.' && $base !== '..') {
            return preg_replace('/\.[^.]+$/', '', $base) ?: $base;
        }

        return 'Certificate';
    }

    private static function certificateIdentifier(string $sourceBytes): string
    {
        return hash('sha256', $sourceBytes);
    }

    private static function certificateFileName(string $filename, string $defaultExt): string
    {
        $base = trim(basename($filename));
        if ($base !== '' && $base !== '.' && $base !== '..') {
            return $base;
        }

        return 'certificate.' . $defaultExt;
    }

    private static function derToPem(string $der): string
    {
        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END CERTIFICATE-----\n";
    }

    private static function dataElement(string $indent, string $key, string $binary): string
    {
        $wrapped = chunk_split(base64_encode($binary), 76, "\n");

        return $indent . '<key>' . $key . "</key>\n"
            . $indent . "<data>\n"
            . $wrapped
            . $indent . '</data>';
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
