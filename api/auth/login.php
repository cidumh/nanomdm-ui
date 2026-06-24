<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$captcha  = trim($input['captcha'] ?? '');

if ($username === '' || $password === '') {
    jsonResponse(1, '请输入用户名和密码');
}

if (!Auth::verifyCaptcha($captcha)) {
    Logger::system('用户登录', '登录失败：验证码错误或已过期', '验证码校验未通过', null, $username);
    jsonResponse(1, '验证码错误或已过期');
}

$user = Auth::attempt($username, $password);
if (!$user) {
    Logger::system('用户登录', '登录失败：用户名或密码错误', '尝试用户名: ' . $username, null, $username);
    jsonResponse(1, '用户名或密码错误');
}

$token = Auth::createSession((int)$user['id']);
Logger::system('用户登录', '登录成功', '用户: ' . $username, (int)$user['id'], $username);

jsonResponse(0, '登录成功', [
    'username' => $user['username'],
    'token'    => $token,
]);
