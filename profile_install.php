<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isInstalled()) {
    include __DIR__ . '/includes/not_installed.php';
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/site_config.php';
require_once __DIR__ . '/includes/profile.php';
require_once __DIR__ . '/includes/profile_builder.php';

ProfileConfig::ensureTables();

$siteName = SiteConfig::siteName();
$config = ProfileConfig::getAll();
$ready = ProfileBuilder::isReady($config);
$readyMessage = ProfileBuilder::readinessMessage($config);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>描述文件安装 - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" href="assets/img/CDMH_TM.png" type="image/png">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/profile_install.css">
</head>
<body class="profile-install-page">
    <div class="profile-install-center">
        <div class="profile-install-card">
            <div class="profile-install-header">
                <img src="assets/img/CDMH_TM.png" alt="logo" class="site-logo">
                <h1><?php echo htmlspecialchars($config['profile_name']); ?></h1>
                <p class="subtitle"><?php echo htmlspecialchars($config['profile_description']); ?></p>
            </div>

            <div class="profile-install-info">
                <div class="info-row">
                    <span class="info-label">组织名称</span>
                    <span class="info-value"><?php echo htmlspecialchars($config['org_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">配置文件标识</span>
                    <span class="info-value mono"><?php echo htmlspecialchars($config['profile_identifier']); ?></span>
                </div>
            </div>

            <div class="profile-install-tip">
                <p>请在 Apple 设备上使用 Safari 打开本页面，点击下方按钮安装 MDM 描述文件。</p>
                <p>安装过程中请按照系统提示完成操作。</p>
            </div>

<?php if ($ready): ?>
            <div class="profile-install-actions">
                <a href="api/profile/download.php" class="btn btn-primary btn-install">安装描述文件</a>
            </div>
<?php else: ?>
            <div class="profile-install-error">
                <?php echo htmlspecialchars($readyMessage ?: '描述文件尚未配置完成，请联系管理员'); ?>
            </div>
<?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
