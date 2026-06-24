<div class="devices-page">
    <div class="page-head">
        <h2 class="page-title">设备管理</h2>
        <p class="page-desc">查看和管理已注册的 Apple 设备</p>
    </div>

    <div class="devices-toolbar">
        <div class="devices-search">
            <input type="text" id="deviceKeyword" placeholder="搜索序列号 / 备注 / 号码 / UDID">
            <button type="button" class="btn btn-primary btn-sm" id="deviceSearchBtn">搜索</button>
            <button type="button" class="btn btn-text btn-sm" id="deviceResetBtn">重置</button>
            <button type="button" class="btn btn-text btn-sm" id="deviceRefreshBtn">刷新</button>
        </div>
        <div class="devices-meta" id="deviceListMeta">共 0 台设备</div>
    </div>

    <div class="devices-table-wrap">
        <table class="data-table devices-table">
            <thead>
                <tr>
                    <th>序列号</th>
                    <th>备注</th>
                    <th>联系号码</th>
                    <th>UDID</th>
                    <th>设备型号</th>
                    <th>系统版本号</th>
                    <th>监管状态</th>
                    <th>监管锁状态</th>
                    <th>激活锁状态</th>
                    <th>丢失状态</th>
                    <th>通讯次数</th>
                    <th>最后通讯时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="deviceListBody">
                <tr><td colspan="13" class="empty-cell">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="devices-pagination" id="devicePagination">
        <button type="button" class="btn btn-text btn-sm" id="devicePrevBtn" disabled>上一页</button>
        <span id="devicePageInfo">第 1 / 1 页</span>
        <button type="button" class="btn btn-text btn-sm" id="deviceNextBtn" disabled>下一页</button>
    </div>

    <div id="msgBox" class="msg-box hidden"></div>
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

<div id="deleteModal" class="device-modal hidden">
    <div class="device-modal-backdrop" id="deleteModalBackdrop"></div>
    <div class="device-modal-box device-modal-sm">
        <div class="device-modal-header">
            <h3>删除设备</h3>
            <button type="button" class="device-modal-close" id="closeDeleteModal" aria-label="关闭">&times;</button>
        </div>
        <div class="device-modal-body">
            <div class="device-delete-warn" id="deleteWarnText">删除仅删除设备信息，并不会退出监管，需要退出监管请在 DEP 管理里解绑并关闭激活锁。</div>
            <p class="device-modal-info">设备序列号：<strong id="deleteDeviceSerial"></strong></p>
            <div class="device-modal-actions">
                <button type="button" class="btn btn-text" id="cancelDeleteBtn">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">确认删除</button>
            </div>
        </div>
    </div>
</div>
