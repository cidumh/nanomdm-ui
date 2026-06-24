<div class="apns-page">
    <div class="page-head">
        <h2 class="page-title">APNS 证书管理</h2>
        <p class="page-desc">APNS推送证书作用于设备通讯，WEB UI内作用于关闭激活锁，如无需关闭激活锁功能可不添加证书.</p>
    </div>

    <section class="apns-block">
        <h3 class="block-title">证书列表</h3>
        <div class="table-wrap apns-table-wrap">
            <table class="apns-table" id="apnsListTable">
                <thead>
                    <tr>
                        <th>证书备注</th>
                        <th>Topic</th>
                        <th>生效时间</th>
                        <th>过期时间</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="apnsListBody">
                    <tr><td colspan="6" class="empty-cell">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="apns-block">
        <h3 class="block-title">添加证书</h3>
        <p class="block-tip">分别粘贴 PEM 证书与 PEM 私钥内容，保存后将新增一条证书记录。</p>
        <form id="apnsSaveForm" class="apns-save-form">
            <div class="form-row">
                <label for="apns_cert_remark">证书备注 <span class="required">*</span></label>
                <input type="text" id="apns_cert_remark" name="cert_remark" placeholder="例如：生产环境 MDM 推送证书" required>
            </div>
            <div class="form-row">
                <label for="apns_pem_cert">PEM 证书 <span class="required">*</span></label>
                <textarea id="apns_pem_cert" name="pem_cert" rows="8" placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----" required></textarea>
            </div>
            <div class="form-row">
                <label for="apns_pem_private_key">PEM 私钥 <span class="required">*</span></label>
                <textarea id="apns_pem_private_key" name="pem_private_key" rows="8" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----" required></textarea>
            </div>
            <div class="form-actions apns-form-actions">
                <button type="submit" class="btn btn-primary" id="saveApnsBtn">保存证书</button>
            </div>
        </form>
    </section>

    <div id="msgBox" class="msg-box hidden"></div>
</div>
