<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?php echo htmlspecialchars($siteName ?? 'MDM管理系统'); ?></title>
    <link rel="icon" href="assets/img/CDMH_TM.png" type="image/png">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-center">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/img/CDMH_TM.png" alt="logo" class="site-logo">
                <h1><?php echo htmlspecialchars($siteName ?? 'MDM管理系统'); ?></h1>
                <p class="subtitle">Apple 设备 MDM 管理平台</p>
            </div>

            <form id="loginForm" class="login-form">
                <div class="form-row">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>
                <div class="form-row">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <div class="form-row captcha-row">
                    <label for="captcha">验证码</label>
                    <div class="captcha-wrap">
                        <input type="text" id="captcha" name="captcha" maxlength="4" autocomplete="off" required>
                        <img id="captchaImg" class="captcha-img" src="api/auth/captcha.php" alt="验证码" title="点击刷新">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="loginBtn">登 录</button>
                </div>
            </form>

            <div id="msgBox" class="msg-box hidden"></div>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>

    <script src="assets/js/common.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
