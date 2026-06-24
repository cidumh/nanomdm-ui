<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/apns_cert.php';
require_once __DIR__ . '/../../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (!isInstalled()) {
    jsonResponse(503, '系统尚未安装');
}

$session = Auth::requireLogin();
ApnsCert::ensureTables();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = (int)($input['id'] ?? 0);

try {
    $row = DB::fetchOne('SELECT cert_remark FROM apns_certificates WHERE id = ? LIMIT 1', [$id]);
    ApnsCert::deleteById($id);
    Logger::system(
        '删除 APNS 证书',
        '删除成功',
        'id=' . $id . ' remark=' . ($row['cert_remark'] ?? ''),
        (int)$session['user_id'],
        $session['username'] ?? ''
    );
    jsonResponse(0, 'APNS 证书已删除', ['list' => ApnsCert::listAll()]);
} catch (InvalidArgumentException $e) {
    jsonResponse(1, $e->getMessage());
} catch (Exception $e) {
    jsonResponse(1, '删除失败：' . $e->getMessage());
}
