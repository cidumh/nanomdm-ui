<?php
require_once __DIR__ . '/policy.php';
$funcKeys = PolicyConfig::funcRestrictionKeys();
?>
<div class="policy-page">
    <div class="page-head">
        <h2 class="page-title">策略配置</h2>
        <p class="page-desc">配置新设备注册时自动执行的策略，保存后将在设备首次注册时生效</p>
    </div>

    <form id="policyForm" class="policy-form">
        <!-- 激活锁 -->
        <section class="policy-block">
            <div class="policy-switch-row">
                <div class="policy-switch-info">
                    <h3 class="block-title">新设备注册时开启激活锁</h3>
                    <p class="block-tip" id="activationLockTip">需先保存 DEP 配置后才能开启</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="activation_lock" name="activation_lock">
                    <span class="switch-slider"></span>
                </label>
            </div>
        </section>

        <!-- DNS 代理 -->
        <section class="policy-block">
            <div class="policy-switch-row">
                <div class="policy-switch-info">
                    <h3 class="block-title">新设备注册时安装 DNS 代理</h3>
                </div>
                <label class="switch">
                    <input type="checkbox" id="dns_proxy" name="dns_proxy">
                    <span class="switch-slider"></span>
                </label>
            </div>
            <div class="policy-sub hidden" id="dnsProxyFields">
                <div class="form-row">
                    <label for="dns_org_name">机构名称</label>
                    <input type="text" id="dns_org_name" name="dns_org_name" placeholder="瓷都名汇">
                </div>
                <div class="form-row">
                    <label for="dns_identifier">配置标识</label>
                    <input type="text" id="dns_identifier" name="dns_identifier">
                </div>
                <div class="form-row">
                    <label for="dns_server_url">ServerURL</label>
                    <input type="text" id="dns_server_url" name="dns_server_url" placeholder="https://dns.alidns.com/dns-query">
                </div>
                <div class="form-row">
                    <label>ServerAddresses <span class="label-hint">（可选，IPv4 地址）</span></label>
                    <input type="text" id="dns_address_1" name="dns_address_1" placeholder="223.5.5.5">
                </div>
                <div class="form-row">
                    <input type="text" id="dns_address_2" name="dns_address_2" placeholder="223.6.6.6">
                </div>
            </div>
        </section>

        <!-- 全局代理 -->
        <section class="policy-block">
            <div class="policy-switch-row">
                <div class="policy-switch-info">
                    <h3 class="block-title">新设备注册时安装全局代理</h3>
                </div>
                <label class="switch">
                    <input type="checkbox" id="global_proxy" name="global_proxy">
                    <span class="switch-slider"></span>
                </label>
            </div>
            <div class="policy-sub hidden" id="globalProxyFields">
                <div class="form-row">
                    <label for="proxy_org_name">机构名称</label>
                    <input type="text" id="proxy_org_name" name="proxy_org_name" placeholder="瓷都名汇">
                </div>
                <div class="form-row">
                    <label for="proxy_identifier">配置标识</label>
                    <input type="text" id="proxy_identifier" name="proxy_identifier">
                </div>
                <div class="form-row">
                    <label for="proxy_pac_url">ProxyPACURL</label>
                    <input type="text" id="proxy_pac_url" name="proxy_pac_url" placeholder="PAC 自动代理文件 URL">
                </div>
            </div>
        </section>

        <!-- 功能限制 -->
        <section class="policy-block">
            <div class="policy-switch-row">
                <div class="policy-switch-info">
                    <h3 class="block-title">新设备注册时安装功能限制</h3>
                </div>
                <label class="switch">
                    <input type="checkbox" id="func_restriction" name="func_restriction">
                    <span class="switch-slider"></span>
                </label>
            </div>
            <div class="policy-sub hidden" id="funcRestrictionFields">
                <div class="form-row">
                    <label for="func_org_name">机构名称</label>
                    <input type="text" id="func_org_name" name="func_org_name" placeholder="瓷都名汇">
                </div>
                <div class="form-row">
                    <label for="func_identifier">配置标识</label>
                    <input type="text" id="func_identifier" name="func_identifier">
                </div>
                <div class="func-switch-grid">
                    <?php foreach ($funcKeys as $item): ?>
                    <div class="func-switch-item<?php echo $item['security'] ? ' func-security' : ''; ?>">
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                        <label class="switch switch-sm">
                            <input type="checkbox" class="func-key" data-key="<?php echo htmlspecialchars($item['key']); ?>">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <?php if ($item['key'] === 'allowCamera'): ?>
                    <div class="func-camera-whitelist">
                        <div class="func-switch-item">
                            <span>相机白名单</span>
                            <label class="switch switch-sm">
                                <input type="checkbox" id="camera_whitelist_enabled" class="func-key" data-key="camera_whitelist_enabled">
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <div class="form-row hidden" id="cameraWhitelistFields">
                            <label for="allowedCameraRestrictionBundleIDs">允许使用相机的应用 ID</label>
                            <textarea id="allowedCameraRestrictionBundleIDs" rows="4" placeholder="每行一个应用 Bundle ID"></textarea>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="form-actions policy-actions">
            <button type="submit" class="btn btn-primary" id="savePolicyBtn">保存策略</button>
        </div>
    </form>

    <div id="msgBox" class="msg-box hidden"></div>
</div>
