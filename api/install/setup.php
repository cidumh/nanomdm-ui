<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/install.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方式不允许');
}

if (isInstalled()) {
    jsonResponse(400, '系统已安装，安装接口已关闭');
}

if (!file_exists(INSTALL_FILE)) {
    jsonResponse(403, '安装入口不可用，请确认 install.php 存在');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$result = Installer::run($input);

if ($result['ok']) {
    $removed = removeInstallFiles();
    jsonResponse(0, $result['msg'], [
        'files_removed' => $removed,
        'tip' => $removed
            ? '安装文件已自动清理，请刷新页面'
            : '安装成功，请手动删除 install.php 和 api/install/setup.php',
    ]);
} else {
    jsonResponse(1, $result['msg']);
}
