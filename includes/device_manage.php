<?php
$manageSerial = trim($_GET['serial'] ?? '');
$manageTab = trim($_GET['tab'] ?? 'detail');
$deviceManageTabs = ['detail', 'actions', 'profiles', 'logs'];
if (!in_array($manageTab, $deviceManageTabs, true)) {
    $manageTab = 'detail';
}
?>
<div class="device-manage-page" id="deviceManagePage" data-serial="<?php echo htmlspecialchars($manageSerial); ?>" data-initial-tab="<?php echo htmlspecialchars($manageTab); ?>">
    <div class="page-head device-manage-head">
        <div class="device-manage-head-main">
            <a href="index.php?page=devices" class="device-manage-back">&larr; 返回设备列表</a>
            <h2 class="page-title">设备管理</h2>
            <p class="page-desc">序列号：<strong id="manageDeviceSerial"><?php echo htmlspecialchars($manageSerial ?: '-'); ?></strong></p>
        </div>
        <div class="device-manage-head-meta hidden" id="manageDeviceMeta"></div>
    </div>

    <div id="manageLoadingBox" class="device-manage-loading">加载设备信息中...</div>
    <div id="manageErrorBox" class="device-manage-error hidden"></div>

    <div id="manageMain" class="hidden">
        <nav class="device-manage-tabs">
            <button type="button" class="device-manage-tab<?php echo $manageTab === 'detail' ? ' active' : ''; ?>" data-tab="detail">设备信息</button>
            <button type="button" class="device-manage-tab<?php echo $manageTab === 'actions' ? ' active' : ''; ?>" data-tab="actions">功能操作</button>
            <button type="button" class="device-manage-tab<?php echo $manageTab === 'profiles' ? ' active' : ''; ?>" data-tab="profiles">配置文件管理</button>
            <button type="button" class="device-manage-tab<?php echo $manageTab === 'logs' ? ' active' : ''; ?>" data-tab="logs">设备的日志</button>
        </nav>

        <div id="msgBox" class="msg-box device-manage-msg-box hidden"></div>

        <div class="device-manage-panel<?php echo $manageTab === 'detail' ? ' active' : ''; ?>" id="tabDetail">
            <div class="device-status-banner" id="deviceStatusBanner"></div>
            <section class="device-info-section">
                <div class="device-info-section-head">
                    <div class="device-info-section-title-wrap">
                        <h3 class="device-info-section-title">设备详情</h3>
                        <p class="device-info-section-desc">查看设备上报信息与本地管理字段</p>
                    </div>
                    <div class="device-manage-toolbar device-info-toolbar">
                        <button type="button" class="btn btn-primary btn-sm" id="updateDeviceInfoBtn">更新设备信息</button>
                        <button type="button" class="btn btn-refresh" id="refreshDeviceInfoBtn"><span class="btn-icon" aria-hidden="true">↻</span>刷新设备信息</button>
                    </div>
                </div>
                <div class="device-detail-grid" id="deviceDetailGrid"></div>
            </section>
        </div>

        <div class="device-manage-panel<?php echo $manageTab === 'actions' ? ' active' : ''; ?>" id="tabActions">
            <div class="device-action-grid" id="deviceActionGrid">
                <button type="button" class="device-manage-action-btn" data-action="connect" id="connectDeviceBtn">设备连接</button>
                <button type="button" class="device-manage-action-btn" data-action="device_configured">发送配置完成</button>
                <button type="button" class="device-manage-action-btn" data-action="restart_device">重启设备</button>
                <button type="button" class="device-manage-action-btn" data-action="shutdown_device">关闭设备</button>
                <button type="button" class="device-manage-action-btn" data-action="update_wallpaper">修改壁纸</button>
                <button type="button" class="device-manage-action-btn" data-action="device_lock">锁定设备（非监管使用）</button>
                <button type="button" class="device-manage-action-btn" data-action="enable_lost_mode">丢失锁机（监管机使用）</button>
                <button type="button" class="device-manage-action-btn" data-action="update_location" id="updateLocationBtn" title="需在设备丢失锁机状态下才能获取到定位信息">获取位置</button>
                <button type="button" class="device-manage-action-btn is-danger" data-action="disable_lost_mode" title="需在设备丢失锁机状态下才能解除成功">解除丢失锁机</button>
                <button type="button" class="device-manage-action-btn" data-action="play_lost_mode_sound" title="需在设备丢失锁机状态下才能播放声音">播放提示音</button>
                <button type="button" class="device-manage-action-btn is-danger" data-action="clear_passcode" title="需要设备 Token 已同步，执行后将清除设备密码和面容">清除密码和面容</button>
                <button type="button" class="device-manage-action-btn is-danger" data-action="erase_device" title="抹除还原后设备需要重新注册">抹除还原</button>
                <button type="button" class="device-manage-action-btn" data-action="enable_activation_lock" id="enableActivationLockBtn">开启激活锁</button>
                <button type="button" class="device-manage-action-btn is-danger" data-action="disable_activation_lock" id="disableActivationLockBtn">关闭激活锁</button>
            </div>
        </div>

        <div class="device-manage-panel<?php echo $manageTab === 'profiles' ? ' active' : ''; ?>" id="tabProfiles">
            <div class="device-profile-tip">
                <span class="device-profile-tip-icon" aria-hidden="true">!</span>
                <span>每次使用本页功能前，请先点击「更新配置文件列表」向设备发送同步指令，待设备响应后再点击「刷新配置文件列表」查看最新数据。</span>
            </div>
            <div class="device-manage-toolbar device-profile-toolbar">
                <button type="button" class="btn btn-primary btn-sm" id="updateProfileListBtn">更新配置文件列表</button>
                <div class="device-toolbar-actions">
                    <button type="button" class="btn btn-refresh" id="refreshProfileListBtn"><span class="btn-icon" aria-hidden="true">↻</span>刷新配置文件列表</button>
                    <button type="button" class="btn btn-action-outline" id="installProfileFileBtn"><span class="btn-icon" aria-hidden="true">+</span>安装配置描述文件</button>
                    <button type="button" class="btn btn-action-outline is-danger" id="removeProfileByIdBtn"><span class="btn-icon" aria-hidden="true">−</span>移除配置描述文件</button>
                </div>
            </div>
            <section class="device-profile-list-section">
                <div class="device-profile-list-head">
                    <h3 class="section-title">配置文件列表</h3>
                    <div class="device-profile-list-meta" id="deviceProfileListMeta">暂无配置文件数据</div>
                </div>
                <div class="device-profile-list-wrap">
                    <table class="device-profile-list-table">
                        <thead>
                            <tr>
                                <th>配置文件名</th>
                                <th>配置文件 ID</th>
                                <th>功能标识</th>
                                <th class="col-action">管理</th>
                            </tr>
                        </thead>
                        <tbody id="deviceProfileListBody">
                            <tr><td colspan="4" class="empty-cell">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="device-profile-restrict-section">
                <h3 class="section-title">功能限制配置</h3>
                <div class="device-action-grid device-profile-restrict-grid">
                    <button type="button" class="device-manage-action-btn" id="deviceDnsProfileBtn">DNS 代理配置</button>
                    <button type="button" class="device-manage-action-btn" id="deviceGlobalProfileBtn">全局代理配置</button>
                    <button type="button" class="device-manage-action-btn" id="deviceFuncProfileBtn">功能限制配置</button>
                </div>
            </section>
        </div>

        <div class="device-manage-panel<?php echo $manageTab === 'logs' ? ' active' : ''; ?>" id="tabLogs">
            <div class="device-per-log-toolbar">
                <input type="date" id="perLogDateFrom" class="log-date-input" autocomplete="off">
                <input type="date" id="perLogDateTo" class="log-date-input" autocomplete="off">
                <input type="text" id="perLogKeyword" class="device-per-log-keyword" placeholder="操作类型 / 通讯ID / 指令类型等">
                <button type="button" class="btn btn-primary btn-sm" id="perLogSearchBtn">搜索</button>
                <button type="button" class="btn btn-text btn-sm" id="perLogResetBtn">重置</button>
                <button type="button" class="btn btn-refresh" id="perLogRefreshBtn"><span class="btn-icon" aria-hidden="true">↻</span>刷新日志</button>
            </div>
            <div class="device-per-log-meta" id="perLogMeta">加载中...</div>
            <div class="device-per-log-table-wrap">
                <table class="data-table device-per-log-table">
                    <thead>
                        <tr>
                            <th>操作类型</th>
                            <th>通讯ID</th>
                            <th>推送ID</th>
                            <th>指令类型</th>
                            <th>状态</th>
                            <th>操作时间</th>
                            <th>确认时间</th>
                        </tr>
                    </thead>
                    <tbody id="perLogBody">
                        <tr><td colspan="7" class="empty-cell">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="device-per-log-pagination">
                <button type="button" class="btn btn-text btn-sm" id="perLogPrevBtn" disabled>上一页</button>
                <span id="perLogPageInfo">第 1 / 1 页</span>
                <button type="button" class="btn btn-text btn-sm" id="perLogNextBtn" disabled>下一页</button>
                <div class="device-per-log-jump">
                    <label for="perLogJumpInput">跳转到</label>
                    <input type="number" id="perLogJumpInput" min="1" value="1">
                    <span>页</span>
                    <button type="button" class="btn btn-text btn-sm" id="perLogJumpBtn">跳转</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="remarkModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="remarkModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>设置备注</h3>
            <button type="button" class="device-modal-close" id="closeRemarkModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <p class="device-modal-info">设备序列号：<strong id="remarkDeviceSerial"></strong></p>
            <div class="form-row">
                <label for="remarkInput">备注</label>
                <input type="text" id="remarkInput" maxlength="255" placeholder="请输入设备备注">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelRemarkBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmRemarkBtn">保存</button>
            </div>
        </div>
    </div>
