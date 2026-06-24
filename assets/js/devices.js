(function () {
    var listBody = document.getElementById('deviceListBody');
    if (!listBody) return;

    var msgBox = document.getElementById('msgBox');
    var keywordInput = document.getElementById('deviceKeyword');
    var searchBtn = document.getElementById('deviceSearchBtn');
    var resetBtn = document.getElementById('deviceResetBtn');
    var refreshBtn = document.getElementById('deviceRefreshBtn');
    var listMeta = document.getElementById('deviceListMeta');
    var prevBtn = document.getElementById('devicePrevBtn');
    var nextBtn = document.getElementById('deviceNextBtn');
    var pageInfo = document.getElementById('devicePageInfo');

    var remarkModal = document.getElementById('remarkModal');
    var remarkSerialEl = document.getElementById('remarkDeviceSerial');
    var remarkInput = document.getElementById('remarkInput');
    var confirmRemarkBtn = document.getElementById('confirmRemarkBtn');

    var phoneModal = document.getElementById('phoneModal');
    var phoneSerialEl = document.getElementById('phoneDeviceSerial');
    var phoneInput = document.getElementById('phoneInput');
    var confirmPhoneBtn = document.getElementById('confirmPhoneBtn');

    var deleteModal = document.getElementById('deleteModal');
    var deleteSerialEl = document.getElementById('deleteDeviceSerial');
    var deleteWarnText = document.getElementById('deleteWarnText');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    var state = {
        page: 1,
        totalPages: 1,
        keyword: '',
        remarkSerial: '',
        phoneSerial: '',
        deleteSerial: '',
        deleteStep: 0,
        rowCache: {}
    };

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function notify(msg, type) {
        MDM.showMsg(msgBox, msg, type);
        if (msgBox && msgBox.scrollIntoView) {
            msgBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function supervisionStatusClass(text) {
        if (text === '监管中') return 'status-green';
        if (text === '未监管') return 'status-red';
        return 'status-neutral';
    }

    function lockStatusClass(text) {
        if (text === '开启') return 'status-green';
        if (text === '关闭') return 'status-red';
        return 'status-neutral';
    }

    function lostStatusClass(text) {
        if (text === '开启') return 'status-red';
        if (text === '关闭') return 'status-green';
        return 'status-neutral';
    }

    function renderStatusBadge(text, statusClass) {
        var label = text || '-';
        return '<span class="device-status-badge ' + statusClass + '">' + escapeHtml(label) + '</span>';
    }

    function lastCommClass(lastCommAt) {
        if (!lastCommAt || lastCommAt === '-') {
            return 'status-red';
        }
        var normalized = String(lastCommAt).replace(' ', 'T');
        var ts = Date.parse(normalized);
        if (isNaN(ts)) {
            ts = Date.parse(String(lastCommAt).replace(/-/g, '/'));
        }
        if (isNaN(ts)) {
            return '';
        }
        var hours = (Date.now() - ts) / (1000 * 60 * 60);
        return hours <= 72 ? 'status-green' : 'status-red';
    }

    function bindModalClose(modal, closeIds) {
        closeIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', function () {
                    modal.classList.add('hidden');
                });
            }
        });
    }

    bindModalClose(remarkModal, ['closeRemarkModal', 'remarkModalBackdrop', 'cancelRemarkBtn']);
    bindModalClose(phoneModal, ['closePhoneModal', 'phoneModalBackdrop', 'cancelPhoneBtn']);
    bindModalClose(deleteModal, ['closeDeleteModal', 'deleteModalBackdrop', 'cancelDeleteBtn']);

    function renderRows(items) {
        state.rowCache = {};
        if (!items.length) {
            listBody.innerHTML = '<tr><td colspan="13" class="empty-cell">暂无设备</td></tr>';
            return;
        }

        items.forEach(function (item) {
            state.rowCache[item.serial_number] = item;
        });

        listBody.innerHTML = items.map(function (item) {
            var serial = escapeHtml(item.serial_number);
            return '<tr>'
                + '<td class="serial-cell">' + serial + '</td>'
                + '<td>' + escapeHtml(item.remark || '-') + '</td>'
                + '<td>' + escapeHtml(item.contact_phone || '-') + '</td>'
                + '<td class="udid-cell" title="' + escapeHtml(item.udid) + '">' + escapeHtml(item.udid) + '</td>'
                + '<td>' + escapeHtml(item.device_model || '-') + '</td>'
                + '<td>' + escapeHtml(item.os_version || '-') + '</td>'
                + '<td class="status-cell">' + renderStatusBadge(item.supervision_status_text, supervisionStatusClass(item.supervision_status_text)) + '</td>'
                + '<td class="status-cell">' + renderStatusBadge(item.supervision_lock_text, lockStatusClass(item.supervision_lock_text)) + '</td>'
                + '<td class="status-cell">' + renderStatusBadge(item.activation_lock_text, lockStatusClass(item.activation_lock_text)) + '</td>'
                + '<td class="status-cell">' + renderStatusBadge(item.lost_status_text, lostStatusClass(item.lost_status_text)) + '</td>'
                + '<td>' + escapeHtml(item.comm_count) + '</td>'
                + '<td class="time-cell ' + lastCommClass(item.last_comm_at) + '">' + escapeHtml(item.last_comm_at || '-') + '</td>'
                + '<td class="action-cell">'
                + '<div class="device-action-group">'
                + '<button type="button" class="device-action-btn device-action-remark" data-action="remark" data-serial="' + serial + '">设置备注</button>'
                + '<button type="button" class="device-action-btn device-action-phone" data-action="phone" data-serial="' + serial + '">设置号码</button>'
                + '<button type="button" class="device-action-btn device-action-manage" data-action="manage" data-serial="' + serial + '">管理</button>'
                + '<button type="button" class="device-action-btn device-action-delete" data-action="delete" data-serial="' + serial + '">删除</button>'
                + '</div>'
                + '</td>'
                + '</tr>';
        }).join('');
    }

    function updatePagination(data) {
        state.page = data.page || 1;
        state.totalPages = data.total_pages || 1;
        listMeta.textContent = '共 ' + (data.total || 0) + ' 台设备';
        pageInfo.textContent = '第 ' + state.page + ' / ' + state.totalPages + ' 页';
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= state.totalPages;
    }

    function loadList() {
        listBody.innerHTML = '<tr><td colspan="13" class="empty-cell">加载中...</td></tr>';
        MDM.api('api/devices/list.php?page=' + encodeURIComponent(state.page) + '&keyword=' + encodeURIComponent(state.keyword))
            .then(function (res) {
                if (res.code !== 0) {
                    notify(res.msg || '加载失败', 'error');
                    listBody.innerHTML = '<tr><td colspan="13" class="empty-cell">加载失败</td></tr>';
                    return;
                }
                renderRows(res.data.items || []);
                updatePagination(res.data || {});
            })
            .catch(function () {
                notify('加载失败', 'error');
                listBody.innerHTML = '<tr><td colspan="13" class="empty-cell">加载失败</td></tr>';
            });
    }

    listBody.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;

        var action = btn.getAttribute('data-action');
        var serial = btn.getAttribute('data-serial') || '';

        if (action === 'remark') {
            var remarkItem = state.rowCache[serial] || {};
            state.remarkSerial = serial;
            remarkSerialEl.textContent = serial;
            remarkInput.value = remarkItem.remark || '';
            remarkModal.classList.remove('hidden');
            remarkInput.focus();
            return;
        }

        if (action === 'phone') {
            var phoneItem = state.rowCache[serial] || {};
            state.phoneSerial = serial;
            phoneSerialEl.textContent = serial;
            phoneInput.value = phoneItem.contact_phone || '';
            phoneModal.classList.remove('hidden');
            phoneInput.focus();
            return;
        }

        if (action === 'manage') {
            window.location.href = 'index.php?page=device_manage&serial=' + encodeURIComponent(serial);
            return;
        }

        if (action === 'delete') {
            state.deleteSerial = serial;
            state.deleteStep = 0;
            deleteSerialEl.textContent = serial;
            deleteWarnText.textContent = '删除仅删除设备信息，并不会退出监管，需要退出监管请在 DEP 管理里解绑并关闭激活锁。';
            confirmDeleteBtn.textContent = '确认删除';
            deleteModal.classList.remove('hidden');
        }
    });

    confirmRemarkBtn.addEventListener('click', function () {
        if (!state.remarkSerial) return;
        confirmRemarkBtn.disabled = true;
        MDM.api('api/devices/update_remark.php', {
            method: 'POST',
            body: {
                serial_number: state.remarkSerial,
                remark: remarkInput.value
            }
        }).then(function (res) {
            confirmRemarkBtn.disabled = false;
            if (res.code === 0) {
                remarkModal.classList.add('hidden');
                notify(res.msg || '备注已保存', 'success');
                loadList();
            } else {
                notify(res.msg || '保存失败', 'error');
            }
        }).catch(function () {
            confirmRemarkBtn.disabled = false;
            notify('保存失败', 'error');
        });
    });

    confirmPhoneBtn.addEventListener('click', function () {
        if (!state.phoneSerial) return;
        confirmPhoneBtn.disabled = true;
        MDM.api('api/devices/update_phone.php', {
            method: 'POST',
            body: {
                serial_number: state.phoneSerial,
                contact_phone: phoneInput.value
            }
        }).then(function (res) {
            confirmPhoneBtn.disabled = false;
            if (res.code === 0) {
                phoneModal.classList.add('hidden');
                notify(res.msg || '联系号码已保存', 'success');
                loadList();
            } else {
                notify(res.msg || '保存失败', 'error');
            }
        }).catch(function () {
            confirmPhoneBtn.disabled = false;
            notify('保存失败', 'error');
        });
    });

    confirmDeleteBtn.addEventListener('click', function () {
        if (!state.deleteSerial) return;

        if (state.deleteStep === 0) {
            state.deleteStep = 1;
            deleteWarnText.textContent = '请再次确认：确定删除设备 ' + state.deleteSerial + ' 吗？此操作不可恢复。';
            confirmDeleteBtn.textContent = '再次确认删除';
            return;
        }

        confirmDeleteBtn.disabled = true;
        MDM.api('api/devices/delete.php', {
            method: 'POST',
            body: { serial_number: state.deleteSerial }
        }).then(function (res) {
            confirmDeleteBtn.disabled = false;
            if (res.code === 0) {
                deleteModal.classList.add('hidden');
                state.deleteStep = 0;
                confirmDeleteBtn.textContent = '确认删除';
                notify(res.msg || '设备已删除', 'success');
                if (state.page > 1 && listBody.querySelectorAll('tr').length <= 1) {
                    state.page -= 1;
                }
                loadList();
            } else {
                notify(res.msg || '删除失败', 'error');
            }
        }).catch(function () {
            confirmDeleteBtn.disabled = false;
            notify('删除失败', 'error');
        });
    });

    searchBtn.addEventListener('click', function () {
        state.keyword = keywordInput.value.trim();
        state.page = 1;
        loadList();
    });

    resetBtn.addEventListener('click', function () {
        keywordInput.value = '';
        state.keyword = '';
        state.page = 1;
        loadList();
    });

    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            loadList();
        });
    }

    keywordInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchBtn.click();
        }
    });

    prevBtn.addEventListener('click', function () {
        if (state.page <= 1) return;
        state.page -= 1;
        loadList();
    });

    nextBtn.addEventListener('click', function () {
        if (state.page >= state.totalPages) return;
        state.page += 1;
        loadList();
    });

    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            MDM.api('api/auth/logout.php').then(function () {
                window.location.href = 'index.php';
            });
        });
    }

    loadList();
})();
