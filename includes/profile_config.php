<div class="profile-page">
    <div class="page-head">
        <h2 class="page-title">描述文件配置</h2>
        <p class="page-desc">配置 MDM 描述文件基础信息，用于生成 Apple 配置描述文件。设备安装页面：<a href="profile_install.php" target="_blank" rel="noopener">profile_install.php</a></p>
    </div>

    <form id="profileForm" class="profile-form">
        <section class="profile-block">
            <h3 class="block-title">基础信息</h3>
            <div class="form-row">
                <label for="profile_name">配置文件名字</label>
                <input type="text" id="profile_name" name="profile_name" placeholder="CDMH-MDM">
            </div>
            <div class="form-row">
                <label for="profile_description">配置文件描述</label>
                <input type="text" id="profile_description" name="profile_description" placeholder="瓷都名汇-MDM管理系统">
            </div>
            <div class="form-row">
                <label for="org_name">组织名称</label>
                <input type="text" id="org_name" name="org_name" placeholder="瓷都名汇">
            </div>
            <div class="form-row">
                <label for="profile_identifier">配置文件标识</label>
                <input type="text" id="profile_identifier" name="profile_identifier" placeholder="com.cidumh.mdm.server">
            </div>
        </section>

        <section class="profile-block">
            <h3 class="block-title">MDM 服务</h3>
            <div class="form-row">
                <label for="mdm_server_url">MDM ServerURL <span class="required">*</span></label>
                <input type="text" id="mdm_server_url" name="mdm_server_url" placeholder="https://example.com/mdm">
            </div>
            <div class="form-row">
                <label for="mdm_checkin_url">MDM CheckInURL <span class="label-hint">（可选）</span></label>
                <input type="text" id="mdm_checkin_url" name="mdm_checkin_url" placeholder="https://example.com/checkin">
            </div>
            <div class="form-row">
                <label for="apns_topic_id">APNS Topic ID</label>
                <input type="text" id="apns_topic_id" name="apns_topic_id" placeholder="com.apple.mgmt.xxx">
            </div>
            <div class="form-row">
                <label for="mdm_payload_identifier">MDM 配置标识</label>
                <input type="text" id="mdm_payload_identifier" name="mdm_payload_identifier" placeholder="com.cidumh.mdm.mdm">
            </div>
        </section>

        <section class="profile-block">
            <div class="profile-switch-row">
                <div class="profile-switch-info">
                    <h3 class="block-title">用户协议</h3>
                    <p class="block-tip">开启后设备注册时需展示用户协议内容</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="user_agreement_enabled" name="user_agreement_enabled">
                    <span class="switch-slider"></span>
                </label>
            </div>
            <div class="profile-sub hidden" id="userAgreementFields">
                <div class="form-row">
                    <label for="user_agreement_content">协议内容 <span class="required">*</span></label>
                    <textarea id="user_agreement_content" name="user_agreement_content" rows="8" placeholder="请输入用户协议内容"></textarea>
                </div>
            </div>
        </section>

        <section class="profile-block">
            <div class="profile-switch-row">
                <div class="profile-switch-info">
                    <h3 class="block-title">SCEP</h3>
                    <p class="block-tip">开启后描述文件将包含 SCEP 证书申请配置</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="scep_enabled" name="scep_enabled">
                    <span class="switch-slider"></span>
                </label>
            </div>
            <div class="profile-sub hidden" id="scepFields">
                <div class="form-row">
                    <label for="scep_url">SCEP URL 地址 <span class="required">*</span></label>
                    <input type="text" id="scep_url" name="scep_url" placeholder="https://example.com/scep">
                </div>
                <div class="form-row">
                    <label for="scep_challenge">SCEP 挑战码 <span class="label-hint">（可选）</span></label>
                    <input type="text" id="scep_challenge" name="scep_challenge">
                </div>
                <div class="form-row">
                    <label for="scep_identifier">SCEP 配置标识</label>
                    <input type="text" id="scep_identifier" name="scep_identifier" placeholder="com.cidumh.mdm.scep">
                </div>
            </div>
        </section>

        <div class="form-actions profile-actions">
            <button type="submit" class="btn btn-primary" id="saveProfileBtn">保存配置</button>
        </div>
    </form>

    <div id="msgBox" class="msg-box hidden"></div>
</div>