</div>

<div id="phoneModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="phoneModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>设置号码</h3>
            <button type="button" class="device-modal-close" id="closePhoneModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <p class="device-modal-info">设备序列号：<strong id="phoneDeviceSerial"></strong></p>
            <div class="form-row">
                <label for="phoneInput">联系号码</label>
                <input type="text" id="phoneInput" maxlength="32" placeholder="请输入联系号码">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelPhoneBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmPhoneBtn">保存</button>
            </div>
        </div>
    </div>
</div>

<div id="deviceNameModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="deviceNameModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>修改设备名称</h3>
            <button type="button" class="device-modal-close" id="closeDeviceNameModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <p class="device-modal-info">设备序列号：<strong id="deviceNameSerial"></strong></p>
            <div class="form-row">
                <label for="deviceNameInput">新设备名称</label>
                <input type="text" id="deviceNameInput" maxlength="255" placeholder="请输入新的设备名称">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDeviceNameBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmDeviceNameBtn">确定</button>
            </div>
        </div>
    </div>
</div>

<div id="restartModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="restartModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>重启设备</h3>
            <button type="button" class="device-modal-close" id="closeRestartModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要向该设备发送重启指令吗？设备执行后将重新启动。</div>
            <p class="device-modal-info">设备序列号：<strong id="restartDeviceSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelRestartBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmRestartBtn">确认重启</button>
            </div>
        </div>
    </div>
