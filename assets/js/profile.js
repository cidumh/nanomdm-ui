(function () {
    var form = document.getElementById('profileForm');
    var msgBox = document.getElementById('msgBox');
    var saveBtn = document.getElementById('saveProfileBtn');
    var logoutBtn = document.getElementById('logoutBtn');
    var userAgreementEnabled = document.getElementById('user_agreement_enabled');
    var userAgreementFields = document.getElementById('userAgreementFields');
    var scepEnabled = document.getElementById('scep_enabled');
    var scepFields = document.getElementById('scepFields');

    if (!form) return;

    function toggleSection(checkbox, container) {
        if (!checkbox || !container) return;
        container.classList.toggle('hidden', !checkbox.checked);
    }

    if (userAgreementEnabled && userAgreementFields) {
        userAgreementEnabled.addEventListener('change', function () {
            toggleSection(userAgreementEnabled, userAgreementFields);
        });
    }

    if (scepEnabled && scepFields) {
        scepEnabled.addEventListener('change', function () {
            toggleSection(scepEnabled, scepFields);
        });
    }

    function fillForm(data) {
        document.getElementById('profile_name').value = data.profile_name || '';
        document.getElementById('profile_description').value = data.profile_description || '';
        document.getElementById('org_name').value = data.org_name || '';
        document.getElementById('profile_identifier').value = data.profile_identifier || '';
        document.getElementById('mdm_server_url').value = data.mdm_server_url || '';
        document.getElementById('mdm_checkin_url').value = data.mdm_checkin_url || '';
        document.getElementById('apns_topic_id').value = data.apns_topic_id || '';
        document.getElementById('mdm_payload_identifier').value = data.mdm_payload_identifier || '';
        userAgreementEnabled.checked = !!data.user_agreement_enabled;
        document.getElementById('user_agreement_content').value = data.user_agreement_content || '';
        scepEnabled.checked = !!data.scep_enabled;
        document.getElementById('scep_url').value = data.scep_url || '';
        document.getElementById('scep_challenge').value = data.scep_challenge || '';
        document.getElementById('scep_identifier').value = data.scep_identifier || '';
        toggleSection(userAgreementEnabled, userAgreementFields);
        toggleSection(scepEnabled, scepFields);
    }

    function collectData() {
        return {
            profile_name: document.getElementById('profile_name').value.trim(),
            profile_description: document.getElementById('profile_description').value.trim(),
            org_name: document.getElementById('org_name').value.trim(),
            profile_identifier: document.getElementById('profile_identifier').value.trim(),
            mdm_server_url: document.getElementById('mdm_server_url').value.trim(),
            mdm_checkin_url: document.getElementById('mdm_checkin_url').value.trim(),
            apns_topic_id: document.getElementById('apns_topic_id').value.trim(),
            mdm_payload_identifier: document.getElementById('mdm_payload_identifier').value.trim(),
            user_agreement_enabled: userAgreementEnabled.checked,
            user_agreement_content: document.getElementById('user_agreement_content').value.trim(),
            scep_enabled: scepEnabled.checked,
            scep_url: document.getElementById('scep_url').value.trim(),
            scep_challenge: document.getElementById('scep_challenge').value.trim(),
            scep_identifier: document.getElementById('scep_identifier').value.trim()
        };
    }

    function validate(data) {
        if (!data.mdm_server_url) {
            MDM.showMsg(msgBox, '请填写 MDM ServerURL', 'error');
            return false;
        }
        if (data.user_agreement_enabled && !data.user_agreement_content) {
            MDM.showMsg(msgBox, '开启用户协议时请填写协议内容', 'error');
            return false;
        }
        if (data.scep_enabled && !data.scep_url) {
            MDM.showMsg(msgBox, '开启 SCEP 时请填写 SCEP URL 地址', 'error');
            return false;
        }
        return true;
    }

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    }).catch(function () {
        window.location.href = 'index.php';
    });

    MDM.api('api/profile/get.php').then(function (res) {
        if (res.code !== 0 || !res.data) {
            MDM.showMsg(msgBox, res.msg || '加载配置失败', 'error');
            return;
        }
        fillForm(res.data);
    }).catch(function () {
        MDM.showMsg(msgBox, '加载配置失败', 'error');
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        var data = collectData();
        if (!validate(data)) return;

        saveBtn.disabled = true;
        saveBtn.textContent = '保存中...';

        MDM.api('api/profile/save.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                    if (res.data) fillForm(res.data);
                } else {
                    MDM.showMsg(msgBox, res.msg, 'error');
                }
                saveBtn.disabled = false;
                saveBtn.textContent = '保存配置';
            })
            .catch(function () {
                MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = '保存配置';
            });
    });

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            MDM.api('api/auth/logout.php').then(function () {
                window.location.href = 'index.php';
            });
        });
    }
})();
