<div class="dep-page">
    <div class="page-head">
        <h2 class="page-title">DEP 配置</h2>
        <p class="page-desc">Apple 商务管理 / Device Enrollment Program 账户配置</p>
    </div>

    <form id="depForm" class="dep-form">
        <section class="dep-block">
            <div class="dep-switch-row">
                <div class="dep-switch-info">
                    <h3 class="block-title">DEP 开关</h3>
                    <p class="block-tip">启用后可在策略配置中开启新设备注册时的激活锁</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="dep_enabled" name="dep_enabled">
                    <span class="switch-slider"></span>
                </label>
            </div>

            <div class="dep-sub hidden" id="depFields">
                <div class="form-row">
                    <label for="dep_api">DEP API <span class="required">*</span></label>
                    <input type="text" id="dep_api" name="dep_api" placeholder="DEP 服务器 API 地址">
                </div>
                <div class="form-row">
                    <label for="dep_api_name">DEP API Name</label>
                    <input type="text" id="dep_api_name" name="dep_api_name">
                    <p class="field-tip">如果 DEP Server 使用的是 Name，DEP 必须填写对应的 DEP 名称，否则无法使用功能。</p>
                </div>
                <div class="form-row">
                    <label for="dep_api_username">DEP API UserName</label>
                    <input type="text" id="dep_api_username" name="dep_api_username" autocomplete="off">
                </div>
                <div class="form-row">
                    <label for="dep_api_password">DEP API PassWord</label>
                    <input type="password" id="dep_api_password" name="dep_api_password" placeholder="留空则不修改" autocomplete="new-password">
                </div>
                <div class="dep-switch-item dep-ssl-row">
                    <div>
                        <span>SSL 证书验证</span>
                        <p class="field-tip">DEP 服务器使用自签名证书或 Windows 缺 CA 证书时可关闭</p>
                    </div>
                    <label class="switch switch-sm">
                        <input type="checkbox" id="dep_ssl_verify" name="dep_ssl_verify">
                        <span class="switch-slider"></span>
                    </label>
                </div>
            </div>
        </section>

        <div class="form-actions dep-actions">
            <button type="submit" class="btn btn-primary" id="saveDepBtn">保存配置</button>
        </div>
    </form>

    <div id="msgBox" class="msg-box hidden"></div>
</div>
