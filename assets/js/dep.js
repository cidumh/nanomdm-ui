(function () {
    var form = document.getElementById('depForm');
    var msgBox = document.getElementById('msgBox');
    var saveBtn = document.getElementById('saveDepBtn');
    var logoutBtn = document.getElementById('logoutBtn');
    var depEnabled = document.getElementById('dep_enabled');
    var depFields = document.getElementById('depFields');
    var hasPassword = false;

    if (!form) return;

    if (depEnabled && depFields) {
        depEnabled.addEventListener('change', function () {
            depFields.classList.toggle('hidden', !depEnabled.checked);
        });
    }

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    }).catch(function () {
        window.location.href = 'index.php';
    });

    MDM.api('api/dep/get.php').then(function (res) {
        if (res.code !== 0 || !res.data) {
            MDM.showMsg(msgBox, res.msg || '加载配置失败', 'error');
            return;
        }
        var d = res.data;
        depEnabled.checked = !!d.dep_enabled;
        document.getElementById('dep_api').value = d.dep_api || '';
        document.getElementById('dep_api_name').value = d.dep_api_name || '';
        document.getElementById('dep_api_username').value = d.dep_api_username || '';
        document.getElementById('dep_ssl_verify').checked = !!d.dep_ssl_verify;
        hasPassword = !!(d.dep_api_password);
        depFields.classList.toggle('hidden', !depEnabled.checked);
    }).catch(function () {
        MDM.showMsg(msgBox, '加载配置失败', 'error');
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        if (depEnabled.checked && !document.getElementById('dep_api').value.trim()) {
            MDM.showMsg(msgBox, '请填写 DEP API', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = '保存中...';

        var data = {
            dep_enabled: depEnabled.checked,
            dep_api: document.getElementById('dep_api').value.trim(),
            dep_api_name: document.getElementById('dep_api_name').value.trim(),
            dep_api_username: document.getElementById('dep_api_username').value.trim(),
            dep_api_password: document.getElementById('dep_api_password').value,
            dep_ssl_verify: document.getElementById('dep_ssl_verify').checked
        };

        MDM.api('api/dep/save.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                    if (data.dep_api_password) {
                        hasPassword = true;
                        document.getElementById('dep_api_password').value = '';
                    }
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
