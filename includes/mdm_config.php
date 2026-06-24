<div class="mdm-page">
    <div class="page-head">
        <h2 class="page-title">MDM 配置</h2>
        <p class="page-desc">配置 NanoMDM 服务连接，保存后可使用发送指令测试工具</p>
    </div>

    <form id="mdmForm" class="mdm-form">
        <section class="mdm-block">
            <h3 class="block-title">服务连接</h3>
            <div class="form-row">
                <label for="mdm_server_url">MDM Server URL <span class="required">*</span></label>
                <input type="text" id="mdm_server_url" name="mdm_server_url" placeholder="https://mdm.cidumh.com/">
            </div>
            <div class="form-row">
                <label for="mdm_api_username">API 用户名 <span class="label-hint">（可空）</span></label>
                <input type="text" id="mdm_api_username" name="mdm_api_username" autocomplete="off">
            </div>
            <div class="form-row">
                <label for="mdm_api_password">API 密码 <span class="label-hint">（可空，留空则不修改）</span></label>
                <input type="password" id="mdm_api_password" name="mdm_api_password" placeholder="留空则不修改" autocomplete="new-password">
            </div>
            <div class="form-actions mdm-actions">
                <button type="submit" class="btn btn-primary" id="saveMdmBtn">保存配置</button>
            </div>
        </section>
    </form>

    <section class="mdm-block mdm-command-block" id="commandSection">
        <h3 class="block-title">发送指令</h3>
        <p class="block-tip" id="commandTip">请先保存 MDM 配置后再使用发送指令</p>

        <form id="commandForm" class="mdm-command-form">
            <div class="form-row">
                <label for="device_udid">设备 UDID <span class="required">*</span></label>
                <input type="text" id="device_udid" name="device_udid" placeholder="00008120-00042CDA0C82201E" disabled>
            </div>
            <div class="form-row">
                <label for="command_content">指令内容 <span class="required">*</span></label>
                <p class="field-tip">XML 格式指令内容；包含 <code>_CommandUUID_</code> 或 <code>(CommandUUID)</code> 时将自动替换为随机 UUID</p>
                <textarea id="command_content" name="command_content" rows="12" placeholder="粘贴 MDM 指令 XML 内容" disabled></textarea>
            </div>
            <div class="form-actions mdm-actions">
                <button type="submit" class="btn btn-primary" id="sendCommandBtn" disabled>发送指令</button>
            </div>
        </form>

        <div id="commandResult" class="command-result hidden"></div>
    </section>

    <div id="msgBox" class="msg-box hidden"></div>
</div>
