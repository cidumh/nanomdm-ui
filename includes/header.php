<?php
/**
 * 侧边栏 - 需传入 $siteName, $username, $currentPage
 */
require_once __DIR__ . '/ui_icons.php';
require_once __DIR__ . '/config_status.php';
$currentPage = $currentPage ?? 'dashboard';
$navPage = $navPage ?? $currentPage;
?>
<aside class="site-sidebar">
    <a href="index.php" class="sidebar-brand">
        <img src="assets/img/CDMH_TM.png" alt="logo" class="sidebar-logo">
        <span class="sidebar-title"><?php echo htmlspecialchars($siteName); ?></span>
    </a>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-link<?php echo $navPage === 'dashboard' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('dashboard'); ?>
            <span class="nav-text">控制台</span>
        </a>
        <a href="index.php?page=policy" class="nav-link<?php echo $navPage === 'policy' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('policy'); ?>
            <span class="nav-text">策略配置</span>
        </a>
        <a href="index.php?page=dep" class="nav-link<?php echo $navPage === 'dep' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('dep'); ?>
            <span class="nav-text <?php echo ConfigStatus::titleClass('dep'); ?>">DEP配置</span>
        </a>
        <a href="index.php?page=dep_manage" class="nav-link<?php echo $navPage === 'dep_manage' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('dep_manage'); ?>
            <span class="nav-text">DEP管理</span>
        </a>
        <a href="index.php?page=apns" class="nav-link<?php echo $navPage === 'apns' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('apns'); ?>
            <span class="nav-text">APNS证书</span>
        </a>
        <a href="index.php?page=profile" class="nav-link<?php echo $navPage === 'profile' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('profile'); ?>
            <span class="nav-text <?php echo ConfigStatus::titleClass('profile'); ?>">描述文件配置</span>
        </a>
        <a href="index.php?page=mdm" class="nav-link<?php echo $navPage === 'mdm' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('mdm'); ?>
            <span class="nav-text <?php echo ConfigStatus::titleClass('mdm'); ?>">MDM配置</span>
        </a>
        <a href="index.php?page=devices" class="nav-link nav-link-devices<?php echo $navPage === 'devices' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('devices'); ?>
            <span class="nav-text">设备管理</span>
        </a>
        <a href="index.php?page=device_logs" class="nav-link<?php echo $navPage === 'device_logs' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('device_logs'); ?>
            <span class="nav-text">设备日志</span>
        </a>
        <a href="index.php?page=system_logs" class="nav-link<?php echo $navPage === 'system_logs' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('system_logs'); ?>
            <span class="nav-text">系统日志</span>
        </a>
        <a href="index.php?page=api_logs" class="nav-link<?php echo $navPage === 'api_logs' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('api_logs'); ?>
            <span class="nav-text">API通讯日志</span>
        </a>
        <a href="index.php?page=settings" class="nav-link<?php echo $navPage === 'settings' ? ' active' : ''; ?>">
            <?php echo uiNavIcon('settings'); ?>
            <span class="nav-text">系统设置</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
        </div>
        <button type="button" class="btn btn-text sidebar-logout" id="logoutBtn">
            <?php echo uiNavIcon('logout'); ?>
            <span class="nav-text">退出登录</span>
        </button>
    </div>
</aside>
