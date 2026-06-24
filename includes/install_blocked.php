<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装已完成</title>
    <link rel="icon" href="assets/img/CDMH_TM.png" type="image/png">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/install.css">
</head>
<body>
    <div class="page-wrap">
        <div class="install-card">
            <div class="install-header">
                <img src="assets/img/CDMH_TM.png" alt="logo" class="site-logo">
                <h1>系统已安装</h1>
                <p class="subtitle">安装向导已不可用</p>
            </div>

            <div class="notice-box">
                <p>面板已完成配置，无需重复安装。</p>
                <p class="notice-tip">为安全起见，请立即删除服务器上的 <code>install.php</code> 文件。</p>
                <div class="form-actions">
                    <a href="index.php" class="btn btn-primary">返回首页</a>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/footer.php'; ?>
    </div>
</body>
</html>