</div>

<div id="wallpaperModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="wallpaperModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>修改壁纸</h3>
            <button type="button" class="device-modal-close" id="closeWallpaperModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <p class="device-modal-info">设备序列号：<strong id="wallpaperDeviceSerial"></strong></p>
            <p class="device-modal-hint">请选择本地壁纸图片，将在浏览器中转换为 Base64 后直接发送给设备，不会上传到服务器存储。</p>
            <div class="form-row">
                <label for="wallpaperFileInput">壁纸图片</label>
                <input type="file" id="wallpaperFileInput" accept="image/*">
            </div>
            <div class="device-wallpaper-preview hidden" id="wallpaperPreviewWrap">
                <img id="wallpaperPreviewImg" alt="壁纸预览">
                <div class="device-wallpaper-file-name" id="wallpaperFileName"></div>
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelWallpaperBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmWallpaperBtn" disabled>确定发送</button>
            </div>
        </div>
    </div>
</div>

<div id="deviceLockModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="deviceLockModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>锁定设备</h3>
            <button type="button" class="device-modal-close" id="closeDeviceLockModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">此功能适用于非监管设备。锁定后设备使用者仍可使用密码或面容 ID 解锁设备。</div>
            <p class="device-modal-info">设备序列号：<strong id="deviceLockSerial"></strong></p>
            <div class="form-row">
                <label for="deviceLockMessageInput">显示的信息内容</label>
                <textarea id="deviceLockMessageInput" rows="3" maxlength="500" placeholder="请输入锁屏时显示的信息内容"></textarea>
            </div>
            <div class="form-row">
                <label for="deviceLockPhoneInput">联系号码</label>
                <input type="text" id="deviceLockPhoneInput" maxlength="32" placeholder="请输入联系号码">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDeviceLockBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeviceLockBtn">确认锁定</button>
            </div>
        </div>
    </div>
