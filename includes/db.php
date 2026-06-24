<?php
/**
 * 数据库连接 - PDO 参数化查询
 */

require_once __DIR__ . '/bootstrap.php';

class DB
{
    private static $pdo = null;

    public static function reset(): void
    {
        self::$pdo = null;
    }

    /**
     * 获取 PDO 实例
     */
    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $cfg = loadDbConfig();
            if (!$cfg) {
                throw new RuntimeException('数据库尚未配置');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname']
            );

            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /**
     * 安装阶段用 - 不带库名连接
     */
    public static function connectRaw(string $host, int $port, string $user, string $pass): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * 执行查询，返回所有行
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 执行查询，返回单行
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * 执行写入
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * 获取最后插入 ID
     */
    public static function lastId(): string
    {
        return self::get()->lastInsertId();
    }

    /**
     * 判断数据表是否包含指定列（SHOW COLUMNS 不支持占位符）
     */
    public static function tableHasColumn(string $table, string $column): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table) || !preg_match('/^[a-z0-9_]+$/', $column)) {
            return false;
        }
        $cols = self::fetchAll("SHOW COLUMNS FROM `{$table}`");
        foreach ($cols as $col) {
            if (($col['Field'] ?? '') === $column) {
                return true;
            }
        }
        return false;
    }
}
