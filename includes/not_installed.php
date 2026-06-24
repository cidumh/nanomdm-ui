<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统未安装 - NanoMDM 控制面板</title>
    <link rel="icon" href="assets/img/CDMH_TM.png" type="image/png">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/install.css">
</head>
<body>
    <div class="page-wrap">
        <div class="install-card">
            <div class="install-header">
                <img src="assets/img/CDMH_TM.png" alt="logo" class="site-logo">
                <h1>系统尚未安装</h1>
                <p class="subtitle">请先完成安装配置后再使用管理面板</p>
            </div>

            <div class="notice-box">
                <?php if (file_exists(INSTALL_FILE)): ?>
                    <p>检测到安装程序可用，请点击下方按钮进入安装向导。</p>
                    <p class="notice-tip">安装完成后请删除服务器上的 <code>install.php</code> 文件，防止被重复执行。</p>
                    <div class="form-actions">
                        <a href="install.php" class="btn btn-primary">前往安装</a>
                    </div>
                <?php else: ?>
                    <p>未找到安装入口文件 <code>install.php</code>，无法继续安装。</p>
                    <p class="notice-tip">请将安装文件上传至网站根目录后，访问 <code>install.php</code> 完成配置。</p>
                <?php endif; ?>
            </div>
        </div>

        <?php include __DIR__ . '/footer.php'; ?>
    </div>
</body>
</html>
