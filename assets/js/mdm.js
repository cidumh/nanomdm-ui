(function () {
    var mdmForm = document.getElementById('mdmForm');
    var commandForm = document.getElementById('commandForm');
    var msgBox = document.getElementById('msgBox');
    var saveBtn = document.getElementById('saveMdmBtn');
    var sendBtn = document.getElementById('sendCommandBtn');
    var commandTip = document.getElementById('commandTip');
    var commandResult = document.getElementById('commandResult');
    var logoutBtn = document.getElementById('logoutBtn');
    var deviceUdid = document.getElementById('device_udid');
    var commandContent = document.getElementById('command_content');
    var hasPassword = false;
    var mdmConfigured = false;

    if (!mdmForm) return;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setCommandEnabled(enabled) {
        mdmConfigured = !!enabled;
        deviceUdid.disabled = !enabled;
        commandContent.disabled = !enabled;
        sendBtn.disabled = !enabled;
        commandTip.textContent = enabled
            ? '向指定设备发送 MDM 测试指令，请求地址为 MDM Server URL + /v1/enqueue/ + 设备 UDID'
            : '请先保存 MDM 配置后再使用发送指令';
        commandForm.classList.toggle('is-disabled', !enabled);
    }

    function showCommandResult(res, isSuccess) {
        if (!commandResult) return;

        var data = res.data || {};
        var html = '<div class="result-head ' + (isSuccess ? 'result-success' : 'result-error') + '">'
            + escapeHtml(res.msg || (isSuccess ? '发送成功' : '发送失败'))
            + '</div>';

        if (data.replaced_command_uuid) {
            html += '<div class="result-row"><span class="result-label">替换 CommandUUID</span><span class="result-value mono">' + escapeHtml(data.replaced_command_uuid) + '</span></div>';
        }
        if (data.push_result) {
            html += '<div class="result-row"><span class="result-label">推送 ID</span><span class="result-value mono">' + escapeHtml(data.push_result) + '</span></div>';
        }
        if (data.command_uuid) {
            html += '<div class="result-row"><span class="result-label">Command UUID</span><span class="result-value mono">' + escapeHtml(data.command_uuid) + '</span></div>';
        }
        if (data.request_type) {
            html += '<div class="result-row"><span class="result-label">Request Type</span><span class="result-value">' + escapeHtml(data.request_type) + '</span></div>';
        }
        if (data.request_url) {
            html += '<div class="result-row"><span class="result-label">请求 URL</span><span class="result-value mono">' + escapeHtml(data.request_url) + '</span></div>';
        }
        if (data.raw_response) {
            html += '<div class="result-raw"><span class="result-label">原始响应</span><pre>' + escapeHtml(JSON.stringify(data.raw_response, null, 2)) + '</pre></div>';
        }

        commandResult.innerHTML = html;
        commandResult.classList.remove('hidden');
    }

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    }).catch(function () {
        window.location.href = 'index.php';
    });

    MDM.api('api/mdm/get.php').then(function (res) {
        if (res.code !== 0 || !res.data) {
            MDM.showMsg(msgBox, res.msg || '加载配置失败', 'error');
            return;
        }
        var d = res.data;
        document.getElementById('mdm_server_url').value = d.mdm_server_url || '';
        document.getElementById('mdm_api_username').value = d.mdm_api_username || '';
        hasPassword = !!(d.has_password || d.mdm_api_password);
        setCommandEnabled(!!d.mdm_configured);
    }).catch(function () {
        MDM.showMsg(msgBox, '加载配置失败', 'error');
    });

    mdmForm.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        if (!document.getElementById('mdm_server_url').value.trim()) {
            MDM.showMsg(msgBox, '请填写 MDM Server URL', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = '保存中...';

        var data = {
            mdm_server_url: document.getElementById('mdm_server_url').value.trim(),
            mdm_api_username: document.getElementById('mdm_api_username').value.trim(),
            mdm_api_password: document.getElementById('mdm_api_password').value
        };

        MDM.api('api/mdm/save.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                    if (data.mdm_api_password) {
                        hasPassword = true;
                        document.getElementById('mdm_api_password').value = '';
                    }
                    setCommandEnabled(true);
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

    if (commandForm) {
        commandForm.addEventListener('submit', function (e) {
            e.preventDefault();
            MDM.hideMsg(msgBox);
            if (commandResult) {
                commandResult.classList.add('hidden');
                commandResult.innerHTML = '';
            }

            if (!mdmConfigured) {
                MDM.showMsg(msgBox, '请先保存 MDM 配置', 'error');
                return;
            }

            var udid = deviceUdid.value.trim();
            var content = commandContent.value;
            if (!udid) {
                MDM.showMsg(msgBox, '请填写设备 UDID', 'error');
                return;
            }
            if (!content.trim()) {
                MDM.showMsg(msgBox, '请填写指令内容', 'error');
                return;
            }

            sendBtn.disabled = true;
            sendBtn.textContent = '发送中...';

            MDM.api('api/mdm/send_command.php', {
                method: 'POST',
                body: {
                    device_udid: udid,
                    command_content: content
                }
            }).then(function (res) {
                showCommandResult(res, res.code === 0);
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                } else {
                    MDM.showMsg(msgBox, res.msg, 'error');
                }
                sendBtn.disabled = false;
                sendBtn.textContent = '发送指令';
            }).catch(function () {
                MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
                sendBtn.disabled = false;
                sendBtn.textContent = '发送指令';
            });
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            MDM.api('api/auth/logout.php').then(function () {
                window.location.href = 'index.php';
            });
        });
    }
})();
