(function () {
    var config = window.MDM_LOG_PAGE;
    if (!config) return;

    var msgBox = document.getElementById('msgBox');
    var filterForm = document.getElementById('logFilterForm');
    var tableHead = document.getElementById('logTableHead');
    var tableBody = document.getElementById('logTableBody');
    var logMeta = document.getElementById('logMeta');
    var pageInfo = document.getElementById('logPageInfo');
    var prevBtn = document.getElementById('logPrevBtn');
    var nextBtn = document.getElementById('logNextBtn');
    var jumpInput = document.getElementById('logJumpInput');
    var jumpBtn = document.getElementById('logJumpBtn');
    var resetBtn = document.getElementById('logResetBtn');
    var refreshBtn = document.getElementById('logRefreshBtn');
    var logoutBtn = document.getElementById('logoutBtn');

    var state = {
        page: 1,
        total: 0,
        totalPages: 1,
        currentList: []
    };

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function formatDateObj(d) {
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }

    function getDefaultDateRange() {
        var today = new Date();
        var from = new Date(today);
        from.setDate(from.getDate() - 6);
        return {
            from: formatDateObj(from),
            to: formatDateObj(today)
        };
    }

    function applyDefaultDateRange() {
        var range = getDefaultDateRange();
        document.getElementById('dateFrom').value = range.from;
        document.getElementById('dateTo').value = range.to;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function displayValue(raw) {
        if (raw === null || raw === undefined || raw === '') return '-';
        return String(raw);
    }

    function copyText(text) {
        var value = text === null || text === undefined ? '' : String(text);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                MDM.showMsg(msgBox, '已复制到剪贴板', 'success');
            }).catch(function () {
                fallbackCopy(value);
            });
            return;
        }
        fallbackCopy(value);
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            var ok = document.execCommand('copy');
            MDM.showMsg(msgBox, ok ? '已复制到剪贴板' : '复制失败，请手动选择复制', ok ? 'success' : 'error');
        } catch (e) {
            MDM.showMsg(msgBox, '复制失败，请手动选择复制', 'error');
        }
        document.body.removeChild(ta);
    }

    function resolveRowClass(row) {
        if (!config.rowHighlight) return '';
        var field = config.rowHighlight.field;
        var val = String(row[field] || '');
        var rules = config.rowHighlight.rules || [];
        for (var i = 0; i < rules.length; i++) {
            var rule = rules[i];
            if (rule.exact ? val === rule.match : val.indexOf(rule.match) !== -1) {
                return rule.class;
            }
        }
        return '';
    }

    function renderHead() {
        var cols = config.columns || [];
        tableHead.innerHTML = '<tr>' + cols.map(function (col) {
            return '<th class="' + escapeHtml(col.class || '') + '">' + escapeHtml(col.label) + '</th>';
        }).join('') + '</tr>';
    }

    function renderCell(row, col, rowIndex) {
        if (col.type === 'copy') {
            var raw = row[col.key];
            var hasContent = raw !== null && raw !== undefined && String(raw).trim() !== '';
            var cls = (col.class || 'col-copy').trim();
            if (!hasContent) {
                return '<td class="' + escapeHtml(cls) + '">-</td>';
            }
            var text = String(raw);
            return '<td class="' + escapeHtml(cls) + '">'
                + '<div class="col-copy-inner">'
                + '<span class="cell-copy-text" title="' + escapeHtml(text) + '">' + escapeHtml(text) + '</span>'
                + '<button type="button" class="btn-log-copy" data-row="' + rowIndex + '" data-field="' + escapeHtml(col.key) + '">复制</button>'
                + '</div></td>';
        }

        var raw = row[col.key];
        var text = displayValue(raw);
        var cls = col.class || '';
        return '<td class="' + escapeHtml(cls.trim()) + '" title="' + escapeHtml(text) + '">' + escapeHtml(text) + '</td>';
    }

    function renderRows(list) {
        var cols = config.columns || [];
        state.currentList = list || [];

        if (!list || !list.length) {
            tableBody.innerHTML = '<tr><td colspan="' + cols.length + '" class="empty-cell">暂无日志</td></tr>';
            return;
        }

        tableBody.innerHTML = list.map(function (row, rowIndex) {
            var rowCls = resolveRowClass(row);
            return '<tr class="' + rowCls + '">' + cols.map(function (col) {
                return renderCell(row, col, rowIndex);
            }).join('') + '</tr>';
        }).join('');
    }

    function getFilters() {
        return {
            date_from: document.getElementById('dateFrom').value,
            date_to: document.getElementById('dateTo').value,
            search_scope: document.getElementById('searchScope').value,
            search_keyword: document.getElementById('searchKeyword').value.trim(),
            page: state.page
        };
    }

    function updatePagination(data) {
        state.total = data.total || 0;
        state.totalPages = data.total_pages || 1;
        state.page = data.page || 1;
        logMeta.textContent = '共 ' + state.total + ' 条日志，共 ' + state.totalPages + ' 页，每页 20 条';
        pageInfo.textContent = '第 ' + state.page + ' / ' + state.totalPages + ' 页';
        jumpInput.value = state.page;
        jumpInput.max = state.totalPages;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= state.totalPages;
    }

    function loadLogs(showRefreshMsg) {
        MDM.api(config.apiUrl + '?' + new URLSearchParams(getFilters()).toString())
            .then(function (res) {
                if (res.code !== 0 || !res.data) {
                    MDM.showMsg(msgBox, res.msg || '加载日志失败', 'error');
                    return;
                }
                renderRows(res.data.list || []);
                updatePagination(res.data);
                if (showRefreshMsg) {
                    MDM.showMsg(msgBox, '日志已刷新', 'success');
                }
            })
            .catch(function () {
                MDM.showMsg(msgBox, '加载日志失败', 'error');
            });
    }

    renderHead();
    applyDefaultDateRange();
    loadLogs();

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-log-copy');
            if (!btn) return;
            var rowIndex = parseInt(btn.getAttribute('data-row'), 10);
            var field = btn.getAttribute('data-field');
            var row = state.currentList[rowIndex];
            if (!row || !field) return;
            copyText(row[field] || '');
        });
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        state.page = 1;
        loadLogs();
    });

    resetBtn.addEventListener('click', function () {
        filterForm.reset();
        applyDefaultDateRange();
        state.page = 1;
        loadLogs();
    });

    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            loadLogs(true);
        });
    }

    prevBtn.addEventListener('click', function () {
        if (state.page <= 1) return;
        state.page -= 1;
        loadLogs();
    });

    nextBtn.addEventListener('click', function () {
        if (state.page >= state.totalPages) return;
        state.page += 1;
        loadLogs();
    });

    jumpBtn.addEventListener('click', function () {
        var target = parseInt(jumpInput.value, 10);
        if (!target || target < 1) target = 1;
        if (target > state.totalPages) target = state.totalPages;
        state.page = target;
        loadLogs();
    });

    MDM.api('api/auth/check.php').then(function (res) {
        if (res.code !== 0) window.location.href = 'index.php';
    });

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            MDM.api('api/auth/logout.php').then(function () {
                window.location.href = 'index.php';
            });
        });
    }
})();
