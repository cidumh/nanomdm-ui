<?php
/**
 * 数据库配置文件读写
 */

require_once __DIR__ . '/bootstrap.php';

function writeDbConfigFile(string $host, int $port, string $dbname, string $user, string $pass): bool
{
    $dir = ROOT_PATH . '/config';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $content = "<?php\nreturn [\n"
        . "    'host'   => " . var_export($host, true) . ",\n"
        . "    'port'   => " . var_export($port, true) . ",\n"
        . "    'dbname' => " . var_export($dbname, true) . ",\n"
        . "    'user'   => " . var_export($user, true) . ",\n"
        . "    'pass'   => " . var_export($pass, true) . ",\n"
        . "];\n";

    return file_put_contents(CONFIG_FILE, $content) !== false;
}

function testDbConnection(string $host, int $port, string $dbname, string $user, string $pass): bool
{
    try {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        return false;
    }
}
