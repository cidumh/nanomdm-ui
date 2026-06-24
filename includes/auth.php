<?php
/**
 * 认证与会话管理
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

define('TOKEN_COOKIE', 'mdm_token');
define('TOKEN_LIFETIME', 30 * 86400); // 30天

class Auth
{
    /**
     * 验证登录凭据
     */
    public static function attempt(string $username, string $password): ?array
    {
        $user = DB::fetchOne(
            'SELECT id, username, password, status FROM users WHERE username = ? LIMIT 1',
            [$username]
        );

        if (!$user || $user['status'] != 1) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    /**
     * 创建登录会话，踢掉旧设备
     */
    public static function createSession(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + TOKEN_LIFETIME);

        // 清除该用户所有旧会话（单设备登录）
        DB::execute('DELETE FROM user_sessions WHERE user_id = ?', [$userId]);

        DB::execute(
            'INSERT INTO user_sessions (user_id, token, ip, user_agent, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$userId, $token, clientIp(), clientAgent(), $expires]
        );

        // 写 cookie，30天有效
        setcookie(TOKEN_COOKIE, $token, [
            'expires'  => time() + TOKEN_LIFETIME,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        return $token;
    }

    /**
     * 校验当前登录状态
     */
    public static function check(): ?array
    {
        $token = $_COOKIE[TOKEN_COOKIE] ?? '';
        if ($token === '') {
            return null;
        }

        $session = DB::fetchOne(
            'SELECT s.*, u.username, u.status AS user_status
             FROM user_sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.token = ? AND s.expires_at > NOW()
             LIMIT 1',
            [$token]
        );

        if (!$session || $session['user_status'] != 1) {
            self::clearCookie();
            return null;
        }

        return $session;
    }

    /**
     * 要求已登录，未登录则返回 JSON 错误
     */
    public static function requireLogin(): array
    {
        $session = self::check();
        if (!$session) {
            jsonResponse(401, '登录已过期，请重新登录');
        }
        return $session;
    }

    /**
     * 清除指定用户所有会话
     */
    public static function revokeUserSessions(int $userId): void
    {
        DB::execute('DELETE FROM user_sessions WHERE user_id = ?', [$userId]);
    }

    /**
     * 退出登录
     */
    public static function logout(): void
    {
        $token = $_COOKIE[TOKEN_COOKIE] ?? '';
        if ($token !== '') {
            DB::execute('DELETE FROM user_sessions WHERE token = ?', [$token]);
        }
        self::clearCookie();
    }

    private static function clearCookie(): void
    {
        setcookie(TOKEN_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * 生成验证码
     */
    public static function generateCaptcha(): string
    {
        $code = '';
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $_SESSION['captcha'] = strtoupper($code);
        $_SESSION['captcha_time'] = time();
        return $code;
    }

    /**
     * 校验验证码
     */
    public static function verifyCaptcha(string $input): bool
    {
        if (empty($_SESSION['captcha']) || empty($_SESSION['captcha_time'])) {
            return false;
        }
        // 5分钟过期
        if (time() - $_SESSION['captcha_time'] > 300) {
            unset($_SESSION['captcha'], $_SESSION['captcha_time']);
            return false;
        }
        $ok = strtoupper($input) === $_SESSION['captcha'];
        unset($_SESSION['captcha'], $_SESSION['captcha_time']);
        return $ok;
    }
}
