(function () {
    var form = document.getElementById('installForm');
    var msgBox = document.getElementById('msgBox');
    var submitBtn = document.getElementById('submitBtn');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        var pass = document.getElementById('admin_pass').value;
        var pass2 = document.getElementById('admin_pass2').value;

        if (pass !== pass2) {
            MDM.showMsg(msgBox, '两次输入的密码不一致', 'error');
            return;
        }

        if (pass.length < 6) {
            MDM.showMsg(msgBox, '管理员密码至少6位', 'error');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = '安装中...';

        var data = {
            db_host: document.getElementById('db_host').value,
            db_port: parseInt(document.getElementById('db_port').value, 10),
            db_name: document.getElementById('db_name').value,
            db_user: document.getElementById('db_user').value,
            db_pass: document.getElementById('db_pass').value,
            site_name: document.getElementById('site_name').value,
            admin_user: document.getElementById('admin_user').value,
            admin_pass: pass
        };

        MDM.api('api/install/setup.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    var tip = (res.data && res.data.tip) ? res.data.tip : '安装成功';
                    MDM.showMsg(msgBox, res.msg + '。' + tip, 'success');
                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 2500);
                } else {
                    MDM.showMsg(msgBox, res.msg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '开始安装';
                }
            })
            .catch(function () {
                MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = '开始安装';
            });
    });
})();
