<?php
/**
 * Apple 激活锁 Escrow Key Unlock
 */

class AppleEscrowClient
{
    private const UNLOCK_URL = 'https://deviceservices-external.apple.com/deviceservicesworkers/escrowKeyUnlock';

    public static function unlock(array $params): array
    {
        $pemCert = trim((string) ($params['pem_cert'] ?? ''));
        $pemKey = trim((string) ($params['pem_private_key'] ?? ''));
        if ($pemCert === '' || $pemKey === '') {
            return ['ok' => false, 'msg' => 'APNS 证书或私钥为空'];
        }

        $postFields = [
            'productType' => (string) ($params['product_type'] ?? ''),
            'serial'      => (string) ($params['serial'] ?? ''),
            'imei'        => (string) ($params['imei'] ?? ''),
            'imei2'       => (string) ($params['imei2'] ?? ''),
            'meid'        => (string) ($params['meid'] ?? ''),
            'escrowKey'   => (string) ($params['escrow_key'] ?? ''),
            'orgName'     => (string) ($params['org_name'] ?? ''),
            'guid'        => (string) ($params['guid'] ?? ''),
        ];

        $certFile = self::writeTempPem($pemCert, 'apns_cert_');
        $keyFile = self::writeTempPem($pemKey, 'apns_key_');

        try {
            $ch = curl_init(self::UNLOCK_URL);
            if ($ch === false) {
                return ['ok' => false, 'msg' => '无法初始化请求'];
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($postFields),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_SSLCERT        => $certFile,
                CURLOPT_SSLKEY         => $keyFile,
                CURLOPT_SSLCERTTYPE    => 'PEM',
                CURLOPT_SSLKEYTYPE     => 'PEM',
                CURLOPT_TIMEOUT        => 60,
            ]);
            self::applyCurlSsl($ch);

            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno !== 0) {
                return ['ok' => false, 'msg' => '请求失败：' . $error, 'http_code' => $httpCode];
            }

            if ($httpCode === 200) {
                return ['ok' => true, 'msg' => '关闭激活锁成功', 'http_code' => $httpCode];
            }

            $msg = trim((string) $body);
            if ($msg === '') {
                $msg = 'HTTP ' . $httpCode;
            }

            return ['ok' => false, 'msg' => $msg, 'http_code' => $httpCode];
        } finally {
            if ($certFile !== '' && is_file($certFile)) {
                @unlink($certFile);
            }
            if ($keyFile !== '' && is_file($keyFile)) {
                @unlink($keyFile);
            }
        }
    }

    private static function writeTempPem(string $content, string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        if ($path === false) {
            throw new RuntimeException('无法创建临时证书文件');
        }
        if (file_put_contents($path, $content) === false) {
            @unlink($path);
            throw new RuntimeException('无法写入临时证书文件');
        }
        return $path;
    }

    /**
     * SSL 选项 - Windows 环境常缺 CA 根证书，默认跳过验证
     */
    private static function applyCurlSsl($ch): void
    {
        require_once __DIR__ . '/dep.php';

        $verify = DepConfig::getBool('dep_ssl_verify');
        if ($verify) {
            $caFile = dirname(__DIR__) . '/config/cacert.pem';
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (is_file($caFile)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caFile);
            }
            return;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
}
