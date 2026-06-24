(function () {
    var form = document.getElementById('apnsSaveForm');
    var msgBox = document.getElementById('msgBox');
    var listBody = document.getElementById('apnsListBody');
    var logoutBtn = document.getElementById('logoutBtn');

    if (!form || !listBody) return;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function statusClass(status) {
        if (status === 'expired') return 'apns-status-expired';
        if (status === 'expiring') return 'apns-status-expiring';
        if (status === 'valid') return 'apns-status-valid';
        return '';
    }

    function renderList(list) {
        if (!list || !list.length) {
            listBody.innerHTML = '<tr><td colspan="6" class="empty-cell">暂无证书</td></tr>';
            return;
        }

        listBody.innerHTML = list.map(function (cert) {
            var statusCls = statusClass(cert.status);
            return '<tr>'
                + '<td class="remark-cell">' + escapeHtml(cert.cert_remark) + '</td>'
                + '<td class="topic-cell" title="' + escapeHtml(cert.topic) + '">' + escapeHtml(cert.topic) + '</td>'
                + '<td class="time-cell">' + escapeHtml(cert.not_before) + '</td>'
                + '<td class="time-cell">' + escapeHtml(cert.not_after) + '</td>'
                + '<td class="status-cell ' + statusCls + '">' + escapeHtml(cert.status_text) + '</td>'
                + '<td class="action-cell">'
                + '<button type="button" class="apns-btn-delete btn-apns-delete" data-id="' + cert.id + '">删除</button>'
                + '</td>'
                + '</tr>';
        }).join('');

        bindDeleteButtons();
    }

    function loadList() {
        return MDM.api('api/apns/get.php').then(function (res) {
            if (res.code === 0) {
                renderList(res.data && res.data.list ? res.data.list : []);
            }
        });
    }

    function bindDeleteButtons() {
        listBody.querySelectorAll('.btn-apns-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-id'), 10);
                if (!id) return;
                if (!confirm('确定要删除该 APNS 证书吗？')) return;

                MDM.api('api/apns/delete.php', {
                    method: 'POST',
                    body: { id: id }
                }).then(function (res) {
                    if (res.code === 0) {
                        MDM.showMsg(msgBox, res.msg || '已删除', 'success');
                        loadList();
                    } else {
                        MDM.showMsg(msgBox, res.msg || '删除失败', 'error');
                    }
                }).catch(function () {
                    MDM.showMsg(msgBox, '网络请求失败', 'error');
                });
            });
        });
    }

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    });

    loadList();

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        var data = {
            cert_remark: document.getElementById('apns_cert_remark').value.trim(),
            pem_cert: document.getElementById('apns_pem_cert').value.trim(),
            pem_private_key: document.getElementById('apns_pem_private_key').value.trim()
        };

        if (!data.cert_remark) {
            MDM.showMsg(msgBox, '请填写证书备注', 'error');
            return;
        }
        if (!data.pem_cert) {
            MDM.showMsg(msgBox, '请填写 PEM 证书', 'error');
            return;
        }
        if (!data.pem_private_key) {
            MDM.showMsg(msgBox, '请填写 PEM 私钥', 'error');
            return;
        }

        MDM.api('api/apns/save.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg || '保存成功', 'success');
                    form.reset();
                    loadList();
                } else {
                    MDM.showMsg(msgBox, res.msg || '保存失败', 'error');
                }
            })
            .catch(function () {
                MDM.showMsg(msgBox, '网络请求失败', 'error');
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
