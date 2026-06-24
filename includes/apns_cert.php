<?php
/**
 * APNS 推送证书管理（证书与私钥存数据库）
 */

require_once __DIR__ . '/db.php';

class ApnsCert
{
    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `apns_certificates` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cert_remark` VARCHAR(255) NOT NULL DEFAULT '',
            `pem_cert` TEXT,
            `pem_private_key` TEXT,
            `topic` VARCHAR(255) NOT NULL DEFAULT '',
            `subject` VARCHAR(500) DEFAULT '',
            `issuer` VARCHAR(500) DEFAULT '',
            `serial_number` VARCHAR(128) DEFAULT '',
            `not_before` DATETIME DEFAULT NULL,
            `not_after` DATETIME DEFAULT NULL,
            `fingerprint` VARCHAR(128) DEFAULT '',
            `user_id` INT UNSIGNED DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::migrateColumns();
    }

    private static function migrateColumns(): void
    {
        try {
            $cols = DB::fetchAll('SHOW COLUMNS FROM apns_certificates');
        } catch (Exception $e) {
            return;
        }

        $names = array_column($cols, 'Field');
        if (!in_array('cert_remark', $names, true)) {
            DB::execute("ALTER TABLE apns_certificates ADD COLUMN `cert_remark` VARCHAR(255) NOT NULL DEFAULT '' AFTER `id`");
        }
        if (!in_array('pem_cert', $names, true)) {
            DB::execute('ALTER TABLE apns_certificates ADD COLUMN `pem_cert` TEXT AFTER `cert_remark`');
        }
        if (!in_array('pem_private_key', $names, true)) {
            DB::execute('ALTER TABLE apns_certificates ADD COLUMN `pem_private_key` TEXT AFTER `pem_cert`');
        }
    }

    public static function listAll(): array
    {
        self::ensureTables();
        $rows = DB::fetchAll(
            'SELECT id, cert_remark, topic, subject, issuer, serial_number, not_before, not_after,
                    fingerprint, created_at, updated_at
             FROM apns_certificates
             WHERE pem_cert IS NOT NULL AND pem_cert != \'\'
               AND pem_private_key IS NOT NULL AND pem_private_key != \'\'
             ORDER BY id DESC'
        );
        $list = [];
        foreach ($rows as $row) {
            $list[] = self::formatRow($row);
        }
        return $list;
    }

    public static function getById(int $id): ?array
    {
        self::ensureTables();
        $row = DB::fetchOne(
            'SELECT id, cert_remark, topic, subject, issuer, serial_number, not_before, not_after,
                    fingerprint, created_at, updated_at
             FROM apns_certificates WHERE id = ? LIMIT 1',
            [$id]
        );
        if (!$row) {
            return null;
        }
        return self::formatRow($row);
    }

    public static function save(string $remark, string $pemCert, string $pemPrivateKey, int $userId): array
    {
        self::ensureTables();

        $remark = trim($remark);
        $pemCert = trim($pemCert);
        $pemPrivateKey = trim($pemPrivateKey);

        if ($remark === '') {
            throw new InvalidArgumentException('请填写证书备注');
        }
        if ($pemCert === '') {
            throw new InvalidArgumentException('请填写 PEM 证书');
        }
        if ($pemPrivateKey === '') {
            throw new InvalidArgumentException('请填写 PEM 私钥');
        }
        if (strpos($pemCert, 'BEGIN CERTIFICATE') === false) {
            throw new InvalidArgumentException('PEM 证书格式不正确，需包含 BEGIN CERTIFICATE');
        }
        if (strpos($pemPrivateKey, 'BEGIN PRIVATE KEY') === false
            && strpos($pemPrivateKey, 'BEGIN RSA PRIVATE KEY') === false
            && strpos($pemPrivateKey, 'BEGIN ENCRYPTED PRIVATE KEY') === false) {
            throw new InvalidArgumentException('PEM 私钥格式不正确，需包含 BEGIN PRIVATE KEY');
        }

        $meta = self::parseCertMeta($pemCert);

        DB::execute(
            'INSERT INTO apns_certificates (cert_remark, pem_cert, pem_private_key, topic, subject, issuer,
             serial_number, not_before, not_after, fingerprint, user_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $remark,
                $pemCert,
                $pemPrivateKey,
                $meta['topic'],
                $meta['subject'],
                $meta['issuer'],
                $meta['serial_number'],
                $meta['not_before'],
                $meta['not_after'],
                $meta['fingerprint'],
                $userId,
            ]
        );

        $id = (int)DB::lastId();
        return self::getById($id) ?? [];
    }

    public static function findPemByTopic(string $topic): ?array
    {
        self::ensureTables();

        $topic = trim($topic);
        if ($topic === '') {
            return null;
        }

        $row = DB::fetchOne(
            'SELECT id, cert_remark, topic, pem_cert, pem_private_key
             FROM apns_certificates
             WHERE topic = ?
               AND pem_cert IS NOT NULL AND pem_cert != \'\'
               AND pem_private_key IS NOT NULL AND pem_private_key != \'\'
             ORDER BY id DESC
             LIMIT 1',
            [$topic]
        );

        if (!$row) {
            return null;
        }

        return [
            'id'              => (int) ($row['id'] ?? 0),
            'cert_remark'     => (string) ($row['cert_remark'] ?? ''),
            'topic'           => (string) ($row['topic'] ?? ''),
            'pem_cert'        => trim((string) ($row['pem_cert'] ?? '')),
            'pem_private_key' => trim((string) ($row['pem_private_key'] ?? '')),
        ];
    }

    public static function getPemBundle(int $id): ?string
    {
        self::ensureTables();
        $row = DB::fetchOne(
            'SELECT pem_cert, pem_private_key FROM apns_certificates WHERE id = ? LIMIT 1',
            [$id]
        );
        if (!$row || trim($row['pem_cert'] ?? '') === '' || trim($row['pem_private_key'] ?? '') === '') {
            return null;
        }
        return trim($row['pem_cert']) . "\n" . trim($row['pem_private_key']);
    }

    public static function deleteById(int $id): void
    {
        self::ensureTables();
        if ($id <= 0) {
            throw new InvalidArgumentException('无效的证书 ID');
        }
        $row = DB::fetchOne('SELECT id, cert_remark FROM apns_certificates WHERE id = ? LIMIT 1', [$id]);
        if (!$row) {
            throw new InvalidArgumentException('证书不存在');
        }
        DB::execute('DELETE FROM apns_certificates WHERE id = ?', [$id]);
    }

    public static function parseCertMeta(string $pemCert): array
    {
        $pemCert = trim($pemCert);
        if (!preg_match_all('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $pemCert, $matches)) {
            throw new InvalidArgumentException('未找到有效的 X.509 证书');
        }

        $pushCert = null;
        foreach ($matches[0] as $block) {
            $x509 = openssl_x509_read($block);
            if (!$x509) {
                continue;
            }
            $info = openssl_x509_parse($x509);
            if (!$info) {
                continue;
            }
            $subject = self::formatName($info['subject'] ?? []);
            $issuer = self::formatName($info['issuer'] ?? []);
            $uid = $info['subject']['UID'] ?? ($info['extensions']['subjectAltName'] ?? '');
            if (is_string($uid) && strpos($uid, 'com.apple.mgmt') !== false) {
                $pushCert = ['x509' => $x509, 'info' => $info, 'subject' => $subject, 'issuer' => $issuer, 'uid' => $uid];
                break;
            }
            if ($pushCert === null) {
                $pushCert = ['x509' => $x509, 'info' => $info, 'subject' => $subject, 'issuer' => $issuer, 'uid' => is_string($uid) ? $uid : ''];
            }
        }

        if ($pushCert === null) {
            throw new InvalidArgumentException('无法解析证书信息');
        }

        $info = $pushCert['info'];
        $topic = $pushCert['uid'];
        if ($topic === '' && !empty($info['subject']['CN'])) {
            $topic = $info['subject']['CN'];
        }

        $fingerprint = openssl_x509_fingerprint($pushCert['x509'], 'sha1');
        if ($fingerprint === false) {
            $fingerprint = '';
        }

        return [
            'topic'         => $topic,
            'subject'       => $pushCert['subject'],
            'issuer'        => $pushCert['issuer'],
            'serial_number' => isset($info['serialNumber']) ? (string)$info['serialNumber'] : '',
            'not_before'    => isset($info['validFrom_time_t']) ? date('Y-m-d H:i:s', $info['validFrom_time_t']) : null,
            'not_after'     => isset($info['validTo_time_t']) ? date('Y-m-d H:i:s', $info['validTo_time_t']) : null,
            'fingerprint'   => strtoupper(str_replace(':', '', $fingerprint)),
        ];
    }

    private static function formatRow(array $row): array
    {
        $daysLeft = null;
        $status = 'unknown';
        if (!empty($row['not_after'])) {
            $expireTs = strtotime($row['not_after']);
            $daysLeft = (int)floor(($expireTs - time()) / 86400);
            if ($daysLeft < 0) {
                $status = 'expired';
            } elseif ($daysLeft <= 30) {
                $status = 'expiring';
            } else {
                $status = 'valid';
            }
        }

        return [
            'id'            => (int)$row['id'],
            'cert_remark'   => $row['cert_remark'] ?? '',
            'topic'         => $row['topic'],
            'subject'       => $row['subject'],
            'issuer'        => $row['issuer'],
            'serial_number' => $row['serial_number'],
            'not_before'    => $row['not_before'],
            'not_after'     => $row['not_after'],
            'fingerprint'   => $row['fingerprint'],
            'days_left'     => $daysLeft,
            'status'        => $status,
            'status_text'   => self::statusText($status, $daysLeft),
            'created_at'    => $row['created_at'],
            'updated_at'    => $row['updated_at'],
        ];
    }

    private static function statusText(string $status, ?int $daysLeft): string
    {
        if ($status === 'expired') {
            return '已过期';
        }
        if ($status === 'expiring') {
            return '即将过期（剩余 ' . max(0, (int)$daysLeft) . ' 天）';
        }
        if ($status === 'valid') {
            return '有效（剩余 ' . (int)$daysLeft . ' 天）';
        }
        return '未知';
    }

    private static function formatName(array $parts): string
    {
        $order = ['CN', 'O', 'OU', 'C'];
        $items = [];
        foreach ($order as $key) {
            if (!empty($parts[$key])) {
                $items[] = $key . '=' . $parts[$key];
            }
        }
        foreach ($parts as $key => $val) {
            if (!in_array($key, $order, true) && is_string($val)) {
                $items[] = $key . '=' . $val;
            }
        }
        return implode(', ', $items);
    }
}
