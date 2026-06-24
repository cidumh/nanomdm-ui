(function () {
    var msgBox = document.getElementById('msgBox');
    var disabledBox = document.getElementById('depDisabledBox');
    var mainBox = document.getElementById('depManageMain');
    var form = document.getElementById('depProfileForm');
    var saveBtn = document.getElementById('saveProfileBtn');
    var logoutBtn = document.getElementById('logoutBtn');
    var skipEnabled = document.getElementById('skip_setup_enabled');
    var skipFields = document.getElementById('skipSetupFields');
    var profileListBody = document.getElementById('profileListBody');
    var profileModal = document.getElementById('profileDetailModal');
    var profileModalContent = document.getElementById('profileDetailContent');
    var profileModalTitle = document.getElementById('profileModalTitle');
    var closeProfileModal = document.getElementById('closeProfileModal');
    var profileModalBackdrop = document.getElementById('profileModalBackdrop');

    var skipLabels = {
        AppleID: 'AppleID',
        Biometric: '指纹面容',
        Diagnostics: '应用数据共享',
        Location: '定位设置',
        Passcode: '屏幕密码',
        Payment: 'Apple Pay',
        Privacy: '隐私权限设置',
        ScreenTime: '屏幕使用时间',
        Siri: 'Siri设置',
        SoftwareUpdate: '系统更新',
        TOS: '服务条款',
        Welcome: '欢迎首页',
        Android: '从安卓迁移',
        Appearance: '外观选择',
        DeviceToDeviceMigration: '设备迁移',
        iMessageAndFaceTime: 'iMessage设置',
        Intelligence: '智能功能设置',
        Keyboard: '键盘设置',
        MessagingActivationUsingPhoneNumber: 'iMessage 激活',
        Safety: '安全设置'
    };

    if (!mainBox) return;

    document.querySelectorAll('.dep-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var name = tab.getAttribute('data-tab');
            document.querySelectorAll('.dep-tab').forEach(function (t) {
                t.classList.toggle('active', t === tab);
            });
            document.getElementById('tabConfig').classList.toggle('hidden', name !== 'config');
            document.getElementById('tabDevices').classList.toggle('hidden', name !== 'devices');
            if (name === 'devices' && window.DepDevices) {
                window.DepDevices.loadIfNeeded();
            }
        });
    });

    if (skipEnabled && skipFields) {
        skipEnabled.addEventListener('change', function () {
            skipFields.classList.toggle('hidden', !skipEnabled.checked);
        });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function boolText(val) {
        return val ? '是' : '否';
    }

    function openProfileModal() {
        profileModal.classList.remove('hidden');
    }

    function hideProfileModal() {
        profileModal.classList.add('hidden');
    }

    if (closeProfileModal) {
        closeProfileModal.addEventListener('click', hideProfileModal);
    }
    if (profileModalBackdrop) {
        profileModalBackdrop.addEventListener('click', hideProfileModal);
    }

    function renderProfileDetail(data) {
        var items = [
            ['配置文件名称', data.profile_name],
            ['配置 ID', data.profile_uuid],
            ['MDM 服务器地址', data.url],
            ['WEB URL 地址', data.configuration_web_url],
            ['组织名称', data.department],
            ['监管模式', boolText(data.is_supervised)],
            ['等待配置完成', boolText(data.await_device_configured)],
            ['强制安装', boolText(data.is_mandatory)],
            ['可移除配置', boolText(data.is_mdm_removable)],
            ['允许配对', boolText(data.allow_pairing)],
            ['系统语言', data.language],
            ['国家', data.region],
            ['邮箱', data.support_email_address],
            ['电话', data.support_phone_number]
        ];

        var html = '<dl class="profile-detail-list">';
        items.forEach(function (item) {
            if (item[1] !== undefined && item[1] !== null && item[1] !== '') {
                html += '<div class="profile-detail-item"><dt>' + escapeHtml(item[0]) + '</dt><dd>' + escapeHtml(item[1]) + '</dd></div>';
            }
        });

        if (data.skip_setup_items && data.skip_setup_items.length) {
            html += '<div class="profile-detail-item"><dt>跳过系统设置</dt><dd><div class="profile-detail-tags">';
            data.skip_setup_items.forEach(function (key) {
                html += '<span>' + escapeHtml(skipLabels[key] || key) + '</span>';
            });
            html += '</div></dd></div>';
        }

        html += '</dl>';
        profileModalContent.innerHTML = html;
        profileModalTitle.textContent = data.profile_name ? ('配置详情 - ' + data.profile_name) : '配置详情';
    }

    function viewProfile(profileUuid, profileName) {
        openProfileModal();
        profileModalTitle.textContent = profileName ? ('配置详情 - ' + profileName) : '配置详情';
        profileModalContent.innerHTML = '<p class="loading-tip">加载中...</p>';

        MDM.api('api/dep_manage/get_profile.php?profile_uuid=' + encodeURIComponent(profileUuid), { lock: false })
            .then(function (res) {
                if (res.code === 0 && res.data) {
                    renderProfileDetail(res.data);
                } else {
                    profileModalContent.innerHTML = '<p class="loading-tip" style="color:var(--danger)">' + escapeHtml(res.msg || '加载失败') + '</p>';
                }
            })
            .catch(function () {
                profileModalContent.innerHTML = '<p class="loading-tip" style="color:var(--danger)">网络请求失败</p>';
            });
    }

    function bindViewButtons() {
        profileListBody.querySelectorAll('.btn-view-profile').forEach(function (btn) {
            btn.addEventListener('click', function () {
                viewProfile(btn.getAttribute('data-uuid'), btn.getAttribute('data-name'));
            });
        });
    }

    function loadProfileList() {
        MDM.api('api/dep_manage/list_profiles.php').then(function (res) {
            if (res.code !== 0 || !Array.isArray(res.data)) return;

            if (res.data.length === 0) {
                profileListBody.innerHTML = '<tr><td colspan="5" class="empty-cell">暂无配置</td></tr>';
                return;
            }

            profileListBody.innerHTML = res.data.map(function (row) {
                return '<tr>'
                    + '<td>' + escapeHtml(row.profile_name) + '</td>'
                    + '<td class="uuid-cell">' + escapeHtml(row.profile_uuid) + '</td>'
                    + '<td>' + escapeHtml(row.department) + '</td>'
                    + '<td>' + escapeHtml(row.updated_at || row.created_at || '-') + '</td>'
                    + '<td><button type="button" class="btn-link btn-view-profile" data-uuid="' + escapeHtml(row.profile_uuid) + '" data-name="' + escapeHtml(row.profile_name) + '">查看</button></td>'
                    + '</tr>';
            }).join('');

            bindViewButtons();
        });
    }

    function collectSkipSetup() {
        var result = {};
        document.querySelectorAll('.skip-item').forEach(function (el) {
            result[el.getAttribute('data-key')] = el.checked;
        });
        return result;
    }

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    });

    MDM.api('api/dep_manage/status.php').then(function (res) {
        if (res.code !== 0) {
            MDM.showMsg(msgBox, res.msg || '加载失败', 'error');
            return;
        }

        if (res.data && res.data.dep_enabled) {
            disabledBox.classList.add('hidden');
            mainBox.classList.remove('hidden');
            loadProfileList();
        } else {
            disabledBox.classList.remove('hidden');
            mainBox.classList.add('hidden');
        }
    }).catch(function () {
        MDM.showMsg(msgBox, '加载失败', 'error');
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            MDM.hideMsg(msgBox);

            var data = {
                profile_name: document.getElementById('profile_name').value.trim(),
                mdm_url: document.getElementById('mdm_url').value.trim(),
                web_url: document.getElementById('web_url').value.trim(),
                department: document.getElementById('department').value.trim(),
                org_magic: document.getElementById('org_magic').value.trim(),
                is_supervised: document.getElementById('is_supervised').checked,
                await_device_configured: document.getElementById('await_device_configured').checked,
                is_mandatory: document.getElementById('is_mandatory').checked,
                is_mdm_removable: document.getElementById('is_mdm_removable').checked,
                device_serials: document.getElementById('device_serials').value,
                language: document.getElementById('language').value.trim(),
                region: document.getElementById('region').value.trim(),
                support_email: document.getElementById('support_email').value.trim(),
                support_phone: document.getElementById('support_phone').value.trim(),
                skip_setup_enabled: skipEnabled.checked,
                skip_setup: collectSkipSetup()
            };

            MDM.api('api/dep_manage/save_profile.php', { method: 'POST', body: data })
                .then(function (res) {
                    if (res.code === 0) {
                        MDM.showMsg(msgBox, res.msg + '（配置 ID: ' + res.data.profile_uuid + '）', 'success');
                        loadProfileList();
                    } else {
                        MDM.showMsg(msgBox, res.msg, 'error');
                    }
                })
                .catch(function () {
                    MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
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
