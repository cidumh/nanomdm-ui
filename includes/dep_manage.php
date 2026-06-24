<?php
require_once __DIR__ . '/dep_profile.php';
$skipOptions = DepProfile::skipSetupOptions();
?>
<div class="dep-manage-page">
    <div class="page-head">
        <h2 class="page-title">DEP管理</h2>
        <p class="page-desc">管理 DEP 设备配置文件，提交至 DEP 服务器</p>
    </div>

    <div id="depDisabledBox" class="dep-disabled-box hidden">
        <p>请先在 <a href="index.php?page=dep">DEP 配置</a> 中开启 DEP 开关并填写 DEP API，才能使用 DEP 管理功能。</p>
    </div>

    <div id="depManageMain" class="hidden">
        <nav class="dep-tabs">
            <button type="button" class="dep-tab active" data-tab="config">DEP设备配置</button>
            <button type="button" class="dep-tab" data-tab="devices">DEP设备列表</button>
        </nav>

        <!-- DEP设备配置 -->
        <div class="dep-tab-panel" id="tabConfig">
            <section class="dep-profile-list-section">
                <h3 class="section-title">已保存的配置</h3>
                <div class="table-wrap">
                    <table class="dep-table" id="profileListTable">
                        <thead>
                            <tr>
                                <th>配置文件名称</th>
                                <th>配置 ID</th>
                                <th>组织名称</th>
                                <th>更新时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="profileListBody">
                            <tr><td colspan="5" class="empty-cell">暂无配置</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <form id="depProfileForm" class="dep-profile-form">
                <section class="dep-form-block">
                    <h3 class="block-title">基本信息</h3>
                    <div class="form-row">
                        <label for="profile_name">配置文件名称 <span class="required">*</span></label>
                        <input type="text" id="profile_name" name="profile_name" required>
                    </div>
                    <div class="form-row">
                        <label for="mdm_url">MDM 服务器地址 <span class="required">*</span></label>
                        <input type="text" id="mdm_url" name="mdm_url" placeholder="https://" required>
                    </div>
                    <div class="form-row">
                        <label for="web_url">WEB URL 地址 <span class="required">*</span></label>
                        <input type="text" id="web_url" name="web_url" placeholder="https://" required>
                    </div>
                    <div class="form-row">
                        <label for="department">组织名称 <span class="required">*</span></label>
                        <input type="text" id="department" name="department" value="瓷都名汇">
                    </div>
                    <div class="form-row">
                        <label for="org_magic">组织标识</label>
                        <input type="text" id="org_magic" name="org_magic" placeholder="可选，留空则不填">
                    </div>
                </section>

                <section class="dep-form-block">
                    <h3 class="block-title">安装选项</h3>
                    <div class="dep-switch-grid">
                        <div class="dep-switch-item">
                            <span>监管模式</span>
                            <label class="switch switch-sm"><input type="checkbox" id="is_supervised" checked><span class="switch-slider"></span></label>
                        </div>
                        <div class="dep-switch-item">
                            <span>等待配置完成</span>
                            <label class="switch switch-sm"><input type="checkbox" id="await_device_configured"><span class="switch-slider"></span></label>
                        </div>
                        <div class="dep-switch-item">
                            <span>强制安装</span>
                            <label class="switch switch-sm"><input type="checkbox" id="is_mandatory" checked><span class="switch-slider"></span></label>
                        </div>
                        <div class="dep-switch-item">
                            <span>可移除配置</span>
                            <label class="switch switch-sm"><input type="checkbox" id="is_mdm_removable"><span class="switch-slider"></span></label>
                        </div>
                    </div>
                    <div class="form-row">
                        <label for="device_serials">设备序列号</label>
                        <textarea id="device_serials" rows="5" placeholder="每行一个设备序列号"></textarea>
                    </div>
                </section>

                <section class="dep-form-block">
                    <h3 class="block-title">区域与联系</h3>
                    <div class="form-row-inline">
                        <div class="form-row">
                            <label for="language">系统语言</label>
                            <input type="text" id="language" name="language" value="zh">
                            <p class="field-hint">优先 ISO 639-1 双字母，如 zh</p>
                        </div>
                        <div class="form-row">
                            <label for="region">国家</label>
                            <input type="text" id="region" name="region" value="CN">
                            <p class="field-hint">ISO 3166-1 大写字母，如 CN</p>
                        </div>
                    </div>
                    <div class="form-row-inline">
                        <div class="form-row">
                            <label for="support_email">邮箱</label>
                            <input type="text" id="support_email" name="support_email">
                        </div>
                        <div class="form-row">
                            <label for="support_phone">电话</label>
                            <input type="text" id="support_phone" name="support_phone">
                        </div>
                    </div>
                </section>

                <section class="dep-form-block">
                    <div class="dep-switch-row">
                        <h3 class="block-title">跳过系统设置</h3>
                        <label class="switch switch-sm">
                            <input type="checkbox" id="skip_setup_enabled">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div id="skipSetupFields" class="skip-setup-grid hidden">
                        <?php foreach ($skipOptions as $item): ?>
                        <div class="dep-switch-item">
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                            <label class="switch switch-sm">
                                <input type="checkbox" class="skip-item" data-key="<?php echo htmlspecialchars($item['key']); ?>"<?php echo $item['default'] ? ' checked' : ''; ?>>
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="saveProfileBtn">保存到 DEP 服务</button>
                </div>
            </form>
        </div>

        <!-- DEP设备列表 -->
        <div class="dep-tab-panel hidden" id="tabDevices">
            <section class="dep-devices-toolbar">
                <div class="dep-devices-search">
                    <input type="text" id="deviceSearchInput" placeholder="输入设备序列号查询" autocomplete="off">
                    <button type="button" class="btn btn-primary btn-sm" id="deviceSearchBtn">查询</button>
                    <button type="button" class="btn btn-text btn-sm" id="deviceSearchReset">返回列表</button>
                </div>
                <div class="dep-devices-meta">
                    <span id="deviceListMeta">-</span>
                </div>
            </section>

            <div class="table-wrap dep-devices-table-wrap">
                <table class="dep-table dep-devices-table" id="deviceListTable">
                    <thead>
                        <tr>
                            <th>序列号</th>
                            <th>型号</th>
                            <th>类型</th>
                            <th>配置 ID</th>
                            <th>分配时间</th>
                            <th>推送时间</th>
                            <th>状态</th>
                            <th>分配人</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="deviceListBody">
                        <tr><td colspan="9" class="empty-cell">加载中...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="dep-devices-pagination" id="devicePagination">
                <button type="button" class="btn btn-text" id="devicePrevBtn" disabled>上一页</button>
                <span id="devicePageInfo">第 1 页</span>
                <button type="button" class="btn btn-text" id="deviceNextBtn" disabled>下一页</button>
            </div>
        </div>
    </div>

    <div id="msgBox" class="msg-box hidden"></div>

    <div id="profileDetailModal" class="dep-modal hidden">
        <div class="dep-modal-backdrop" id="profileModalBackdrop"></div>
        <div class="dep-modal-box">
            <div class="dep-modal-header">
                <h3 id="profileModalTitle">配置详情</h3>
                <button type="button" class="dep-modal-close" id="closeProfileModal" aria-label="关闭">&times;</button>
            </div>
            <div id="profileDetailContent" class="dep-modal-body">
                <p class="loading-tip">加载中...</p>
            </div>
        </div>
    </div>

    <div id="bindProfileModal" class="dep-modal hidden">
        <div class="dep-modal-backdrop" id="bindModalBackdrop"></div>
        <div class="dep-modal-box">
            <div class="dep-modal-header">
                <h3 id="bindModalTitle">绑定配置文件</h3>
                <button type="button" class="dep-modal-close" id="closeBindModal" aria-label="关闭">&times;</button>
            </div>
            <div class="dep-modal-body">
                <p class="bind-device-info">设备序列号：<strong id="bindDeviceSerial">-</strong></p>
                <div class="form-row">
                    <label for="bindProfileSelect">选择配置文件</label>
                    <select id="bindProfileSelect">
                        <option value="">加载中...</option>
                    </select>
                </div>
                <p class="field-hint">请先在「DEP设备配置」中创建并保存配置文件。</p>
                <div class="dep-modal-actions">
                    <button type="button" class="btn btn-text" id="cancelBindBtn">取消</button>
                    <button type="button" class="btn btn-primary btn-sm" id="confirmBindBtn">确认绑定</button>
                </div>
            </div>
        </div>
    </div>

    <div id="disownConfirmModal" class="dep-modal hidden">
        <div class="dep-modal-backdrop" id="disownModalBackdrop"></div>
        <div class="dep-modal-box dep-modal-sm">
            <div class="dep-modal-header">
                <h3>解绑设备</h3>
                <button type="button" class="dep-modal-close" id="closeDisownModal" aria-label="关闭">&times;</button>
            </div>
            <div class="dep-modal-body">
                <p class="disown-warn">解绑后设备将脱离监管配置，且无法通过在线方式重新上锁。此操作不可撤销，请再次确认。</p>
                <p class="bind-device-info">设备序列号：<strong id="disownDeviceSerial">-</strong></p>
                <div class="dep-modal-actions">
                    <button type="button" class="btn btn-text" id="cancelDisownBtn">取消</button>
                    <button type="button" class="btn btn-primary btn-sm btn-danger" id="confirmDisownBtn">确认解绑</button>
                </div>
            </div>
        </div>
    </div>

    <div id="activationLockModal" class="dep-modal hidden">
        <div class="dep-modal-backdrop" id="activationLockBackdrop"></div>
        <div class="dep-modal-box">
            <div class="dep-modal-header">
                <h3>开启激活锁</h3>
                <button type="button" class="dep-modal-close" id="closeActivationLockModal" aria-label="关闭">&times;</button>
            </div>
            <div class="dep-modal-body">
                <p class="activation-lock-tip">系统将自动生成绕过码并提交至 DEP 服务器。若该设备此前已开启激活锁，新绕过码将覆盖旧记录，仅最新一组有效。</p>
                <p class="bind-device-info">设备序列号：<strong id="activationLockSerial">-</strong></p>
                <div class="form-row">
                    <label for="activationLockMessage">丢失提示内容 <span class="required">*</span></label>
                    <textarea id="activationLockMessage" rows="3" placeholder="设备锁屏或丢失模式下显示的文字" maxlength="500"></textarea>
                    <p class="field-hint">将显示在设备激活锁界面，最多 500 字</p>
                </div>
                <div class="dep-modal-actions">
                    <button type="button" class="btn btn-text" id="cancelActivationLockBtn">取消</button>
                    <button type="button" class="btn btn-primary btn-sm" id="confirmActivationLockBtn">确认开启</button>
                </div>
            </div>
        </div>
    </div>
</div>
