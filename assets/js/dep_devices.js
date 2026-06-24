(function () {
    var deviceListBody = document.getElementById('deviceListBody');
    if (!deviceListBody) return;

    var msgBox = document.getElementById('msgBox');
    var searchInput = document.getElementById('deviceSearchInput');
    var searchBtn = document.getElementById('deviceSearchBtn');
    var searchReset = document.getElementById('deviceSearchReset');
    var listMeta = document.getElementById('deviceListMeta');
    var prevBtn = document.getElementById('devicePrevBtn');
    var nextBtn = document.getElementById('deviceNextBtn');
    var pageInfo = document.getElementById('devicePageInfo');
    var pagination = document.getElementById('devicePagination');

    var bindModal = document.getElementById('bindProfileModal');
    var bindModalTitle = document.getElementById('bindModalTitle');
    var bindDeviceSerial = document.getElementById('bindDeviceSerial');
    var bindProfileSelect = document.getElementById('bindProfileSelect');
    var confirmBindBtn = document.getElementById('confirmBindBtn');
    var closeBindModal = document.getElementById('closeBindModal');
    var bindModalBackdrop = document.getElementById('bindModalBackdrop');
    var cancelBindBtn = document.getElementById('cancelBindBtn');

    var disownModal = document.getElementById('disownConfirmModal');
    var disownDeviceSerial = document.getElementById('disownDeviceSerial');
    var confirmDisownBtn = document.getElementById('confirmDisownBtn');
    var closeDisownModal = document.getElementById('closeDisownModal');
    var disownModalBackdrop = document.getElementById('disownModalBackdrop');
    var cancelDisownBtn = document.getElementById('cancelDisownBtn');

    var activationLockModal = document.getElementById('activationLockModal');
    var activationLockSerial = document.getElementById('activationLockSerial');
    var activationLockMessage = document.getElementById('activationLockMessage');
    var confirmActivationLockBtn = document.getElementById('confirmActivationLockBtn');
    var closeActivationLockModal = document.getElementById('closeActivationLockModal');
    var activationLockBackdrop = document.getElementById('activationLockBackdrop');
    var cancelActivationLockBtn = document.getElementById('cancelActivationLockBtn');

    var state = {
        mode: 'list',
        currentPage: 0,
        pageCursors: [''],
        moreToFollow: false,
        profiles: [],
        bindSerial: '',
        bindIsEdit: false,
        disownSerial: '',
        disownStep: 0,
        activationLockSerial: '',
        devicesLoaded: false
    };

    function isBusyResponse(res) {
        return res && res.code === -1;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function notify(msg, type, hideFn) {
        if (typeof hideFn === 'function') {
            hideFn();
        }
        MDM.showMsg(msgBox, msg, type);
        if (msgBox && msgBox.scrollIntoView) {
            msgBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function showBindModal() {
        bindModal.classList.remove('hidden');
    }

    function hideBindModal() {
        bindModal.classList.add('hidden');
        state.bindSerial = '';
    }

    function showDisownModal() {
        disownModal.classList.remove('hidden');
        state.disownStep = 0;
        confirmDisownBtn.textContent = '确认解绑';
    }

    function hideDisownModal() {
        disownModal.classList.add('hidden');
        state.disownSerial = '';
        state.disownStep = 0;
        confirmDisownBtn.textContent = '确认解绑';
    }

    if (closeBindModal) closeBindModal.addEventListener('click', hideBindModal);
    if (bindModalBackdrop) bindModalBackdrop.addEventListener('click', hideBindModal);
    if (cancelBindBtn) cancelBindBtn.addEventListener('click', hideBindModal);

    if (closeDisownModal) closeDisownModal.addEventListener('click', hideDisownModal);
    if (disownModalBackdrop) disownModalBackdrop.addEventListener('click', hideDisownModal);
    if (cancelDisownBtn) cancelDisownBtn.addEventListener('click', hideDisownModal);

    function showActivationLockModal() {
        activationLockModal.classList.remove('hidden');
    }

    function hideActivationLockModal() {
        activationLockModal.classList.add('hidden');
        state.activationLockSerial = '';
        if (activationLockMessage) activationLockMessage.value = '';
    }

    if (closeActivationLockModal) closeActivationLockModal.addEventListener('click', hideActivationLockModal);
    if (activationLockBackdrop) activationLockBackdrop.addEventListener('click', hideActivationLockModal);
    if (cancelActivationLockBtn) cancelActivationLockBtn.addEventListener('click', hideActivationLockModal);

    function renderDeviceRows(devices) {
        if (!devices.length) {
            deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">暂无设备</td></tr>';
            return;
        }

        deviceListBody.innerHTML = devices.map(function (d) {
            var hasProfile = d.profile_uuid && d.profile_uuid !== '';
            var bindLabel = hasProfile ? '修改配置文件' : '绑定配置文件';
            var removeBtn = hasProfile
                ? '<button type="button" class="btn-link btn-device-remove" data-serial="' + escapeHtml(d.serial_number) + '" data-uuid="' + escapeHtml(d.profile_uuid) + '">移除配置</button>'
                : '';

            return '<tr>'
                + '<td class="serial-cell">' + escapeHtml(d.serial_number) + '</td>'
                + '<td>' + escapeHtml(d.model) + '</td>'
                + '<td>' + escapeHtml(d.device_family) + '</td>'
                + '<td class="uuid-cell">' + (hasProfile ? escapeHtml(d.profile_uuid) : '-') + '</td>'
                + '<td class="time-cell">' + escapeHtml(d.profile_assign_time) + '</td>'
                + '<td class="time-cell">' + escapeHtml(d.profile_push_time) + '</td>'
                + '<td>' + escapeHtml(d.profile_status) + '</td>'
                + '<td>' + escapeHtml(d.device_assigned_by) + '</td>'
                + '<td class="action-cell">'
                + '<button type="button" class="btn-link btn-device-lock" data-serial="' + escapeHtml(d.serial_number) + '">开启激活锁</button>'
                + '<button type="button" class="btn-link btn-device-bind" data-serial="' + escapeHtml(d.serial_number) + '" data-has-profile="' + (hasProfile ? '1' : '0') + '">' + bindLabel + '</button>'
                + removeBtn
                + '<button type="button" class="btn-link btn-link-danger btn-device-disown" data-serial="' + escapeHtml(d.serial_number) + '">解绑设备</button>'
                + '</td>'
                + '</tr>';
        }).join('');

        bindDeviceActions();
    }

    function updatePagination() {
        if (state.mode === 'search') {
            pagination.classList.add('hidden');
            return;
        }
        pagination.classList.remove('hidden');
        pageInfo.textContent = '第 ' + (state.currentPage + 1) + ' 页';
        prevBtn.disabled = state.currentPage <= 0;
        nextBtn.disabled = !state.moreToFollow;
    }

    function loadDevicePage(pageIndex, keepMsg) {
        var cursor = state.pageCursors[pageIndex] || '';
        deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">加载中...</td></tr>';
        if (!keepMsg) {
            MDM.hideMsg(msgBox);
        }

        var body = { limit: 100 };
        if (cursor) {
            body.cursor = cursor;
        }

        return MDM.api('api/dep_manage/list_devices.php', {
            method: 'POST',
            body: body
        }).then(function (res) {
            if (isBusyResponse(res)) return;
            if (res.code !== 0) {
                deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">' + escapeHtml(res.msg || '加载失败') + '</td></tr>';
                MDM.showMsg(msgBox, res.msg || '加载失败', 'error');
                return;
            }

            state.currentPage = pageIndex;
            state.moreToFollow = !!res.data.more_to_follow;

            if (state.moreToFollow && res.data.cursor) {
                state.pageCursors[pageIndex + 1] = res.data.cursor;
            }

            renderDeviceRows(res.data.devices || []);
            listMeta.textContent = '本页 ' + (res.data.devices ? res.data.devices.length : 0) + ' 台'
                + (res.data.fetched_until ? ' · 同步至 ' + res.data.fetched_until : '');
            updatePagination();
        }).catch(function () {
            deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">网络请求失败</td></tr>';
            MDM.showMsg(msgBox, '网络请求失败', 'error');
        });
    }

    function searchDevice(keepMsg) {
        var serial = searchInput.value.trim();
        if (!serial) {
            MDM.showMsg(msgBox, '请输入设备序列号', 'error');
            return;
        }

        state.mode = 'search';
        deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">查询中...</td></tr>';
        if (!keepMsg) {
            MDM.hideMsg(msgBox);
        }

        MDM.api('api/dep_manage/search_device.php', {
            method: 'POST',
            body: { serial: serial }
        }).then(function (res) {
            if (isBusyResponse(res)) return;
            if (res.code !== 0) {
                deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">' + escapeHtml(res.msg || '未找到设备') + '</td></tr>';
                MDM.showMsg(msgBox, res.msg || '未找到设备', 'error');
                listMeta.textContent = '查询：' + serial;
                updatePagination();
                return;
            }

            renderDeviceRows(res.data.devices || []);
            listMeta.textContent = '查询结果：' + serial;
            updatePagination();
        }).catch(function () {
            deviceListBody.innerHTML = '<tr><td colspan="9" class="empty-cell">网络请求失败</td></tr>';
            MDM.showMsg(msgBox, '网络请求失败', 'error');
        });
    }

    function resetToList() {
        state.mode = 'list';
        state.currentPage = 0;
        state.pageCursors = [''];
        searchInput.value = '';
        loadDevicePage(0);
    }

    function loadProfilesForBind() {
        return MDM.api('api/dep_manage/list_profiles.php', { lock: false }).then(function (res) {
            if (res.code !== 0 || !Array.isArray(res.data)) {
                state.profiles = [];
                bindProfileSelect.innerHTML = '<option value="">暂无配置文件</option>';
                return;
            }
            state.profiles = res.data;
            if (!res.data.length) {
                bindProfileSelect.innerHTML = '<option value="">请先在 DEP设备配置 中创建配置</option>';
                return;
            }
            bindProfileSelect.innerHTML = res.data.map(function (p) {
                return '<option value="' + escapeHtml(p.profile_uuid) + '">'
                    + escapeHtml(p.profile_name) + ' (' + escapeHtml(p.profile_uuid) + ')</option>';
            }).join('');
        });
    }

    function openBindDialog(serial, isEdit) {
        state.bindSerial = serial;
        state.bindIsEdit = isEdit;
        bindDeviceSerial.textContent = serial;
        bindModalTitle.textContent = isEdit ? '修改配置文件' : '绑定配置文件';
        confirmBindBtn.textContent = isEdit ? '确认修改' : '确认绑定';
        bindProfileSelect.innerHTML = '<option value="">加载中...</option>';
        showBindModal();
        loadProfilesForBind();
    }

    function confirmBind() {
        var profileUuid = bindProfileSelect.value;
        if (!profileUuid) {
            notify('请选择配置文件', 'error', hideBindModal);
            return;
        }
        if (!state.bindSerial) return;

        MDM.api('api/dep_manage/bind_profile.php', {
            method: 'POST',
            body: { serial: state.bindSerial, profile_uuid: profileUuid }
        }).then(function (res) {
            if (isBusyResponse(res)) return;
            if (res.code === 0) {
                notify(res.msg || '绑定成功', 'success', hideBindModal);
                refreshCurrentView(true);
            } else {
                notify(res.msg || '操作失败', 'error', hideBindModal);
            }
        }).catch(function () {
            notify('网络请求失败', 'error', hideBindModal);
        });
    }

    function removeProfile(serial, profileUuid) {
        if (!confirm('确定要移除该设备的配置文件吗？')) return;

        MDM.api('api/dep_manage/remove_profile.php', {
            method: 'POST',
            body: { serial: serial, profile_uuid: profileUuid }
        }).then(function (res) {
            if (isBusyResponse(res)) return;
            if (res.code === 0) {
                MDM.showMsg(msgBox, res.msg || '移除成功', 'success');
                refreshCurrentView(true);
            } else {
                MDM.showMsg(msgBox, res.msg || '操作失败', 'error');
            }
        }).catch(function () {
            MDM.showMsg(msgBox, '网络请求失败', 'error');
        });
    }

    function openDisownDialog(serial) {
        state.disownSerial = serial;
        disownDeviceSerial.textContent = serial;
        showDisownModal();
    }

    function openActivationLockDialog(serial) {
        state.activationLockSerial = serial;
        activationLockSerial.textContent = serial;
        if (activationLockMessage) activationLockMessage.value = '';
        showActivationLockModal();
    }

    function confirmActivationLock() {
        if (!state.activationLockSerial) return;

        var lostMessage = activationLockMessage ? activationLockMessage.value.trim() : '';
        if (!lostMessage) {
            MDM.showMsg(msgBox, '请填写丢失提示内容', 'error');
            return;
        }

        var serial = state.activationLockSerial;

        MDM.api('api/dep_manage/enable_activation_lock.php', {
            method: 'POST',
            body: {
                serial: serial,
                lost_message: lostMessage
            }
        }).then(function (res) {
            if (isBusyResponse(res)) return;
            if (res.code === 0) {
                var tip = res.msg || ('设备 ' + serial + ' 激活锁已成功开启');
                notify(tip, 'success', hideActivationLockModal);
            } else {
                notify(res.msg || '开启失败', 'error', hideActivationLockModal);
            }
        }).catch(function () {
            notify('网络请求失败', 'error', hideActivationLockModal);
        });
    }

    function confirmDisown() {
        if (!state.disownSerial) return;

        if (state.disownStep === 0) {
            state.disownStep = 1;
            confirmDisownBtn.textContent = '再次确认解绑';
            return;
        }

        MDM.api('api/dep_manage/disown_device.php', {
            method: 'POST',
            body: { serial: state.disownSerial }
        }).then(function (res) {
            if (isBusyResponse(res)) return;
            if (res.code === 0) {
                notify(res.msg || '解绑成功', 'success', hideDisownModal);
                refreshCurrentView(true);
            } else {
                notify(res.msg || '解绑失败', 'error', hideDisownModal);
            }
        }).catch(function () {
            notify('网络请求失败', 'error', hideDisownModal);
        });
    }

    function refreshCurrentView(keepMsg) {
        if (state.mode === 'search') {
            searchDevice(keepMsg);
        } else {
            loadDevicePage(state.currentPage, keepMsg);
        }
    }

    function bindDeviceActions() {
        deviceListBody.querySelectorAll('.btn-device-bind').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openBindDialog(btn.getAttribute('data-serial'), btn.getAttribute('data-has-profile') === '1');
            });
        });

        deviceListBody.querySelectorAll('.btn-device-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeProfile(btn.getAttribute('data-serial'), btn.getAttribute('data-uuid'));
            });
        });

        deviceListBody.querySelectorAll('.btn-device-disown').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openDisownDialog(btn.getAttribute('data-serial'));
            });
        });

        deviceListBody.querySelectorAll('.btn-device-lock').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openActivationLockDialog(btn.getAttribute('data-serial'));
            });
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', searchDevice);
    }
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') searchDevice();
        });
    }
    if (searchReset) {
        searchReset.addEventListener('click', resetToList);
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (state.currentPage > 0) {
                loadDevicePage(state.currentPage - 1);
            }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (state.moreToFollow) {
                loadDevicePage(state.currentPage + 1);
            }
        });
    }
    if (confirmBindBtn) {
        confirmBindBtn.addEventListener('click', confirmBind);
    }
    if (confirmDisownBtn) {
        confirmDisownBtn.addEventListener('click', confirmDisown);
    }
    if (confirmActivationLockBtn) {
        confirmActivationLockBtn.addEventListener('click', confirmActivationLock);
    }

    window.DepDevices = {
        loadIfNeeded: function () {
            if (!state.devicesLoaded) {
                state.devicesLoaded = true;
                loadDevicePage(0);
            }
        },
        refresh: refreshCurrentView
    };
})();
