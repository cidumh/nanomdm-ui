/**
 * 公共 JS 工具
 */

var MDM = MDM || {};

MDM._busyCount = 0;

MDM.setBusy = function (on) {
    if (on) {
        MDM._busyCount++;
    } else {
        MDM._busyCount = Math.max(0, MDM._busyCount - 1);
    }
    document.body.classList.toggle('mdm-busy', MDM._busyCount > 0);
};

MDM.isBusy = function () {
    return MDM._busyCount > 0;
};

MDM.runLocked = function (fn) {
    if (MDM.isBusy()) {
        return Promise.resolve({ code: -1, msg: '请稍候，正在处理中...' });
    }
    MDM.setBusy(true);
    return Promise.resolve()
        .then(fn)
        .finally(function () {
            MDM.setBusy(false);
        });
};

MDM.api = function (url, options) {
    options = options || {};
    var method = (options.method || 'GET').toUpperCase();
    var body = options.body || null;
    var lock = options.lock;
    if (lock === undefined) {
        lock = method !== 'GET';
    }

    var doFetch = function () {
        var fetchOpts = {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        };

        if (body && method !== 'GET') {
            fetchOpts.body = JSON.stringify(body);
        }

        return fetch(url, fetchOpts).then(function (res) {
            return res.json();
        });
    };

    if (!lock) {
        return doFetch();
    }

    return MDM.runLocked(doFetch);
};

MDM.showMsg = function (el, text, type) {
    if (!el) return;
    el.textContent = text;
    el.className = 'msg-box ' + (type || 'error');
    el.classList.remove('hidden');
};

MDM.hideMsg = function (el) {
    if (!el) return;
    el.classList.add('hidden');
};
