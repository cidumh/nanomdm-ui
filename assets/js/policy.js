(function () {
    var form = document.getElementById('policyForm');
    var msgBox = document.getElementById('msgBox');
    var saveBtn = document.getElementById('savePolicyBtn');
    var logoutBtn = document.getElementById('logoutBtn');
    var depConfigured = false;

    if (!form) return;

    var toggles = {
        activation_lock: document.getElementById('activation_lock'),
        dns_proxy: document.getElementById('dns_proxy'),
        global_proxy: document.getElementById('global_proxy'),
        func_restriction: document.getElementById('func_restriction')
    };

    var panels = {
        dns_proxy: document.getElementById('dnsProxyFields'),
        global_proxy: document.getElementById('globalProxyFields'),
        func_restriction: document.getElementById('funcRestrictionFields')
    };

    function bindPanel(toggleKey) {
        var el = toggles[toggleKey];
        var panel = panels[toggleKey];
        if (!el || !panel) return;
        el.addEventListener('change', function () {
            panel.classList.toggle('hidden', !el.checked);
        });
    }

    ['dns_proxy', 'global_proxy', 'func_restriction'].forEach(bindPanel);

    var cameraWhitelistEnabled = document.getElementById('camera_whitelist_enabled');
    var cameraWhitelistFields = document.getElementById('cameraWhitelistFields');

    if (cameraWhitelistEnabled && cameraWhitelistFields) {
        cameraWhitelistEnabled.addEventListener('change', function () {
            cameraWhitelistFields.classList.toggle('hidden', !cameraWhitelistEnabled.checked);
        });
    }

    function setCheck(id, val) {
        var el = document.getElementById(id);
        if (el) el.checked = !!val;
    }

    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val || '';
    }

    function updateActivationLockState() {
        var el = toggles.activation_lock;
        var tip = document.getElementById('activationLockTip');
        if (!el) return;

        if (!depConfigured) {
            el.disabled = true;
            if (el.checked) el.checked = false;
            if (tip) {
                tip.textContent = '需先保存 DEP 配置后才能开启';
                tip.classList.add('warn');
            }
        } else {
            el.disabled = false;
            if (tip) {
                tip.textContent = 'DEP 配置已就绪，可开启激活锁';
                tip.classList.remove('warn');
            }
        }
    }

    function fillForm(data) {
        depConfigured = !!data.dep_configured;

        setCheck('activation_lock', depConfigured ? data.activation_lock : false);
        setCheck('dns_proxy', data.dns_proxy);
        setCheck('global_proxy', data.global_proxy);
        setCheck('func_restriction', data.func_restriction);

        setVal('dns_org_name', data.dns_org_name);
        setVal('dns_identifier', data.dns_identifier);
        setVal('dns_server_url', data.dns_server_url);
        setVal('dns_address_1', data.dns_address_1);
        setVal('dns_address_2', data.dns_address_2);
        setVal('proxy_org_name', data.proxy_org_name);
        setVal('proxy_identifier', data.proxy_identifier);
        setVal('proxy_pac_url', data.proxy_pac_url);
        setVal('func_org_name', data.func_org_name);
        setVal('func_identifier', data.func_identifier);

        var func = data.func_restrictions || {};
        document.querySelectorAll('.func-key').forEach(function (input) {
            var key = input.getAttribute('data-key');
            if (key && func.hasOwnProperty(key)) {
                input.checked = !!func[key];
            }
        });
        setVal('allowedCameraRestrictionBundleIDs', func.allowedCameraRestrictionBundleIDs);

        Object.keys(panels).forEach(function (key) {
            if (toggles[key] && panels[key]) {
                panels[key].classList.toggle('hidden', !toggles[key].checked);
            }
        });

        if (cameraWhitelistFields && cameraWhitelistEnabled) {
            cameraWhitelistFields.classList.toggle('hidden', !cameraWhitelistEnabled.checked);
        }

        updateActivationLockState();
    }

    function collectFuncRestrictions() {
        var result = {};
        document.querySelectorAll('.func-key').forEach(function (input) {
            var key = input.getAttribute('data-key');
            if (key) {
                result[key] = input.checked;
            }
        });
        result.allowedCameraRestrictionBundleIDs = document.getElementById('allowedCameraRestrictionBundleIDs').value;
        return result;
    }

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    }).catch(function () {
        window.location.href = 'index.php';
    });

    MDM.api('api/policy/get.php').then(function (res) {
        if (res.code === 0 && res.data) {
            fillForm(res.data);
        } else {
            MDM.showMsg(msgBox, res.msg || '加载策略失败', 'error');
        }
    }).catch(function () {
        MDM.showMsg(msgBox, '加载策略失败', 'error');
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        if (toggles.activation_lock && toggles.activation_lock.checked && !depConfigured) {
            MDM.showMsg(msgBox, '请先完成 DEP 配置后再开启激活锁', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = '保存中...';

        var data = {
            activation_lock: toggles.activation_lock ? toggles.activation_lock.checked : false,
            dns_proxy: toggles.dns_proxy ? toggles.dns_proxy.checked : false,
            dns_org_name: document.getElementById('dns_org_name').value,
            dns_identifier: document.getElementById('dns_identifier').value,
            dns_server_url: document.getElementById('dns_server_url').value,
            dns_address_1: document.getElementById('dns_address_1').value,
            dns_address_2: document.getElementById('dns_address_2').value,
            global_proxy: toggles.global_proxy ? toggles.global_proxy.checked : false,
            proxy_org_name: document.getElementById('proxy_org_name').value,
            proxy_identifier: document.getElementById('proxy_identifier').value,
            proxy_pac_url: document.getElementById('proxy_pac_url').value,
            func_restriction: toggles.func_restriction ? toggles.func_restriction.checked : false,
            func_org_name: document.getElementById('func_org_name').value,
            func_identifier: document.getElementById('func_identifier').value,
            func_restrictions: collectFuncRestrictions()
        };

        MDM.api('api/policy/save.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                } else {
                    MDM.showMsg(msgBox, res.msg, 'error');
                }
                saveBtn.disabled = false;
                saveBtn.textContent = '保存策略';
            })
            .catch(function () {
                MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = '保存策略';
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
