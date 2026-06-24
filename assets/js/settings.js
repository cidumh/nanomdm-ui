(function () {
    var form = document.getElementById('settingsForm');
    var msgBox = document.getElementById('msgBox');
    var saveBtn = document.getElementById('saveBtn');
    var logoutBtn = document.getElementById('logoutBtn');

    if (!form) return;

    MDM.api('api/auth/check.php')
        .then(function (res) {
            if (res.code !== 0) {
                window.location.href = 'index.php';
            }
        })
        .catch(function () {
            window.location.href = 'index.php';
        });

    MDM.api('api/settings/get.php')
        .then(function (res) {
            if (res.code !== 0 || !res.data) {
                MDM.showMsg(msgBox, res.msg || '加载设置失败', 'error');
                return;
            }
            var d = res.data;
            document.getElementById('site_name').value = d.site_name || '';
            document.getElementById('admin_user').value = d.username || '';
            document.getElementById('footer_icp_text').value = d.footer_icp_text || '';
            document.getElementById('footer_icp_url').value = d.footer_icp_url || '';
            document.getElementById('footer_ga_text').value = d.footer_ga_text || '';
            document.getElementById('footer_ga_url').value = d.footer_ga_url || '';
            document.getElementById('db_host').value = d.db_host || '';
            document.getElementById('db_port').value = d.db_port || 3306;
            document.getElementById('db_name').value = d.db_name || '';
            document.getElementById('db_user').value = d.db_user || '';
        })
        .catch(function () {
            MDM.showMsg(msgBox, '加载设置失败', 'error');
        });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        var pass = document.getElementById('admin_pass').value;
        var pass2 = document.getElementById('admin_pass2').value;

        if (pass !== '' && pass !== pass2) {
            MDM.showMsg(msgBox, '两次输入的管理员密码不一致', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = '保存中...';

        var data = {
            site_name: document.getElementById('site_name').value,
            admin_user: document.getElementById('admin_user').value,
            admin_pass: pass,
            admin_pass2: pass2,
            footer_icp_text: document.getElementById('footer_icp_text').value.trim(),
            footer_icp_url: document.getElementById('footer_icp_url').value.trim(),
            footer_ga_text: document.getElementById('footer_ga_text').value.trim(),
            footer_ga_url: document.getElementById('footer_ga_url').value.trim(),
            db_host: document.getElementById('db_host').value,
            db_port: parseInt(document.getElementById('db_port').value, 10),
            db_name: document.getElementById('db_name').value,
            db_user: document.getElementById('db_user').value,
            db_pass: document.getElementById('db_pass').value
        };

        MDM.api('api/settings/save.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                    if (res.data && res.data.relogin) {
                        setTimeout(function () {
                            window.location.href = 'index.php';
                        }, 1200);
                    } else {
                        saveBtn.disabled = false;
                        saveBtn.textContent = '保存设置';
                    }
                } else {
                    MDM.showMsg(msgBox, res.msg, 'error');
                    saveBtn.disabled = false;
                    saveBtn.textContent = '保存设置';
                }
            })
            .catch(function () {
                MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = '保存设置';
            });
    });

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            MDM.api('api/auth/logout.php')
                .then(function () {
                    window.location.href = 'index.php';
                });
        });
    }
})();