</div>

<div id="enableLostModeModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="enableLostModeModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>丢失锁机</h3>
            <button type="button" class="device-modal-close" id="closeEnableLostModeModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">此功能适用于监管设备。开启丢失模式后，设备将无法正常使用，需通过「解除丢失锁机」才能解除锁定。</div>
            <p class="device-modal-info">设备序列号：<strong id="enableLostModeSerial"></strong></p>
            <div class="form-row">
                <label for="enableLostModeFootnoteInput">底部显示信息</label>
                <textarea id="enableLostModeFootnoteInput" rows="2" maxlength="500" placeholder="请输入锁屏底部显示的信息"></textarea>
            </div>
            <div class="form-row">
                <label for="enableLostModeMessageInput">提示显示的信息</label>
                <textarea id="enableLostModeMessageInput" rows="3" maxlength="500" placeholder="请输入锁屏提示显示的信息"></textarea>
            </div>
            <div class="form-row">
                <label for="enableLostModePhoneInput">联系号码</label>
                <input type="text" id="enableLostModePhoneInput" maxlength="32" placeholder="请输入联系号码">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelEnableLostModeBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmEnableLostModeBtn">确认丢失锁机</button>
            </div>
        </div>
    </div>
</div>

<div id="updateLocationModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="updateLocationModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>获取位置</h3>
            <button type="button" class="device-modal-close" id="closeUpdateLocationModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要获取该设备的位置信息吗？注意：需在设备丢失锁机状态下才能获取到定位信息。</div>
            <p class="device-modal-info">设备序列号：<strong id="updateLocationSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelUpdateLocationBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmUpdateLocationBtn">确认获取</button>
            </div>
        </div>
    </div>
</div>

<div id="disableLostModeModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="disableLostModeModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>解除丢失锁机</h3>
            <button type="button" class="device-modal-close" id="closeDisableLostModeModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要解除该设备的丢失锁机吗？注意：需在设备丢失锁机状态下才能解除成功，解除后设备将退出丢失模式。</div>
            <p class="device-modal-info">设备序列号：<strong id="disableLostModeSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDisableLostModeBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDisableLostModeBtn">确认解除</button>
            </div>
        </div>
    </div>
</div>

<div id="playLostModeSoundModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="playLostModeSoundModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>播放提示音</h3>
            <button type="button" class="device-modal-close" id="closePlayLostModeSoundModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要向该设备播放提示音吗？注意：需在设备丢失锁机状态下才能播放声音。</div>
            <p class="device-modal-info">设备序列号：<strong id="playLostModeSoundSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelPlayLostModeSoundBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmPlayLostModeSoundBtn">确认播放</button>
            </div>
        </div>
    </div>
</div>

