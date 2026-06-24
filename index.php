<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isInstalled()) {
    include __DIR__ . '/includes/not_installed.php';
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/site_config.php';
require_once __DIR__ . '/includes/config_status.php';

$session = Auth::check();

if (!$session) {
    $siteName = SiteConfig::siteName();
    include __DIR__ . '/includes/login_page.php';
    exit;
}

$siteName = SiteConfig::siteName();
$username = $session['username'];
$configStatus = ConfigStatus::load();

$pages = [
    'dashboard' => [
        'file' => 'dashboard.php',
        'title' => '控制台',
        'js'    => ['dashboard.js'],
        'css'   => [],
    ],
    'settings' => [
        'file' => 'settings.php',
        'title' => '系统设置',
        'js'    => ['settings.js'],
        'css'   => ['settings.css'],
    ],
    'policy' => [
        'file' => 'policy_config.php',
        'title' => '策略配置',
        'js'    => ['policy.js'],
        'css'   => ['policy.css'],
    ],
    'dep' => [
        'file' => 'dep_config.php',
        'title' => 'DEP配置',
        'js'    => ['dep.js'],
        'css'   => ['dep.css'],
    ],
    'dep_manage' => [
        'file' => 'dep_manage.php',
        'title' => 'DEP管理',
        'js'    => ['dep_manage.js', 'dep_devices.js'],
        'css'   => ['dep_manage.css'],
    ],
    'apns' => [
        'file' => 'apns_config.php',
        'title' => 'APNS证书管理',
        'js'    => ['apns.js'],
        'css'   => ['apns.css'],
    ],
    'profile' => [
        'file' => 'profile_config.php',
        'title' => '描述文件配置',
        'js'    => ['profile.js'],
        'css'   => ['profile.css'],
    ],
    'mdm' => [
        'file' => 'mdm_config.php',
        'title' => 'MDM配置',
        'js'    => ['mdm.js'],
        'css'   => ['mdm.css'],
    ],
    'devices' => [
        'file' => 'devices.php',
        'title' => '设备管理',
        'js'    => ['devices.js'],
        'css'   => ['devices.css'],
    ],
    'device_manage' => [
        'file' => 'device_manage.php',
        'title' => '设备管理',
        'js'    => ['device_manage.js'],
        'css'   => ['device_manage.css', 'devices.css', 'policy.css'],
    ],
    'device_logs' => [
        'file' => 'device_logs.php',
        'title' => '设备日志',
        'js'    => ['logs.js'],
        'css'   => ['logs.css'],
    ],
    'system_logs' => [
        'file' => 'system_logs.php',
        'title' => '系统日志',
        'js'    => ['logs.js'],
        'css'   => ['logs.css'],
    ],
    'api_logs' => [
        'file' => 'api_logs.php',
        'title' => 'API通讯日志',
        'js'    => ['logs.js'],
        'css'   => ['logs.css'],
    ],
];

$currentPage = $_GET['page'] ?? 'dashboard';
if (!isset($pages[$currentPage])) {
    $currentPage = 'dashboard';
}

$pageInfo = $pages[$currentPage];
$pageTitle = ($currentPage === 'dashboard') ? $siteName : ($pageInfo['title'] . ' - ' . $siteName);

$navPage = $currentPage;
if ($currentPage === 'device_manage') {
    $navPage = 'devices';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" href="assets/img/CDMH_TM.png" type="image/png">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
<?php foreach ($pageInfo['css'] as $cssFile): ?>
    <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($cssFile); ?>">
<?php endforeach; ?>
</head>
<body class="app-layout page-<?php echo htmlspecialchars($currentPage); ?>">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="app-body">
        <main class="main-content">
            <?php include __DIR__ . '/includes/' . $pageInfo['file']; ?>
        </main>

        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>

    <script src="assets/js/common.js"></script>
<?php foreach ($pageInfo['js'] as $jsFile): ?>
    <script src="assets/js/<?php echo htmlspecialchars($jsFile); ?>"></script>
<?php endforeach; ?>
</body>
</html>
