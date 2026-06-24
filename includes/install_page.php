<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - NanoMDM 控制面板</title>
    <link rel="icon" href="assets/img/CDMH_TM.png" type="image/png">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/install.css">
</head>
<body>
    <div class="page-wrap">
        <div class="install-card">
            <div class="install-header">
                <img src="assets/img/CDMH_TM.png" alt="logo" class="site-logo">
                <h1>系统安装向导</h1>
                <p class="subtitle">基于 NanoMDM API 的 Apple MDM 管理面板</p>
                <p class="install-warn">此页面仅首次部署使用，安装完成后请删除 install.php</p>
            </div>

            <form id="installForm" class="install-form">
                <fieldset>
                    <legend>数据库连接设置(填写已有数据库配置)</legend>
                    <div class="form-row">
                        <label for="db_host">连接地址</label>
                        <input type="text" id="db_host" name="db_host" value="127.0.0.1" required>
                    </div>
                    <div class="form-row">
                        <label for="db_port">端口</label>
                        <input type="number" id="db_port" name="db_port" value="3306" required>
                    </div>
                    <div class="form-row">
                        <label for="db_name">数据库名</label>
                        <input type="text" id="db_name" name="db_name" value="nanomdm_ui" required>
                    </div>
                    <div class="form-row">
                        <label for="db_user">用户名</label>
                        <input type="text" id="db_user" name="db_user" value="cdmh" required>
                    </div>
                    <div class="form-row">
                        <label for="db_pass">密码</label>
                        <input type="password" id="db_pass" name="db_pass" placeholder="请输入数据库密码">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>面板设置</legend>
                    <div class="form-row">
                        <label for="site_name">面板名称</label>
                        <input type="text" id="site_name" name="site_name" value="瓷都名汇-MDM管理系统" required>
                    </div>
                    <div class="form-row">
                        <label for="admin_user">管理员用户名</label>
                        <input type="text" id="admin_user" name="admin_user" value="cdmh" required>
                    </div>
                    <div class="form-row">
                        <label for="admin_pass">管理员密码</label>
                        <input type="password" id="admin_pass" name="admin_pass" placeholder="请设置密码（至少6位）" required>
                    </div>
                    <div class="form-row">
                        <label for="admin_pass2">确认密码</label>
                        <input type="password" id="admin_pass2" name="admin_pass2" placeholder="再次输入密码" required>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">开始安装</button>
                </div>
            </form>

            <div id="msgBox" class="msg-box hidden"></div>
        </div>

        <?php include __DIR__ . '/footer.php'; ?>
    </div>

    <script src="assets/js/common.js"></script>
    <script src="assets/js/install.js"></script>
</body>
</html>