<div id="clearPasscodeModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="clearPasscodeModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>清除密码和面容</h3>
            <button type="button" class="device-modal-close" id="closeClearPasscodeModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要清除该设备的密码和面容吗？此操作将清除设备密码和面容 ID，且需要使用设备信息中的 Token 发送指令。</div>
            <p class="device-modal-info">设备序列号：<strong id="clearPasscodeSerial"></strong></p>
            <p class="device-modal-info">Token 状态：<strong id="clearPasscodeTokenStatus">-</strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelClearPasscodeBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmClearPasscodeBtn">确认清除</button>
            </div>
        </div>
    </div>
</div>

<div id="eraseDeviceModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="eraseDeviceModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>抹除还原</h3>
            <button type="button" class="device-modal-close" id="closeEraseDeviceModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要向该设备发送抹除还原指令吗？此操作不可撤销，抹除还原后设备需要重新注册。</div>
            <p class="device-modal-info">设备序列号：<strong id="eraseDeviceSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelEraseDeviceBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmEraseDeviceBtn">确认抹除</button>
            </div>
        </div>
    </div>
</div>

<div id="enableActivationLockModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="enableActivationLockModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>开启激活锁</h3>
            <button type="button" class="device-modal-close" id="closeEnableActivationLockModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-modal-hint">系统将自动生成绕过码并提交至 DEP 服务器。若该设备此前已开启激活锁，新绕过码将覆盖旧记录，仅最新一组有效。</div>
            <p class="device-modal-info">设备序列号：<strong id="enableActivationLockSerial"></strong></p>
            <div class="form-row">
                <label for="enableActivationLockMessageInput">丢失提示内容</label>
                <textarea id="enableActivationLockMessageInput" rows="3" maxlength="500" placeholder="设备锁屏或丢失模式下显示的文字"></textarea>
                <p class="field-hint">将显示在设备激活锁界面，最多 500 字</p>
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelEnableActivationLockBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmEnableActivationLockBtn">确认开启</button>
            </div>
        </div>
    </div>
</div>

<div id="disableActivationLockModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="disableActivationLockModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>关闭激活锁</h3>
            <button type="button" class="device-modal-close" id="closeDisableActivationLockModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要关闭该设备的激活锁吗？将使用设备绕过码和匹配的 APNS 证书向 Apple 发送关闭请求。</div>
            <p class="device-modal-info">设备序列号：<strong id="disableActivationLockSerial"></strong></p>
            <p class="device-modal-info">Topic：<strong id="disableActivationLockTopic">-</strong></p>
            <p class="device-modal-info">绕过码状态：<strong id="disableActivationLockBypassStatus">-</strong></p>
            <div class="form-row">
                <label for="disableActivationLockOrgNameInput">组织名称</label>
                <input type="text" id="disableActivationLockOrgNameInput" maxlength="255" placeholder="请输入组织名称">
            </div>
            <div class="form-row">
                <label for="disableActivationLockGuidInput">姓名</label>
                <input type="text" id="disableActivationLockGuidInput" maxlength="64" placeholder="请输入姓名">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDisableActivationLockBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDisableActivationLockBtn">确认关闭</button>
            </div>
        </div>
    </div>
</div>

<div id="shutdownModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="shutdownModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>关闭设备</h3>
            <button type="button" class="device-modal-close" id="closeShutdownModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn">确定要向该设备发送关机指令吗？设备执行后将关闭电源。</div>
            <p class="device-modal-info">设备序列号：<strong id="shutdownDeviceSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelShutdownBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmShutdownBtn">确认关闭</button>
            </div>
        </div>
    </div>
</div>

<div id="installProfileFileModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="installProfileFileModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>安装配置描述文件</h3>
            <button type="button" class="device-modal-close" id="closeInstallProfileFileModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <p class="device-modal-info">设备序列号：<strong id="installProfileFileSerial"></strong></p>
            <div class="form-row">
                <label for="installProfileFileInput">选择配置文件</label>
                <input type="file" id="installProfileFileInput" accept=".mobileconfig,.plist,.xml,.cer,.crt,.pem,.der,text/xml,application/xml,application/x-x509-ca-cert,application/x-pem-file">
                <p class="field-hint">支持配置描述文件（mobileconfig / plist）和证书（cer / crt / pem），文件仅在本地读取后发送安装指令</p>
            </div>
            <p class="device-modal-info" id="installProfileFileName">未选择文件</p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelInstallProfileFileBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmInstallProfileFileBtn">确认安装</button>
            </div>
        </div>
    </div>
