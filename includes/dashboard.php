<?php
require_once __DIR__ . '/ui_icons.php';
require_once __DIR__ . '/config_status.php';
?>
<div class="dashboard">
    <section class="welcome-section">
        <h2>欢迎回来，<span id="welcomeUser">-</span></h2>
        <p class="welcome-desc">基于 NanoMDM API 扩展的 UI 可视化控制面板，专用于 Apple 设备 MDM 管理。</p>
    </section>

    <section class="stats-row">
        <div class="stat-card">
            <p class="stat-label">设备总数</p>
            <p class="stat-value" id="statDeviceTotal">-</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">今日通讯设备总数</p>
            <p class="stat-value" id="statTodayActive">-</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">今日通讯次数</p>
            <p class="stat-value" id="statTodayComm">-</p>
        </div>
    </section>

    <section class="feature-section">
        <h3 class="section-title">功能菜单</h3>
        <div class="feature-grid">
            <a href="index.php?page=policy" class="feature-card" data-page="policy">
                <?php echo uiFeatureIcon('policy'); ?>
                <h3>策略配置</h3>
                <p>管理 MDM 策略与合规规则</p>
            </a>
            <a href="index.php?page=dep" class="feature-card" data-page="dep">
                <?php echo uiFeatureIcon('dep'); ?>
                <h3 class="<?php echo ConfigStatus::titleClass('dep'); ?>">DEP配置</h3>
                <p>Apple 商务管理 / DEP 账户配置</p>
            </a>
            <a href="index.php?page=dep_manage" class="feature-card" data-page="dep_manage">
                <?php echo uiFeatureIcon('dep_manage'); ?>
                <h3>DEP管理</h3>
                <p>DEP 设备配置文件管理</p>
            </a>
            <a href="index.php?page=apns" class="feature-card" data-page="apns">
                <?php echo uiFeatureIcon('apns'); ?>
                <h3>APNS证书管理</h3>
                <p>填写 PEM 证书与私钥，保存至数据库</p>
            </a>
            <a href="index.php?page=mdm" class="feature-card" data-page="mdm">
                <?php echo uiFeatureIcon('mdm'); ?>
                <h3 class="<?php echo ConfigStatus::titleClass('mdm'); ?>">MDM配置</h3>
                <p>NanoMDM 服务连接与指令测试</p>
            </a>
            <a href="index.php?page=profile" class="feature-card" data-page="profile">
                <?php echo uiFeatureIcon('profile'); ?>
                <h3 class="<?php echo ConfigStatus::titleClass('profile'); ?>">描述文件配置</h3>
                <p>配置 MDM 描述文件基础信息</p>
            </a>
            <a href="index.php?page=devices" class="feature-card" data-page="devices">
                <?php echo uiFeatureIcon('devices'); ?>
                <h3>设备管理</h3>
                <p>查看和管理已注册的 Apple 设备</p>
            </a>
            <a href="index.php?page=device_logs" class="feature-card" data-page="device_logs">
                <?php echo uiFeatureIcon('device_logs'); ?>
                <h3>设备日志</h3>
                <p>设备操作与状态变更记录</p>
            </a>
            <a href="index.php?page=system_logs" class="feature-card" data-page="system_logs">
                <?php echo uiFeatureIcon('system_logs'); ?>
                <h3>系统日志</h3>
                <p>后台系统操作日志查询</p>
            </a>
            <a href="index.php?page=api_logs" class="feature-card" data-page="api_logs">
                <?php echo uiFeatureIcon('api_logs'); ?>
                <h3>API通讯日志</h3>
                <p>MDM 服务器通讯数据记录</p>
            </a>
            <a href="index.php?page=settings" class="feature-card" data-page="settings">
                <?php echo uiFeatureIcon('settings'); ?>
                <h3>系统设置</h3>
                <p>面板名称、账户及数据库连接配置</p>
            </a>
        </div>
    </section>
</div>
