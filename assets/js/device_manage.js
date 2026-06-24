(function () {
    var page = document.getElementById('deviceManagePage');
    if (!page) return;

    var serial = (page.getAttribute('data-serial') || '').trim();
    var msgBox = document.getElementById('msgBox');
    var loadingBox = document.getElementById('manageLoadingBox');
    var errorBox = document.getElementById('manageErrorBox');
    var mainBox = document.getElementById('manageMain');
    var detailGrid = document.getElementById('deviceDetailGrid');
    var statusBanner = document.getElementById('deviceStatusBanner');
    var manageMeta = document.getElementById('manageDeviceMeta');
    var updateDeviceInfoBtn = document.getElementById('updateDeviceInfoBtn');
    var refreshDeviceInfoBtn = document.getElementById('refreshDeviceInfoBtn');
    var updateProfileListBtn = document.getElementById('updateProfileListBtn');
    var refreshProfileListBtn = document.getElementById('refreshProfileListBtn');
    var updateLocationBtn = document.getElementById('updateLocationBtn');
    var deviceProfileListBody = document.getElementById('deviceProfileListBody');
    var deviceProfileListMeta = document.getElementById('deviceProfileListMeta');

    var remarkModal = document.getElementById('remarkModal');
    var remarkSerialEl = document.getElementById('remarkDeviceSerial');
    var remarkInput = document.getElementById('remarkInput');
    var confirmRemarkBtn = document.getElementById('confirmRemarkBtn');

    var phoneModal = document.getElementById('phoneModal');
    var phoneSerialEl = document.getElementById('phoneDeviceSerial');
    var phoneInput = document.getElementById('phoneInput');
    var confirmPhoneBtn = document.getElementById('confirmPhoneBtn');

    var deviceNameModal = document.getElementById('deviceNameModal');
    var deviceNameSerialEl = document.getElementById('deviceNameSerial');
    var deviceNameInput = document.getElementById('deviceNameInput');
    var confirmDeviceNameBtn = document.getElementById('confirmDeviceNameBtn');

    var restartModal = document.getElementById('restartModal');
    var restartSerialEl = document.getElementById('restartDeviceSerial');
    var confirmRestartBtn = document.getElementById('confirmRestartBtn');

    var shutdownModal = document.getElementById('shutdownModal');
    var shutdownSerialEl = document.getElementById('shutdownDeviceSerial');
    var confirmShutdownBtn = document.getElementById('confirmShutdownBtn');

    var wallpaperModal = document.getElementById('wallpaperModal');
    var wallpaperSerialEl = document.getElementById('wallpaperDeviceSerial');
    var wallpaperFileInput = document.getElementById('wallpaperFileInput');
    var wallpaperPreviewWrap = document.getElementById('wallpaperPreviewWrap');
    var wallpaperPreviewImg = document.getElementById('wallpaperPreviewImg');
    var wallpaperFileName = document.getElementById('wallpaperFileName');
    var confirmWallpaperBtn = document.getElementById('confirmWallpaperBtn');

    var deviceLockModal = document.getElementById('deviceLockModal');
    var deviceLockSerialEl = document.getElementById('deviceLockSerial');
    var deviceLockMessageInput = document.getElementById('deviceLockMessageInput');
    var deviceLockPhoneInput = document.getElementById('deviceLockPhoneInput');
    var confirmDeviceLockBtn = document.getElementById('confirmDeviceLockBtn');

    var enableLostModeModal = document.getElementById('enableLostModeModal');
    var enableLostModeSerialEl = document.getElementById('enableLostModeSerial');
    var enableLostModeFootnoteInput = document.getElementById('enableLostModeFootnoteInput');
    var enableLostModeMessageInput = document.getElementById('enableLostModeMessageInput');
    var enableLostModePhoneInput = document.getElementById('enableLostModePhoneInput');
    var confirmEnableLostModeBtn = document.getElementById('confirmEnableLostModeBtn');

    var updateLocationModal = document.getElementById('updateLocationModal');
    var updateLocationSerialEl = document.getElementById('updateLocationSerial');
    var confirmUpdateLocationBtn = document.getElementById('confirmUpdateLocationBtn');

    var disableLostModeModal = document.getElementById('disableLostModeModal');
    var disableLostModeSerialEl = document.getElementById('disableLostModeSerial');
    var confirmDisableLostModeBtn = document.getElementById('confirmDisableLostModeBtn');

    var playLostModeSoundModal = document.getElementById('playLostModeSoundModal');
    var playLostModeSoundSerialEl = document.getElementById('playLostModeSoundSerial');
    var confirmPlayLostModeSoundBtn = document.getElementById('confirmPlayLostModeSoundBtn');

    var clearPasscodeModal = document.getElementById('clearPasscodeModal');
    var clearPasscodeSerialEl = document.getElementById('clearPasscodeSerial');
    var clearPasscodeTokenStatusEl = document.getElementById('clearPasscodeTokenStatus');
    var confirmClearPasscodeBtn = document.getElementById('confirmClearPasscodeBtn');

    var eraseDeviceModal = document.getElementById('eraseDeviceModal');
    var eraseDeviceSerialEl = document.getElementById('eraseDeviceSerial');
    var confirmEraseDeviceBtn = document.getElementById('confirmEraseDeviceBtn');

    var disableActivationLockModal = document.getElementById('disableActivationLockModal');
    var enableActivationLockModal = document.getElementById('enableActivationLockModal');
    var enableActivationLockSerialEl = document.getElementById('enableActivationLockSerial');
    var enableActivationLockMessageInput = document.getElementById('enableActivationLockMessageInput');
    var confirmEnableActivationLockBtn = document.getElementById('confirmEnableActivationLockBtn');
    var disableActivationLockSerialEl = document.getElementById('disableActivationLockSerial');
    var disableActivationLockTopicEl = document.getElementById('disableActivationLockTopic');
    var disableActivationLockBypassStatusEl = document.getElementById('disableActivationLockBypassStatus');
    var disableActivationLockOrgNameInput = document.getElementById('disableActivationLockOrgNameInput');
    var disableActivationLockGuidInput = document.getElementById('disableActivationLockGuidInput');
    var confirmDisableActivationLockBtn = document.getElementById('confirmDisableActivationLockBtn');

    var connectDeviceBtn = document.getElementById('connectDeviceBtn');

    var perLogBody = document.getElementById('perLogBody');
    var perLogMeta = document.getElementById('perLogMeta');
    var perLogPageInfo = document.getElementById('perLogPageInfo');
    var perLogPrevBtn = document.getElementById('perLogPrevBtn');
    var perLogNextBtn = document.getElementById('perLogNextBtn');
    var perLogJumpInput = document.getElementById('perLogJumpInput');
    var perLogJumpBtn = document.getElementById('perLogJumpBtn');
    var perLogDateFrom = document.getElementById('perLogDateFrom');
    var perLogDateTo = document.getElementById('perLogDateTo');
    var perLogKeyword = document.getElementById('perLogKeyword');

    var VALID_TABS = ['detail', 'actions', 'profiles', 'logs'];

    var state = {
        device: null,
        logPage: 1,
        logTotalPages: 1,
        logsLoaded: false,
        profilesLoaded: false,
        profileDefaults: null,
        profileList: [],
        installProfileBase64: '',
        installProfileFilename: '',
        activeTab: normalizeTab(page.getAttribute('data-initial-tab') || getTabFromUrl()),
        wallpaperBase64: ''
    };

    function normalizeTab(tab) {
        tab = (tab || '').trim();
        return VALID_TABS.indexOf(tab) >= 0 ? tab : 'detail';
    }

    function getTabFromUrl() {
        try {
            return normalizeTab(new URLSearchParams(window.location.search).get('tab'));
        } catch (e) {
            return 'detail';
        }
    }

    function syncTabToUrl(tab) {
        tab = normalizeTab(tab);
        try {
            var url = new URL(window.location.href);
            if (tab === 'detail') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }
            var next = url.pathname + url.search + url.hash;
            if (window.location.pathname + window.location.search + window.location.hash !== next) {
                window.history.replaceState({ deviceManageTab: tab }, '', next);
            }
        } catch (e) {
            // ignore URL sync errors
        }
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function closeModal(modal) {
        if (modal) modal.classList.add('hidden');
    }

    function notify(msg, type, afterFn) {
        if (typeof afterFn === 'function') {
            afterFn();
        }
        MDM.showMsg(msgBox, msg, type);
        if (msgBox && msgBox.scrollIntoView) {
            msgBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function formatDateObj(d) {
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }

    function applyDefaultDateRange() {
        var today = new Date();
        var from = new Date(today);
        from.setDate(from.getDate() - 6);
        if (perLogDateFrom) perLogDateFrom.value = formatDateObj(from);
        if (perLogDateTo) perLogDateTo.value = formatDateObj(today);
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
    bindModalClose(deviceNameModal, ['closeDeviceNameModal', 'deviceNameModalBackdrop', 'cancelDeviceNameBtn']);
    bindModalClose(restartModal, ['closeRestartModal', 'restartModalBackdrop', 'cancelRestartBtn']);
    bindModalClose(shutdownModal, ['closeShutdownModal', 'shutdownModalBackdrop', 'cancelShutdownBtn']);
    bindModalClose(wallpaperModal, ['closeWallpaperModal', 'wallpaperModalBackdrop', 'cancelWallpaperBtn']);
    bindModalClose(deviceLockModal, ['closeDeviceLockModal', 'deviceLockModalBackdrop', 'cancelDeviceLockBtn']);
    bindModalClose(enableLostModeModal, ['closeEnableLostModeModal', 'enableLostModeModalBackdrop', 'cancelEnableLostModeBtn']);
    bindModalClose(updateLocationModal, ['closeUpdateLocationModal', 'updateLocationModalBackdrop', 'cancelUpdateLocationBtn']);
    bindModalClose(disableLostModeModal, ['closeDisableLostModeModal', 'disableLostModeModalBackdrop', 'cancelDisableLostModeBtn']);
    bindModalClose(playLostModeSoundModal, ['closePlayLostModeSoundModal', 'playLostModeSoundModalBackdrop', 'cancelPlayLostModeSoundBtn']);
    bindModalClose(clearPasscodeModal, ['closeClearPasscodeModal', 'clearPasscodeModalBackdrop', 'cancelClearPasscodeBtn']);
    bindModalClose(eraseDeviceModal, ['closeEraseDeviceModal', 'eraseDeviceModalBackdrop', 'cancelEraseDeviceBtn']);
    bindModalClose(disableActivationLockModal, ['closeDisableActivationLockModal', 'disableActivationLockModalBackdrop', 'cancelDisableActivationLockBtn']);
    bindModalClose(enableActivationLockModal, ['closeEnableActivationLockModal', 'enableActivationLockModalBackdrop', 'cancelEnableActivationLockBtn']);

    function showError(message) {
        loadingBox.classList.add('hidden');
        mainBox.classList.add('hidden');
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function getEditableActionLabel(action) {
        var labels = {
            remark: '设置备注',
            phone: '设置号码',
            device_name: '修改设备名称'
        };
        return labels[action] || '操作';
    }

    function getDetailValue(label) {
        var details = (state.device && state.device.details) || [];
        for (var i = 0; i < details.length; i++) {
            var item = details[i];
            if (item.label === label && item.value && item.value !== '-') {
                return item.value;
            }
        }
        return '';
    }

    function getDetailIconTone(label) {
        var tones = {
            '备注': 'tone-blue',
            '联系号码': 'tone-teal',
            '设备名字': 'tone-indigo',
            '设备类型': 'tone-purple',
            '设备序列号': 'tone-slate',
            '设备 UDID': 'tone-slate',
            '设备型号标识': 'tone-purple',
            '系统版本': 'tone-green',
            '总容量': 'tone-orange',
            '剩余容量': 'tone-orange',
            '剩余电量': 'tone-yellow',
            'Wi-Fi MAC': 'tone-blue',
            '蓝牙 MAC': 'tone-blue',
            'IMEI': 'tone-teal',
            'MEID': 'tone-teal',
            'ICCID': 'tone-teal',
            '运营商': 'tone-teal',
            '电话号码': 'tone-teal',
            '最后上线时间': 'tone-slate',
            '注册时间': 'tone-slate'
        };
        return tones[label] || 'tone-default';
    }

    function getDetailIconChar(label) {
        if (!label) return '?';
        return label.charAt(0);
    }

    function renderDetailItem(item) {
        var iconTone = getDetailIconTone(item.label);
        var iconChar = getDetailIconChar(item.label);

        if (item.type === 'editable') {
            var actionLabel = getEditableActionLabel(item.action);
            return '<div class="device-detail-item is-editable ' + iconTone + '">'
                + '<div class="device-detail-item-inner">'
                + '<div class="device-detail-icon" aria-hidden="true">' + escapeHtml(iconChar) + '</div>'
                + '<div class="device-detail-body">'
                + '<div class="device-detail-label">' + escapeHtml(item.label) + '</div>'
                + '<div class="device-detail-value-row">'
                + '<div class="device-detail-value">' + escapeHtml(item.value || '-') + '</div>'
                + '<button type="button" class="device-detail-action-btn" data-action="' + escapeHtml(item.action) + '">' + escapeHtml(actionLabel) + '</button>'
                + '</div>'
                + '</div>'
                + '</div>'
                + '</div>';
        }

        var toneClass = item.tone ? ' tone-' + escapeHtml(item.tone) : '';
        return '<div class="device-detail-item ' + iconTone + '">'
            + '<div class="device-detail-item-inner">'
            + '<div class="device-detail-icon" aria-hidden="true">' + escapeHtml(iconChar) + '</div>'
            + '<div class="device-detail-body">'
            + '<div class="device-detail-label">' + escapeHtml(item.label) + '</div>'
            + '<div class="device-detail-value' + toneClass + '">' + escapeHtml(item.value || '-') + '</div>'
            + '</div>'
            + '</div>'
            + '</div>';
    }

    function renderSimGroup(item) {
        var fieldsHtml = (item.fields || []).map(function (field) {
            return '<div class="device-sim-field">'
                + '<div class="device-detail-label">' + escapeHtml(field.label) + '</div>'
                + '<div class="device-detail-value">' + escapeHtml(field.value || '-') + '</div>'
                + '</div>';
        }).join('');

        return '<div class="device-detail-item is-sim-group tone-teal">'
            + '<div class="device-detail-item-inner device-detail-item-inner-wide">'
            + '<div class="device-detail-icon" aria-hidden="true">卡</div>'
            + '<div class="device-detail-body">'
            + '<div class="device-sim-group-title">' + escapeHtml(item.label) + '</div>'
            + '<div class="device-sim-fields">' + fieldsHtml + '</div>'
            + '</div>'
            + '</div>'
            + '</div>';
    }

    function renderStatusBanner(data) {
        if (!statusBanner) return;

        var badges = data.status_badges || [];
        if (!badges.length) {
            statusBanner.innerHTML = '';
            statusBanner.classList.add('hidden');
            return;
        }

        statusBanner.classList.remove('hidden');
        statusBanner.innerHTML = badges.map(function (item) {
            return '<div class="device-status-card tone-' + escapeHtml(item.tone) + '">'
                + '<div class="device-status-card-dot" aria-hidden="true"></div>'
                + '<div class="device-status-card-label">' + escapeHtml(item.label) + '</div>'
                + '<div class="device-status-card-value">' + escapeHtml(item.text) + '</div>'
                + '</div>';
        }).join('');
    }

    function renderDetails(data) {
        var details = data.details || [];
        if (!details.length) {
            detailGrid.innerHTML = '<div class="device-detail-empty"><p class="device-detail-empty-title">暂无设备信息</p><p class="device-detail-empty-desc">请等待设备上报，或点击「更新设备信息」向设备发送查询指令。</p></div>';
            return;
        }

        detailGrid.innerHTML = details.map(function (item) {
            if (item.type === 'sim_group') {
                return renderSimGroup(item);
            }
            return renderDetailItem(item);
        }).join('');
    }

    function openRemarkModal() {
        remarkSerialEl.textContent = serial;
        remarkInput.value = (state.device && state.device.remark) || '';
        remarkModal.classList.remove('hidden');
        remarkInput.focus();
    }

    function openPhoneModal() {
        phoneSerialEl.textContent = serial;
        phoneInput.value = (state.device && state.device.contact_phone) || '';
        phoneModal.classList.remove('hidden');
        phoneInput.focus();
    }

    function openDeviceNameModal() {
        deviceNameSerialEl.textContent = serial;
        deviceNameInput.value = getDetailValue('设备名字');
        deviceNameModal.classList.remove('hidden');
        deviceNameInput.focus();
        deviceNameInput.select();
    }

    function renderDeviceMeta(data) {
        var name = '';
        (data.details || []).some(function (item) {
            if (item.label === '设备名字' && item.value && item.value !== '-') {
                name = item.value;
                return true;
            }
            return false;
        });

        var parts = [];
        if (name) parts.push('设备：' + name);
        if (data.udid) parts.push('UDID：' + data.udid);
        if (!parts.length) {
            manageMeta.classList.add('hidden');
            return;
        }
        manageMeta.textContent = parts.join(' · ');
        manageMeta.classList.remove('hidden');
    }

    function loadDevice(showRefreshMsg) {
        if (!serial) {
            showError('缺少设备序列号，请从设备列表进入管理页面。');
            return;
        }

        MDM.api('api/devices/get.php?serial_number=' + encodeURIComponent(serial))
            .then(function (res) {
                if (res.code !== 0 || !res.data) {
                    showError(res.msg || '加载设备信息失败');
                    return;
                }

                state.device = res.data;
                loadingBox.classList.add('hidden');
                errorBox.classList.add('hidden');
                mainBox.classList.remove('hidden');
                renderStatusBanner(res.data);
                renderDetails(res.data);
                renderDeviceMeta(res.data);

                if (showRefreshMsg) {
                    notify('设备信息已刷新', 'success');
                }
            })
            .catch(function () {
                showError('加载设备信息失败');
            });
    }

    function switchTab(tab, skipUrlSync) {
        tab = normalizeTab(tab);
        state.activeTab = tab;
        document.querySelectorAll('.device-manage-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
        });
        document.querySelectorAll('.device-manage-panel').forEach(function (panel) {
            panel.classList.remove('active');
        });
        var panelMap = {
            detail: 'tabDetail',
            actions: 'tabActions',
            profiles: 'tabProfiles',
            logs: 'tabLogs'
        };
        var panel = document.getElementById(panelMap[tab]);
        if (panel) panel.classList.add('active');

        if (!skipUrlSync) {
            syncTabToUrl(tab);
        }

        if (tab === 'logs') {
            loadPerLogs();
        }
        if (tab === 'profiles') {
            loadDeviceProfiles();
        }
    }

    document.querySelectorAll('.device-manage-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchTab(btn.getAttribute('data-tab'));
        });
    });

    detailGrid.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;

        var action = btn.getAttribute('data-action');
        if (action === 'remark') {
            openRemarkModal();
            return;
        }

        if (action === 'phone') {
            openPhoneModal();
            return;
        }

        if (action === 'device_name') {
            openDeviceNameModal();
        }
    });

    function sendDeviceConnect(triggerBtn) {
        if (!serial) return;

        var btn = triggerBtn || connectDeviceBtn;
        if (btn) btn.disabled = true;

        MDM.api('api/devices/connect.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            if (btn) btn.disabled = false;
            if (res.code === 0) {
                notify(res.msg || '发送设备连接成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error');
            }
        }).catch(function () {
            if (btn) btn.disabled = false;
            notify('发送失败', 'error');
        });
    }

    function sendDeviceConfigured(triggerBtn) {
        if (!serial) return;

        if (triggerBtn) triggerBtn.disabled = true;

        MDM.api('api/devices/send_configured.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            if (triggerBtn) triggerBtn.disabled = false;
            if (res.code === 0) {
                notify(res.msg || '发送配置完成成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error');
            }
        }).catch(function () {
            if (triggerBtn) triggerBtn.disabled = false;
            notify('发送失败', 'error');
        });
    }

    function openRestartModal() {
        restartSerialEl.textContent = serial;
        restartModal.classList.remove('hidden');
    }

    function sendRestartDevice() {
        if (!serial) return;

        confirmRestartBtn.disabled = true;
        MDM.api('api/devices/restart.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmRestartBtn.disabled = false;
            if (res.code === 0) {
                restartModal.classList.add('hidden');
                notify(res.msg || '发送重启设备成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(restartModal); });
            }
        }).catch(function () {
            confirmRestartBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(restartModal); });
        });
    }

    function openUpdateLocationModal() {
        updateLocationSerialEl.textContent = serial;
        updateLocationModal.classList.remove('hidden');
    }

    function sendUpdateLocation() {
        if (!serial) return;

        confirmUpdateLocationBtn.disabled = true;
        MDM.api('api/devices/update_location.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmUpdateLocationBtn.disabled = false;
            if (res.code === 0) {
                updateLocationModal.classList.add('hidden');
                notify(res.msg || '发送获取位置成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(updateLocationModal); });
            }
        }).catch(function () {
            confirmUpdateLocationBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(updateLocationModal); });
        });
    }

    function openDisableLostModeModal() {
        disableLostModeSerialEl.textContent = serial;
        disableLostModeModal.classList.remove('hidden');
    }

    function sendDisableLostMode() {
        if (!serial) return;

        if (!window.confirm('再次确认：确定要解除该设备的丢失锁机吗？解除后设备将退出丢失模式。')) {
            return;
        }

        confirmDisableLostModeBtn.disabled = true;
        MDM.api('api/devices/disable_lost_mode.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmDisableLostModeBtn.disabled = false;
            if (res.code === 0) {
                disableLostModeModal.classList.add('hidden');
                notify(res.msg || '发送解除丢失锁机成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(disableLostModeModal); });
            }
        }).catch(function () {
            confirmDisableLostModeBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(disableLostModeModal); });
        });
    }

    function openPlayLostModeSoundModal() {
        playLostModeSoundSerialEl.textContent = serial;
        playLostModeSoundModal.classList.remove('hidden');
    }

    function sendPlayLostModeSound() {
        if (!serial) return;

        confirmPlayLostModeSoundBtn.disabled = true;
        MDM.api('api/devices/play_lost_mode_sound.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmPlayLostModeSoundBtn.disabled = false;
            if (res.code === 0) {
                playLostModeSoundModal.classList.add('hidden');
                notify(res.msg || '发送播放声音成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(playLostModeSoundModal); });
            }
        }).catch(function () {
            confirmPlayLostModeSoundBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(playLostModeSoundModal); });
        });
    }

    function openClearPasscodeModal() {
        if (!state.device || !state.device.token_synced) {
            notify('设备 Token 未同步，无法清除密码和面容', 'error');
            return;
        }

        clearPasscodeSerialEl.textContent = serial;
        clearPasscodeTokenStatusEl.textContent = '已同步';
        clearPasscodeModal.classList.remove('hidden');
    }

    function sendClearPasscode() {
        if (!serial) return;

        if (!window.confirm('再次确认：确定要清除该设备的密码和面容吗？')) {
            return;
        }

        confirmClearPasscodeBtn.disabled = true;
        MDM.api('api/devices/clear_passcode.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmClearPasscodeBtn.disabled = false;
            if (res.code === 0) {
                clearPasscodeModal.classList.add('hidden');
                notify(res.msg || '发送清除密码和面容成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(clearPasscodeModal); });
            }
        }).catch(function () {
            confirmClearPasscodeBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(clearPasscodeModal); });
        });
    }

    function openEraseDeviceModal() {
        eraseDeviceSerialEl.textContent = serial;
        eraseDeviceModal.classList.remove('hidden');
    }

    function sendEraseDevice() {
        if (!serial) return;

        if (!window.confirm('再次确认：确定要抹除还原该设备吗？此操作不可撤销，设备将恢复出厂设置并需要重新注册。')) {
            return;
        }

        confirmEraseDeviceBtn.disabled = true;
        MDM.api('api/devices/erase_device.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmEraseDeviceBtn.disabled = false;
            if (res.code === 0) {
                eraseDeviceModal.classList.add('hidden');
                notify(res.msg || '发送抹除还原成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(eraseDeviceModal); });
            }
        }).catch(function () {
            confirmEraseDeviceBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(eraseDeviceModal); });
        });
    }

    function openEnableActivationLockModal() {
        if (!state.device) return;

        if (!state.device.dep_configured) {
            notify('请先完成 DEP 配置后再开启激活锁', 'error');
            return;
        }

        enableActivationLockSerialEl.textContent = serial;
        enableActivationLockMessageInput.value = '';
        enableActivationLockModal.classList.remove('hidden');
        enableActivationLockMessageInput.focus();
    }

    function sendEnableActivationLock() {
        if (!serial) return;

        var lostMessage = enableActivationLockMessageInput.value.trim();
        if (!lostMessage) {
            notify('请填写丢失提示内容', 'error');
            enableActivationLockMessageInput.focus();
            return;
        }

        if (!window.confirm('再次确认：确定要开启该设备的激活锁吗？')) {
            return;
        }

        confirmEnableActivationLockBtn.disabled = true;
        MDM.api('api/devices/enable_activation_lock.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                lost_message: lostMessage
            }
        }).then(function (res) {
            confirmEnableActivationLockBtn.disabled = false;
            if (res.code === 0) {
                enableActivationLockModal.classList.add('hidden');
                notify(res.msg || '开启激活锁成功', 'success');
                state.logsLoaded = false;
                loadDevice();
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '开启失败', 'error', function () { closeModal(enableActivationLockModal); });
            }
        }).catch(function () {
            confirmEnableActivationLockBtn.disabled = false;
            notify('开启失败', 'error', function () { closeModal(enableActivationLockModal); });
        });
    }

    function openDisableActivationLockModal() {
        if (!state.device) return;

        disableActivationLockSerialEl.textContent = serial;
        disableActivationLockTopicEl.textContent = state.device.topic || '-';
        disableActivationLockBypassStatusEl.textContent = state.device.has_bypass_code ? '已保存' : '未保存';
        disableActivationLockOrgNameInput.value = state.device.org_name_default || '';
        disableActivationLockGuidInput.value = '';
        disableActivationLockModal.classList.remove('hidden');
        disableActivationLockOrgNameInput.focus();
    }

    function sendDisableActivationLock() {
        if (!serial) return;

        var orgName = disableActivationLockOrgNameInput.value.trim();
        var guid = disableActivationLockGuidInput.value.trim();

        if (!orgName) {
            notify('请输入组织名称', 'error');
            disableActivationLockOrgNameInput.focus();
            return;
        }

        if (!guid) {
            notify('请输入姓名', 'error');
            disableActivationLockGuidInput.focus();
            return;
        }

        if (!window.confirm('再次确认：确定要关闭该设备的激活锁吗？')) {
            return;
        }

        confirmDisableActivationLockBtn.disabled = true;
        MDM.api('api/devices/disable_activation_lock.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                org_name: orgName,
                guid: guid
            }
        }).then(function (res) {
            confirmDisableActivationLockBtn.disabled = false;
            if (res.code === 0) {
                disableActivationLockModal.classList.add('hidden');
                notify(res.msg || '关闭激活锁成功', 'success');
                state.logsLoaded = false;
                loadDevice();
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '关闭失败', 'error', function () { closeModal(disableActivationLockModal); });
            }
        }).catch(function () {
            confirmDisableActivationLockBtn.disabled = false;
            notify('关闭失败', 'error', function () { closeModal(disableActivationLockModal); });
        });
    }

    function openShutdownModal() {
        shutdownSerialEl.textContent = serial;
        shutdownModal.classList.remove('hidden');
    }

    function resetWallpaperModal() {
        state.wallpaperBase64 = '';
        if (wallpaperFileInput) wallpaperFileInput.value = '';
        if (wallpaperPreviewWrap) wallpaperPreviewWrap.classList.add('hidden');
        if (wallpaperPreviewImg) wallpaperPreviewImg.removeAttribute('src');
        if (wallpaperFileName) wallpaperFileName.textContent = '';
        if (confirmWallpaperBtn) confirmWallpaperBtn.disabled = true;
    }

    function openWallpaperModal() {
        wallpaperSerialEl.textContent = serial;
        resetWallpaperModal();
        wallpaperModal.classList.remove('hidden');
    }

    function openDeviceLockModal() {
        deviceLockSerialEl.textContent = serial;
        deviceLockMessageInput.value = '';
        deviceLockPhoneInput.value = (state.device && state.device.contact_phone) || '';
        deviceLockModal.classList.remove('hidden');
        deviceLockMessageInput.focus();
    }

    function sendDeviceLock() {
        if (!serial) return;

        var message = deviceLockMessageInput.value.trim();
        var phoneNumber = deviceLockPhoneInput.value.trim();

        if (!message) {
            notify('请输入锁定时显示的信息内容', 'error');
            deviceLockMessageInput.focus();
            return;
        }

        if (!phoneNumber) {
            notify('请输入联系号码', 'error');
            deviceLockPhoneInput.focus();
            return;
        }

        if (!window.confirm('确定要向该设备发送锁定指令吗？')) {
            return;
        }

        confirmDeviceLockBtn.disabled = true;
        MDM.api('api/devices/lock_device.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                message: message,
                phone_number: phoneNumber
            }
        }).then(function (res) {
            confirmDeviceLockBtn.disabled = false;
            if (res.code === 0) {
                deviceLockModal.classList.add('hidden');
                notify(res.msg || '发送锁定设备成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(deviceLockModal); });
            }
        }).catch(function () {
            confirmDeviceLockBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(deviceLockModal); });
        });
    }

    function openEnableLostModeModal() {
        enableLostModeSerialEl.textContent = serial;
        enableLostModeFootnoteInput.value = '';
        enableLostModeMessageInput.value = '';
        enableLostModePhoneInput.value = (state.device && state.device.contact_phone) || '';
        enableLostModeModal.classList.remove('hidden');
        enableLostModeFootnoteInput.focus();
    }

    function sendEnableLostMode() {
        if (!serial) return;

        var footnote = enableLostModeFootnoteInput.value.trim();
        var message = enableLostModeMessageInput.value.trim();
        var phoneNumber = enableLostModePhoneInput.value.trim();

        if (!footnote) {
            notify('请输入底部显示信息', 'error');
            enableLostModeFootnoteInput.focus();
            return;
        }

        if (!message) {
            notify('请输入提示显示的信息', 'error');
            enableLostModeMessageInput.focus();
            return;
        }

        if (!phoneNumber) {
            notify('请输入联系号码', 'error');
            enableLostModePhoneInput.focus();
            return;
        }

        if (!window.confirm('确定要向该设备发送丢失锁机指令吗？开启后需使用「解除丢失锁机」才能解除。')) {
            return;
        }

        confirmEnableLostModeBtn.disabled = true;
        MDM.api('api/devices/enable_lost_mode.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                footnote: footnote,
                message: message,
                phone_number: phoneNumber
            }
        }).then(function (res) {
            confirmEnableLostModeBtn.disabled = false;
            if (res.code === 0) {
                enableLostModeModal.classList.add('hidden');
                notify(res.msg || '发送丢失锁机成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(enableLostModeModal); });
            }
        }).catch(function () {
            confirmEnableLostModeBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(enableLostModeModal); });
        });
    }

    function handleWallpaperFileSelect(file) {
        if (!file) {
            resetWallpaperModal();
            return;
        }

        if (!file.type || file.type.indexOf('image/') !== 0) {
            notify('请选择图片文件', 'error');
            resetWallpaperModal();
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            notify('壁纸图片不能超过 10MB', 'error');
            resetWallpaperModal();
            return;
        }

        var reader = new FileReader();
        reader.onload = function () {
            var result = reader.result || '';
            var commaIndex = String(result).indexOf(',');
            if (commaIndex === -1) {
                notify('图片读取失败', 'error');
                resetWallpaperModal();
                return;
            }

            state.wallpaperBase64 = String(result).slice(commaIndex + 1);
            if (wallpaperPreviewImg) wallpaperPreviewImg.src = result;
            if (wallpaperFileName) {
                wallpaperFileName.textContent = file.name + '（' + Math.max(1, Math.round(file.size / 1024)) + ' KB）';
            }
            if (wallpaperPreviewWrap) wallpaperPreviewWrap.classList.remove('hidden');
            if (confirmWallpaperBtn) confirmWallpaperBtn.disabled = false;
        };
        reader.onerror = function () {
            notify('图片读取失败', 'error');
            resetWallpaperModal();
        };
        reader.readAsDataURL(file);
    }

    function sendWallpaperChange() {
        if (!serial) return;
        if (!state.wallpaperBase64) {
            notify('请先选择壁纸图片', 'error');
            return;
        }

        confirmWallpaperBtn.disabled = true;
        MDM.api('api/devices/update_wallpaper.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                image_base64: state.wallpaperBase64
            }
        }).then(function (res) {
            confirmWallpaperBtn.disabled = false;
            if (res.code === 0) {
                wallpaperModal.classList.add('hidden');
                resetWallpaperModal();
                notify(res.msg || '发送修改壁纸成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(wallpaperModal); });
            }
        }).catch(function () {
            confirmWallpaperBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(wallpaperModal); });
        });
    }

    function sendShutDownDevice() {
        if (!serial) return;

        confirmShutdownBtn.disabled = true;
        MDM.api('api/devices/shutdown.php', {
            method: 'POST',
            body: { serial_number: serial }
        }).then(function (res) {
            confirmShutdownBtn.disabled = false;
            if (res.code === 0) {
                shutdownModal.classList.add('hidden');
                notify(res.msg || '发送关闭设备成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(shutdownModal); });
            }
        }).catch(function () {
            confirmShutdownBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(shutdownModal); });
        });
    }

    var deviceActionGrid = document.getElementById('deviceActionGrid');
    if (deviceActionGrid) {
        deviceActionGrid.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;

            var action = btn.getAttribute('data-action');
            if (action === 'connect') {
                sendDeviceConnect(btn);
                return;
            }

            if (action === 'device_configured') {
                sendDeviceConfigured(btn);
                return;
            }

            if (action === 'restart_device') {
                openRestartModal();
                return;
            }

            if (action === 'shutdown_device') {
                openShutdownModal();
                return;
            }

            if (action === 'update_wallpaper') {
                openWallpaperModal();
                return;
            }

            if (action === 'device_lock') {
                openDeviceLockModal();
                return;
            }

            if (action === 'enable_lost_mode') {
                openEnableLostModeModal();
                return;
            }

            if (action === 'update_location') {
                openUpdateLocationModal();
                return;
            }

            if (action === 'disable_lost_mode') {
                openDisableLostModeModal();
                return;
            }

            if (action === 'play_lost_mode_sound') {
                openPlayLostModeSoundModal();
                return;
            }

            if (action === 'clear_passcode') {
                openClearPasscodeModal();
                return;
            }

            if (action === 'erase_device') {
                openEraseDeviceModal();
                return;
            }

            if (action === 'enable_activation_lock') {
                openEnableActivationLockModal();
                return;
            }

            if (action === 'disable_activation_lock') {
                openDisableActivationLockModal();
            }
        });
    }

    if (wallpaperFileInput) {
        wallpaperFileInput.addEventListener('change', function () {
            var file = wallpaperFileInput.files && wallpaperFileInput.files[0];
            handleWallpaperFileSelect(file);
        });
    }

    if (confirmUpdateLocationBtn) {
        confirmUpdateLocationBtn.addEventListener('click', function () {
            sendUpdateLocation();
        });
    }

    if (confirmDisableLostModeBtn) {
        confirmDisableLostModeBtn.addEventListener('click', function () {
            sendDisableLostMode();
        });
    }

    if (confirmPlayLostModeSoundBtn) {
        confirmPlayLostModeSoundBtn.addEventListener('click', function () {
            sendPlayLostModeSound();
        });
    }

    if (confirmClearPasscodeBtn) {
        confirmClearPasscodeBtn.addEventListener('click', function () {
            sendClearPasscode();
        });
    }

    if (confirmEraseDeviceBtn) {
        confirmEraseDeviceBtn.addEventListener('click', function () {
            sendEraseDevice();
        });
    }

    if (confirmEnableActivationLockBtn) {
        confirmEnableActivationLockBtn.addEventListener('click', function () {
            sendEnableActivationLock();
        });
    }

    if (confirmDisableActivationLockBtn) {
        confirmDisableActivationLockBtn.addEventListener('click', function () {
            sendDisableActivationLock();
        });
    }

    if (confirmWallpaperBtn) {
        confirmWallpaperBtn.addEventListener('click', function () {
            sendWallpaperChange();
        });
    }

    if (confirmDeviceLockBtn) {
        confirmDeviceLockBtn.addEventListener('click', function () {
            sendDeviceLock();
        });
    }

    if (confirmEnableLostModeBtn) {
        confirmEnableLostModeBtn.addEventListener('click', function () {
            sendEnableLostMode();
        });
    }

    if (confirmUpdateLocationBtn) {
        confirmUpdateLocationBtn.addEventListener('click', function () {
            sendUpdateLocation();
        });
    }

    if (confirmRestartBtn) {
        confirmRestartBtn.addEventListener('click', function () {
            sendRestartDevice();
        });
    }

    if (confirmShutdownBtn) {
        confirmShutdownBtn.addEventListener('click', function () {
            sendShutDownDevice();
        });
    }

    if (updateDeviceInfoBtn) {
        updateDeviceInfoBtn.addEventListener('click', function () {
            if (!serial) return;

            updateDeviceInfoBtn.disabled = true;
            MDM.api('api/devices/update_info.php', {
                method: 'POST',
                body: { serial_number: serial }
            }).then(function (res) {
                updateDeviceInfoBtn.disabled = false;
                if (res.code === 0) {
                    notify(res.msg || '发送更新设备信息成功,等待指令执行完成后刷新数据即可', 'success');
                    state.logsLoaded = false;
                    if (state.activeTab === 'logs') {
                        loadPerLogs();
                    }
                } else {
                    notify(res.msg || '发送失败', 'error');
                }
            }).catch(function () {
                updateDeviceInfoBtn.disabled = false;
                notify('发送失败', 'error');
            });
        });
    }

    if (updateProfileListBtn) {
        updateProfileListBtn.addEventListener('click', function () {
            if (!serial) return;

            updateProfileListBtn.disabled = true;
            MDM.api('api/devices/update_profile_list.php', {
                method: 'POST',
                body: { serial_number: serial }
            }).then(function (res) {
                updateProfileListBtn.disabled = false;
                if (res.code === 0) {
                    notify(res.msg || '更新配置文件列表成功，请刷新设备日志，等待指令执行成功后再刷新配置文件列表', 'success');
                    state.logsLoaded = false;
                    if (state.activeTab === 'logs') {
                        loadPerLogs();
                    }
                } else {
                    notify(res.msg || '发送失败', 'error');
                }
            }).catch(function () {
                updateProfileListBtn.disabled = false;
                notify('发送失败', 'error');
            });
        });
    }

    function renderDeviceProfiles(list) {
        state.profileList = list || [];
        if (!deviceProfileListBody) return;

        if (!list || !list.length) {
            deviceProfileListBody.innerHTML = '<tr><td colspan="4" class="empty-cell device-profile-empty">'
                + '<div class="device-profile-empty-inner">'
                + '<p class="device-profile-empty-title">暂无配置文件</p>'
                + '<p class="device-profile-empty-hint">请先点击「更新配置文件列表」向设备发送同步指令，待设备响应后再点击「刷新配置文件列表」获取最新列表。</p>'
                + '</div></td></tr>';
            if (deviceProfileListMeta) {
                deviceProfileListMeta.textContent = '共 0 个配置文件';
            }
            return;
        }

        if (deviceProfileListMeta) {
            var updatedAt = list[0].updated_at || '';
            deviceProfileListMeta.textContent = '共 ' + list.length + ' 个配置文件' + (updatedAt ? ('，最近同步：' + updatedAt) : '');
        }

        deviceProfileListBody.innerHTML = list.map(function (item) {
            var name = item.payload_display_name || '-';
            var identifier = item.payload_identifier || '-';
            var types = item.payload_types_text || '-';
            var typesHtml = types === '-'
                ? '-'
                : types.split(',').map(function (part) {
                    var text = part.trim();
                    return text ? '<span class="device-profile-type-tag">' + escapeHtml(text) + '</span>' : '';
                }).join('');
            return '<tr>'
                + '<td class="col-name" title="' + escapeHtml(name) + '">' + escapeHtml(name) + '</td>'
                + '<td class="col-mono" title="' + escapeHtml(identifier) + '">' + escapeHtml(identifier) + '</td>'
                + '<td class="col-types" title="' + escapeHtml(types) + '">' + typesHtml + '</td>'
                + '<td class="col-action"><button type="button" class="btn btn-text btn-sm btn-remove-device-profile" data-identifier="' + escapeHtml(identifier) + '" data-name="' + escapeHtml(name) + '">移除</button></td>'
                + '</tr>';
        }).join('');
    }

    function loadDeviceProfiles(showRefreshMsg) {
        if (!serial) return;

        if (deviceProfileListBody) {
            deviceProfileListBody.innerHTML = '<tr><td colspan="4" class="empty-cell">加载中...</td></tr>';
        }

        MDM.api('api/devices/profiles.php?serial_number=' + encodeURIComponent(serial))
            .then(function (res) {
                if (res.code !== 0) {
                    notify(res.msg || '加载配置文件列表失败', 'error');
                    return;
                }
                renderDeviceProfiles(res.data.list || []);
                if (showRefreshMsg) {
                    notify('配置文件列表已刷新', 'success');
                }
            })
            .catch(function () {
                notify('加载配置文件列表失败', 'error');
            });
    }

    function loadProfileInstallDefaults(callback) {
        MDM.api('api/devices/profile_install_defaults.php?serial_number=' + encodeURIComponent(serial))
            .then(function (res) {
                if (res.code !== 0 || !res.data) {
                    notify(res.msg || '加载配置默认值失败', 'error');
                    return;
                }
                state.profileDefaults = res.data;
                if (typeof callback === 'function') {
                    callback(res.data);
                }
            })
            .catch(function () {
                notify('加载配置默认值失败', 'error');
            });
    }

    function removeDeviceProfile(identifier, name, source) {
        if (!serial || !identifier) return;

        var label = name ? (name + ' (' + identifier + ')') : identifier;
        if (!window.confirm('确定要移除配置文件「' + label + '」吗？')) {
            return;
        }
        if (!window.confirm('再次确认：确定要向设备发送移除配置文件指令吗？')) {
            return;
        }

        MDM.api('api/devices/remove_profile.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                identifier: identifier,
                source: source || 'list'
            }
        }).then(function (res) {
            if (res.code === 0) {
                notify(res.msg || '发送移除配置文件成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error');
            }
        }).catch(function () {
            notify('发送失败', 'error');
        });
    }

    function renderDeviceFuncSwitches(defaults) {
        var grid = document.getElementById('deviceFuncSwitchGrid');
        if (!grid || !defaults) return;

        var keys = defaults.func_keys || [];
        var restrictions = (defaults.func && defaults.func.func_restrictions) || {};

        grid.innerHTML = keys.map(function (item) {
            var checked = restrictions[item.key] ? ' checked' : '';
            var cls = item.security ? 'func-switch-item func-security' : 'func-switch-item';
            return '<div class="' + cls + '">'
                + '<span>' + escapeHtml(item.label) + '</span>'
                + '<label class="switch switch-sm">'
                + '<input type="checkbox" class="device-func-key func-key" data-key="' + escapeHtml(item.key) + '"' + checked + '>'
                + '<span class="switch-slider"></span>'
                + '</label>'
                + '</div>';
        }).join('');

        var cameraWhitelist = document.getElementById('deviceFuncCameraWhitelistInput');
        if (cameraWhitelist) {
            cameraWhitelist.value = restrictions.allowedCameraRestrictionBundleIDs || '';
        }
    }

    function collectDeviceFuncRestrictions() {
        var restrictions = {};
        document.querySelectorAll('.device-func-key').forEach(function (input) {
            var key = input.getAttribute('data-key');
            if (key) {
                restrictions[key] = !!input.checked;
            }
        });
        var cameraWhitelist = document.getElementById('deviceFuncCameraWhitelistInput');
        var whitelistText = cameraWhitelist ? cameraWhitelist.value.trim() : '';
        restrictions.camera_whitelist_enabled = whitelistText !== '';
        restrictions.allowedCameraRestrictionBundleIDs = whitelistText;
        return restrictions;
    }

    if (refreshProfileListBtn) {
        refreshProfileListBtn.addEventListener('click', function () {
            loadDeviceProfiles(true);
        });
    }

    if (deviceProfileListBody) {
        deviceProfileListBody.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-remove-device-profile');
            if (!btn) return;
            removeDeviceProfile(btn.getAttribute('data-identifier'), btn.getAttribute('data-name'), 'list');
        });
    }

    var installProfileFileBtn = document.getElementById('installProfileFileBtn');
    var installProfileFileModal = document.getElementById('installProfileFileModal');
    var installProfileFileInput = document.getElementById('installProfileFileInput');
    var installProfileFileNameEl = document.getElementById('installProfileFileName');
    var confirmInstallProfileFileBtn = document.getElementById('confirmInstallProfileFileBtn');

    bindModalClose(installProfileFileModal, ['closeInstallProfileFileModal', 'installProfileFileModalBackdrop', 'cancelInstallProfileFileBtn']);

    if (installProfileFileBtn) {
        installProfileFileBtn.addEventListener('click', function () {
            var serialEl = document.getElementById('installProfileFileSerial');
            if (serialEl) serialEl.textContent = serial;
            state.installProfileBase64 = '';
            state.installProfileFilename = '';
            if (installProfileFileInput) installProfileFileInput.value = '';
            if (installProfileFileNameEl) installProfileFileNameEl.textContent = '未选择文件';
            installProfileFileModal.classList.remove('hidden');
        });
    }

    if (installProfileFileInput) {
        installProfileFileInput.addEventListener('change', function () {
            var file = installProfileFileInput.files && installProfileFileInput.files[0];
            if (!file) {
                state.installProfileBase64 = '';
                state.installProfileFilename = '';
                if (installProfileFileNameEl) installProfileFileNameEl.textContent = '未选择文件';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                notify('配置文件不能超过 2MB', 'error');
                installProfileFileInput.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                var buffer = reader.result;
                var bytes = new Uint8Array(buffer);
                var binary = '';
                for (var i = 0; i < bytes.length; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                state.installProfileBase64 = btoa(binary);
                state.installProfileFilename = file.name || '';
                if (installProfileFileNameEl) installProfileFileNameEl.textContent = file.name;
            };
            reader.onerror = function () {
                notify('文件读取失败', 'error');
            };
            reader.readAsArrayBuffer(file);
        });
    }

    if (confirmInstallProfileFileBtn) {
        confirmInstallProfileFileBtn.addEventListener('click', function () {
            if (!state.installProfileBase64) {
                notify('请选择配置文件', 'error');
                return;
            }
            if (!window.confirm('确定要向该设备安装所选配置文件吗？')) {
                return;
            }
            confirmInstallProfileFileBtn.disabled = true;
            MDM.api('api/devices/install_profile.php', {
                method: 'POST',
                body: {
                    serial_number: serial,
                    profile_base64: state.installProfileBase64,
                    profile_filename: state.installProfileFilename
                }
            }).then(function (res) {
                confirmInstallProfileFileBtn.disabled = false;
                if (res.code === 0) {
                    closeModal(installProfileFileModal);
                    notify(res.msg || '发送安装配置描述文件成功', 'success');
                    state.logsLoaded = false;
                    if (state.activeTab === 'logs') loadPerLogs();
                } else {
                    notify(res.msg || '发送失败', 'error', function () { closeModal(installProfileFileModal); });
                }
            }).catch(function () {
                confirmInstallProfileFileBtn.disabled = false;
                notify('发送失败', 'error', function () { closeModal(installProfileFileModal); });
            });
        });
    }

    var removeProfileByIdBtn = document.getElementById('removeProfileByIdBtn');
    var removeProfileByIdModal = document.getElementById('removeProfileByIdModal');
    var removeProfileByIdInput = document.getElementById('removeProfileByIdInput');
    var confirmRemoveProfileByIdBtn = document.getElementById('confirmRemoveProfileByIdBtn');

    bindModalClose(removeProfileByIdModal, ['closeRemoveProfileByIdModal', 'removeProfileByIdModalBackdrop', 'cancelRemoveProfileByIdBtn']);

    if (removeProfileByIdBtn) {
        removeProfileByIdBtn.addEventListener('click', function () {
            var serialEl = document.getElementById('removeProfileByIdSerial');
            if (serialEl) serialEl.textContent = serial;
            if (removeProfileByIdInput) removeProfileByIdInput.value = '';
            removeProfileByIdModal.classList.remove('hidden');
            if (removeProfileByIdInput) removeProfileByIdInput.focus();
        });
    }

    if (confirmRemoveProfileByIdBtn) {
        confirmRemoveProfileByIdBtn.addEventListener('click', function () {
            var identifier = removeProfileByIdInput ? removeProfileByIdInput.value.trim() : '';
            if (!identifier) {
                notify('请输入配置文件标识 ID', 'error');
                return;
            }
            closeModal(removeProfileByIdModal);
            removeDeviceProfile(identifier, '', 'manual');
        });
    }

    var deviceDnsProfileModal = document.getElementById('deviceDnsProfileModal');
    var deviceGlobalProfileModal = document.getElementById('deviceGlobalProfileModal');
    var deviceFuncProfileModal = document.getElementById('deviceFuncProfileModal');

    bindModalClose(deviceDnsProfileModal, ['closeDeviceDnsProfileModal', 'deviceDnsProfileModalBackdrop', 'cancelDeviceDnsProfileBtn']);
    bindModalClose(deviceGlobalProfileModal, ['closeDeviceGlobalProfileModal', 'deviceGlobalProfileModalBackdrop', 'cancelDeviceGlobalProfileBtn']);
    bindModalClose(deviceFuncProfileModal, ['closeDeviceFuncProfileModal', 'deviceFuncProfileModalBackdrop', 'cancelDeviceFuncProfileBtn']);

    function fillDnsProfileForm(data) {
        var dns = (data && data.dns) || {};
        document.getElementById('deviceDnsOrgNameInput').value = dns.dns_org_name || '';
        document.getElementById('deviceDnsIdentifierInput').value = dns.dns_identifier || '';
        document.getElementById('deviceDnsServerUrlInput').value = dns.dns_server_url || '';
        document.getElementById('deviceDnsAddress1Input').value = dns.dns_address_1 || '';
        document.getElementById('deviceDnsAddress2Input').value = dns.dns_address_2 || '';
    }

    function fillGlobalProfileForm(data) {
        var global = (data && data.global) || {};
        document.getElementById('deviceGlobalOrgNameInput').value = global.proxy_org_name || '';
        document.getElementById('deviceGlobalIdentifierInput').value = global.proxy_identifier || '';
        document.getElementById('deviceGlobalPacUrlInput').value = global.proxy_pac_url || '';
    }

    function fillFuncProfileForm(data) {
        var func = (data && data.func) || {};
        document.getElementById('deviceFuncOrgNameInput').value = func.func_org_name || '';
        document.getElementById('deviceFuncIdentifierInput').value = func.func_identifier || '';
        renderDeviceFuncSwitches(data);
    }

    var deviceDnsProfileBtn = document.getElementById('deviceDnsProfileBtn');
    var deviceGlobalProfileBtn = document.getElementById('deviceGlobalProfileBtn');
    var deviceFuncProfileBtn = document.getElementById('deviceFuncProfileBtn');

    if (deviceDnsProfileBtn) {
        deviceDnsProfileBtn.addEventListener('click', function () {
            loadProfileInstallDefaults(function (data) {
                fillDnsProfileForm(data);
                deviceDnsProfileModal.classList.remove('hidden');
            });
        });
    }

    if (deviceGlobalProfileBtn) {
        deviceGlobalProfileBtn.addEventListener('click', function () {
            loadProfileInstallDefaults(function (data) {
                fillGlobalProfileForm(data);
                deviceGlobalProfileModal.classList.remove('hidden');
            });
        });
    }

    if (deviceFuncProfileBtn) {
        deviceFuncProfileBtn.addEventListener('click', function () {
            loadProfileInstallDefaults(function (data) {
                fillFuncProfileForm(data);
                deviceFuncProfileModal.classList.remove('hidden');
            });
        });
    }

    var confirmDeviceDnsProfileBtn = document.getElementById('confirmDeviceDnsProfileBtn');
    if (confirmDeviceDnsProfileBtn) {
        confirmDeviceDnsProfileBtn.addEventListener('click', function () {
            var body = {
                serial_number: serial,
                type: 'dns',
                dns_org_name: document.getElementById('deviceDnsOrgNameInput').value.trim(),
                dns_identifier: document.getElementById('deviceDnsIdentifierInput').value.trim(),
                dns_server_url: document.getElementById('deviceDnsServerUrlInput').value.trim(),
                dns_address_1: document.getElementById('deviceDnsAddress1Input').value.trim(),
                dns_address_2: document.getElementById('deviceDnsAddress2Input').value.trim()
            };
            if (!window.confirm('确定要覆盖安装 DNS 代理配置吗？')) return;
            confirmDeviceDnsProfileBtn.disabled = true;
            MDM.api('api/devices/install_managed_profile.php', { method: 'POST', body: body }).then(function (res) {
                confirmDeviceDnsProfileBtn.disabled = false;
                if (res.code === 0) {
                    closeModal(deviceDnsProfileModal);
                    notify(res.msg || '发送安装 DNS 代理成功', 'success');
                    state.logsLoaded = false;
                    if (state.activeTab === 'logs') loadPerLogs();
                } else {
                    notify(res.msg || '发送失败', 'error', function () { closeModal(deviceDnsProfileModal); });
                }
            }).catch(function () {
                confirmDeviceDnsProfileBtn.disabled = false;
                notify('发送失败', 'error', function () { closeModal(deviceDnsProfileModal); });
            });
        });
    }

    var confirmDeviceGlobalProfileBtn = document.getElementById('confirmDeviceGlobalProfileBtn');
    if (confirmDeviceGlobalProfileBtn) {
        confirmDeviceGlobalProfileBtn.addEventListener('click', function () {
            var body = {
                serial_number: serial,
                type: 'global',
                proxy_org_name: document.getElementById('deviceGlobalOrgNameInput').value.trim(),
                proxy_identifier: document.getElementById('deviceGlobalIdentifierInput').value.trim(),
                proxy_pac_url: document.getElementById('deviceGlobalPacUrlInput').value.trim()
            };
            if (!window.confirm('确定要覆盖安装全局代理配置吗？')) return;
            confirmDeviceGlobalProfileBtn.disabled = true;
            MDM.api('api/devices/install_managed_profile.php', { method: 'POST', body: body }).then(function (res) {
                confirmDeviceGlobalProfileBtn.disabled = false;
                if (res.code === 0) {
                    closeModal(deviceGlobalProfileModal);
                    notify(res.msg || '发送安装全局代理成功', 'success');
                    state.logsLoaded = false;
                    if (state.activeTab === 'logs') loadPerLogs();
                } else {
                    notify(res.msg || '发送失败', 'error', function () { closeModal(deviceGlobalProfileModal); });
                }
            }).catch(function () {
                confirmDeviceGlobalProfileBtn.disabled = false;
                notify('发送失败', 'error', function () { closeModal(deviceGlobalProfileModal); });
            });
        });
    }

    var confirmDeviceFuncProfileBtn = document.getElementById('confirmDeviceFuncProfileBtn');
    if (confirmDeviceFuncProfileBtn) {
        confirmDeviceFuncProfileBtn.addEventListener('click', function () {
            var body = {
                serial_number: serial,
                type: 'func',
                func_org_name: document.getElementById('deviceFuncOrgNameInput').value.trim(),
                func_identifier: document.getElementById('deviceFuncIdentifierInput').value.trim(),
                func_restrictions: collectDeviceFuncRestrictions()
            };
            if (!window.confirm('确定要覆盖安装功能限制配置吗？')) return;
            confirmDeviceFuncProfileBtn.disabled = true;
            MDM.api('api/devices/install_managed_profile.php', { method: 'POST', body: body }).then(function (res) {
                confirmDeviceFuncProfileBtn.disabled = false;
                if (res.code === 0) {
                    closeModal(deviceFuncProfileModal);
                    notify(res.msg || '发送安装功能限制成功', 'success');
                    state.logsLoaded = false;
                    if (state.activeTab === 'logs') loadPerLogs();
                } else {
                    notify(res.msg || '发送失败', 'error', function () { closeModal(deviceFuncProfileModal); });
                }
            }).catch(function () {
                confirmDeviceFuncProfileBtn.disabled = false;
                notify('发送失败', 'error', function () { closeModal(deviceFuncProfileModal); });
            });
        });
    }

    if (refreshDeviceInfoBtn) {
        refreshDeviceInfoBtn.addEventListener('click', function () {
            if (!serial) return;
            refreshDeviceInfoBtn.disabled = true;
            MDM.api('api/devices/get.php?serial_number=' + encodeURIComponent(serial))
                .then(function (res) {
                    refreshDeviceInfoBtn.disabled = false;
                    if (res.code !== 0 || !res.data) {
                        notify(res.msg || '刷新设备信息失败', 'error');
                        return;
                    }
                    state.device = res.data;
                    renderStatusBanner(res.data);
                    renderDetails(res.data);
                    renderDeviceMeta(res.data);
                    notify('设备信息已刷新', 'success');
                })
                .catch(function () {
                    refreshDeviceInfoBtn.disabled = false;
                    notify('刷新设备信息失败', 'error');
                });
        });
    }

    function resolvePerLogRowClass(status) {
        var val = String(status || '');
        if (val.indexOf('完成') !== -1) return 'log-row-success';
        if (val.indexOf('等待') !== -1) return 'log-row-wait';
        if (val.indexOf('失败') !== -1) return 'log-row-fail';
        return '';
    }

    confirmRemarkBtn.addEventListener('click', function () {
        confirmRemarkBtn.disabled = true;
        MDM.api('api/devices/update_remark.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                remark: remarkInput.value
            }
        }).then(function (res) {
            confirmRemarkBtn.disabled = false;
            if (res.code === 0) {
                remarkModal.classList.add('hidden');
                notify(res.msg || '备注已保存', 'success');
                loadDevice();
            } else {
                notify(res.msg || '保存失败', 'error', function () { closeModal(remarkModal); });
            }
        }).catch(function () {
            confirmRemarkBtn.disabled = false;
            notify('保存失败', 'error', function () { closeModal(remarkModal); });
        });
    });

    confirmPhoneBtn.addEventListener('click', function () {
        confirmPhoneBtn.disabled = true;
        MDM.api('api/devices/update_phone.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                contact_phone: phoneInput.value
            }
        }).then(function (res) {
            confirmPhoneBtn.disabled = false;
            if (res.code === 0) {
                phoneModal.classList.add('hidden');
                notify(res.msg || '联系号码已保存', 'success');
                loadDevice();
            } else {
                notify(res.msg || '保存失败', 'error', function () { closeModal(phoneModal); });
            }
        }).catch(function () {
            confirmPhoneBtn.disabled = false;
            notify('保存失败', 'error', function () { closeModal(phoneModal); });
        });
    });

    confirmDeviceNameBtn.addEventListener('click', function () {
        var newName = deviceNameInput.value.trim();
        if (!newName) {
            notify('请输入新的设备名称', 'error');
            deviceNameInput.focus();
            return;
        }

        confirmDeviceNameBtn.disabled = true;
        MDM.api('api/devices/update_device_name.php', {
            method: 'POST',
            body: {
                serial_number: serial,
                device_name: newName
            }
        }).then(function (res) {
            confirmDeviceNameBtn.disabled = false;
            if (res.code === 0) {
                deviceNameModal.classList.add('hidden');
                notify(res.msg || '发送修改设备名称成功', 'success');
                state.logsLoaded = false;
                if (state.activeTab === 'logs') {
                    loadPerLogs();
                }
            } else {
                notify(res.msg || '发送失败', 'error', function () { closeModal(deviceNameModal); });
            }
        }).catch(function () {
            confirmDeviceNameBtn.disabled = false;
            notify('发送失败', 'error', function () { closeModal(deviceNameModal); });
        });
    });

    function getLogFilters() {
        return {
            serial_number: serial,
            date_from: perLogDateFrom ? perLogDateFrom.value : '',
            date_to: perLogDateTo ? perLogDateTo.value : '',
            search_scope: 'all',
            search_keyword: perLogKeyword ? perLogKeyword.value.trim() : '',
            page: state.logPage
        };
    }

    function renderPerLogs(list) {
        if (!list || !list.length) {
            perLogBody.innerHTML = '<tr><td colspan="7" class="empty-cell">暂无日志</td></tr>';
            return;
        }

        perLogBody.innerHTML = list.map(function (row) {
            var rowClass = resolvePerLogRowClass(row.command_status);
            return '<tr class="' + rowClass + '">'
                + '<td>' + escapeHtml(row.operation_type || '-') + '</td>'
                + '<td class="col-mono" title="' + escapeHtml(row.comm_id || '') + '">' + escapeHtml(row.comm_id || '-') + '</td>'
                + '<td class="col-mono" title="' + escapeHtml(row.push_id || '') + '">' + escapeHtml(row.push_id || '-') + '</td>'
                + '<td>' + escapeHtml(row.command_type || '-') + '</td>'
                + '<td>' + escapeHtml(row.command_status || '-') + '</td>'
                + '<td>' + escapeHtml(row.created_at || '-') + '</td>'
                + '<td>' + escapeHtml(row.confirmed_at || '-') + '</td>'
                + '</tr>';
        }).join('');
    }

    function loadPerLogs(showRefreshMsg) {
        if (!serial) return;

        perLogBody.innerHTML = '<tr><td colspan="7" class="empty-cell">加载中...</td></tr>';
        MDM.api('api/devices/per_logs/list.php?' + new URLSearchParams(getLogFilters()).toString())
            .then(function (res) {
                if (res.code !== 0 || !res.data) {
                    notify(res.msg || '加载日志失败', 'error');
                    perLogBody.innerHTML = '<tr><td colspan="7" class="empty-cell">加载失败</td></tr>';
                    return;
                }

                renderPerLogs(res.data.list || []);
                state.logPage = res.data.page || 1;
                state.logTotalPages = res.data.total_pages || 1;
                perLogMeta.textContent = '共 ' + (res.data.total || 0) + ' 条日志';
                perLogPageInfo.textContent = '第 ' + state.logPage + ' / ' + state.logTotalPages + ' 页';
                perLogPrevBtn.disabled = state.logPage <= 1;
                perLogNextBtn.disabled = state.logPage >= state.logTotalPages;
                if (perLogJumpInput) {
                    perLogJumpInput.value = state.logPage;
                    perLogJumpInput.max = state.logTotalPages;
                }

                if (showRefreshMsg) {
                    notify('日志已刷新', 'success');
                }
            })
            .catch(function () {
                notify('加载日志失败', 'error');
                perLogBody.innerHTML = '<tr><td colspan="7" class="empty-cell">加载失败</td></tr>';
            });
    }

    document.getElementById('perLogSearchBtn').addEventListener('click', function () {
        state.logPage = 1;
        loadPerLogs();
    });

    document.getElementById('perLogResetBtn').addEventListener('click', function () {
        applyDefaultDateRange();
        if (perLogKeyword) perLogKeyword.value = '';
        state.logPage = 1;
        loadPerLogs();
    });

    document.getElementById('perLogRefreshBtn').addEventListener('click', function () {
        loadPerLogs(true);
    });

    perLogPrevBtn.addEventListener('click', function () {
        if (state.logPage <= 1) return;
        state.logPage -= 1;
        loadPerLogs();
    });

    perLogNextBtn.addEventListener('click', function () {
        if (state.logPage >= state.logTotalPages) return;
        state.logPage += 1;
        loadPerLogs();
    });

    if (perLogJumpBtn) {
        perLogJumpBtn.addEventListener('click', function () {
            var target = parseInt(perLogJumpInput.value, 10);
            if (!target || target < 1) target = 1;
            if (target > state.logTotalPages) target = state.logTotalPages;
            state.logPage = target;
            loadPerLogs();
        });
    }

    applyDefaultDateRange();
    switchTab(state.activeTab, true);
    loadDevice();
})();