</div>

<div id="removeProfileByIdModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="removeProfileByIdModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>移除配置描述文件</h3>
            <button type="button" class="device-modal-close" id="closeRemoveProfileByIdModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <p class="device-modal-info">设备序列号：<strong id="removeProfileByIdSerial"></strong></p>
            <div class="form-row">
                <label for="removeProfileByIdInput">配置文件标识 ID</label>
                <input type="text" id="removeProfileByIdInput" placeholder="请输入配置文件 PayloadIdentifier">
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelRemoveProfileByIdBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveProfileByIdBtn">确认移除</button>
            </div>
        </div>
    </div>
</div>

<div id="deviceDnsProfileModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="deviceDnsProfileModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>DNS 代理配置</h3>
            <button type="button" class="device-modal-close" id="closeDeviceDnsProfileModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="form-row"><label for="deviceDnsOrgNameInput">机构名称</label><input type="text" id="deviceDnsOrgNameInput"></div>
            <div class="form-row"><label for="deviceDnsIdentifierInput">配置标识</label><input type="text" id="deviceDnsIdentifierInput"></div>
            <div class="form-row"><label for="deviceDnsServerUrlInput">ServerURL</label><input type="text" id="deviceDnsServerUrlInput"></div>
            <div class="form-row"><label for="deviceDnsAddress1Input">ServerAddresses 1</label><input type="text" id="deviceDnsAddress1Input"></div>
            <div class="form-row"><label for="deviceDnsAddress2Input">ServerAddresses 2</label><input type="text" id="deviceDnsAddress2Input"></div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDeviceDnsProfileBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmDeviceDnsProfileBtn">覆盖安装</button>
            </div>
        </div>
    </div>
</div>

<div id="deviceGlobalProfileModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="deviceGlobalProfileModalBackdrop"></div>
    <div class="device-modal-box">
        <div class="device-modal-header">
            <h3>全局代理配置</h3>
            <button type="button" class="device-modal-close" id="closeDeviceGlobalProfileModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="form-row"><label for="deviceGlobalOrgNameInput">机构名称</label><input type="text" id="deviceGlobalOrgNameInput"></div>
            <div class="form-row"><label for="deviceGlobalIdentifierInput">配置标识</label><input type="text" id="deviceGlobalIdentifierInput"></div>
            <div class="form-row"><label for="deviceGlobalPacUrlInput">ProxyPACURL</label><input type="text" id="deviceGlobalPacUrlInput"></div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDeviceGlobalProfileBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmDeviceGlobalProfileBtn">覆盖安装</button>
            </div>
        </div>
    </div>
</div>

<div id="deviceFuncProfileModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="deviceFuncProfileModalBackdrop"></div>
    <div class="device-modal-box device-modal-lg">
        <div class="device-modal-header">
            <h3>功能限制配置</h3>
            <button type="button" class="device-modal-close" id="closeDeviceFuncProfileModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="form-row"><label for="deviceFuncOrgNameInput">机构名称</label><input type="text" id="deviceFuncOrgNameInput"></div>
            <div class="form-row"><label for="deviceFuncIdentifierInput">配置标识</label><input type="text" id="deviceFuncIdentifierInput"></div>
            <div class="func-switch-grid device-func-switch-grid" id="deviceFuncSwitchGrid"></div>
            <div class="form-row">
                <label for="deviceFuncCameraWhitelistInput">相机白名单应用 ID</label>
                <textarea id="deviceFuncCameraWhitelistInput" rows="3" placeholder="每行一个 Bundle ID"></textarea>
            </div>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDeviceFuncProfileBtn">取消</button>
                <button type="button" class="btn btn-primary" id="confirmDeviceFuncProfileBtn">覆盖安装</button>
            </div>
        </div>
    </div>
</div>
