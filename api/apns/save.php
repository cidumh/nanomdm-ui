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

$remark        = trim($input['cert_remark'] ?? '');
$pemCert       = trim($input['pem_cert'] ?? '');
$pemPrivateKey = trim($input['pem_private_key'] ?? '');

try {
    $cert = ApnsCert::save($remark, $pemCert, $pemPrivateKey, (int)$session['user_id']);
    Logger::system(
        '添加 APNS 证书',
        '添加成功',
        'remark=' . $remark . ' topic=' . ($cert['topic'] ?? '') . ' expire=' . ($cert['not_after'] ?? ''),
        (int)$session['user_id'],
        $session['username'] ?? ''
    );
    jsonResponse(0, 'APNS 证书已保存', ['cert' => $cert, 'list' => ApnsCert::listAll()]);
} catch (InvalidArgumentException $e) {
    jsonResponse(1, $e->getMessage());
} catch (Exception $e) {
    jsonResponse(1, '保存失败：' . $e->getMessage());
}
