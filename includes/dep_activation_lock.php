<?php
/**
 * DEP 激活锁绕过码与本地存储
 */

require_once __DIR__ . '/db.php';

class DepActivationLock
{
    public static function ensureTables(): void
    {
        DB::execute("CREATE TABLE IF NOT EXISTS `dep_activation_lock` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `serial_number` VARCHAR(64) NOT NULL,
            `bypass_code` VARCHAR(64) NOT NULL,
            `escrow_key` VARCHAR(64) NOT NULL,
            `lost_message` VARCHAR(500) DEFAULT '',
            `user_id` INT UNSIGNED DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_serial` (`serial_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function save(string $serial, string $bypassCode, string $escrowKey, string $lostMessage, ?int $userId = null): void
    {
        self::ensureTables();
        $serial = trim($serial);

        $exists = DB::fetchOne(
            'SELECT id FROM dep_activation_lock WHERE serial_number = ? LIMIT 1',
            [$serial]
        );

        if ($exists) {
            DB::execute(
                'UPDATE dep_activation_lock SET bypass_code=?, escrow_key=?, lost_message=?, user_id=?, updated_at=NOW()
                 WHERE serial_number=?',
                [$bypassCode, $escrowKey, $lostMessage, $userId, $serial]
            );
        } else {
            DB::execute(
                'INSERT INTO dep_activation_lock (serial_number, bypass_code, escrow_key, lost_message, user_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                [$serial, $bypassCode, $escrowKey, $lostMessage, $userId]
            );
        }
    }

    public static function getBySerial(string $serial): ?array
    {
        self::ensureTables();
        $row = DB::fetchOne(
            'SELECT serial_number, bypass_code, escrow_key, lost_message, created_at, updated_at
             FROM dep_activation_lock WHERE serial_number = ? LIMIT 1',
            [trim($serial)]
        );
        return $row ?: null;
    }

    public static function formatLogDetail(string $serial, string $bypassCode, string $escrowKey, string $extra = ''): string
    {
        $detail = 'serial=' . $serial
            . ' bypass_code=' . $bypassCode
            . ' escrow_key=' . $escrowKey;
        if ($extra !== '') {
            $detail .= ' ' . $extra;
        }
        return $detail;
    }
}

/**
 * 8-bit 到 5-bit 转换（与 micromdm activationlock 一致）
 */
function depConvertBits(array $data, int $fromBits, int $toBits): array
{
    $ret = [];
    $acc = 0;
    $bits = 0;
    $maxv = (1 << $toBits) - 1;

    foreach ($data as $value) {
        if ($value < 0 || ($value >> $fromBits) !== 0) {
            throw new InvalidArgumentException('invalid data range');
        }
        $acc = ($acc << $fromBits) | $value;
        $bits += $fromBits;
        while ($bits >= $toBits) {
            $bits -= $toBits;
            $ret[] = ($acc >> $bits) & $maxv;
        }
    }

    if ($bits > 0) {
        for ($bit = $fromBits; $bit >= $bits; $bit--) {
            $acc &= ~(1 << $bit);
        }
        $ret[] = $acc & $maxv;
    }

    return $ret;
}

/**
 * 按 Apple / micromdm 算法生成 bypass code 与 escrow_key（PBKDF2-HMAC-SHA256，小写 hex）
 *
 * @return array{bypass_code: string, escrow_key: string}
 */
function depGenerateActivationLockBypass(): array
{
    $symbols = '0123456789ACDEFGHJKLMNPQRTUVWXYZ';
    $dashPositions = [5, 10, 14, 18, 22];

    $rawBytes = random_bytes(16);
    $salt = str_repeat("\0", 4);
    $hash = hash_pbkdf2('sha256', $rawBytes, $salt, 50000, 32, true);
    $escrowKey = bin2hex($hash);

    $bytes = array_values(unpack('C*', $rawBytes));
    $values = depConvertBits($bytes, 8, 5);

    $code = '';
    $dashIdx = 0;
    foreach ($values as $i => $p) {
        if ($dashIdx < count($dashPositions) && $i === $dashPositions[$dashIdx]) {
            $code .= '-';
            $dashIdx++;
        }
        $code .= $symbols[$p];
    }

    return [
        'bypass_code' => $code,
        'escrow_key'  => $escrowKey,
    ];
}
