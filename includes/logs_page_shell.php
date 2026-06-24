<?php
/**
 * 日志页面公共筛选区
 * 需设置 $logSearchScopes 数组
 */
?>
<div class="logs-page<?php echo !empty($logPageCompact) ? ' logs-compact' : ''; ?>">
    <div class="page-head">
        <h2 class="page-title"><?php echo htmlspecialchars($logPageTitle); ?></h2>
        <p class="page-desc"><?php echo htmlspecialchars($logPageDesc); ?></p>
    </div>

    <section class="logs-filter-block">
        <form id="logFilterForm" class="logs-filter-form">
            <div class="logs-filter-row">
                <div class="filter-item">
                    <label for="dateFrom">开始日期</label>
                    <input type="date" id="dateFrom" name="date_from" class="log-date-input" autocomplete="off">
                </div>
                <div class="filter-item">
                    <label for="dateTo">结束日期</label>
                    <input type="date" id="dateTo" name="date_to" class="log-date-input" autocomplete="off">
                </div>
                <div class="filter-item">
                    <label for="searchScope">搜索范围</label>
                    <select id="searchScope" name="search_scope">
<?php foreach ($logSearchScopes as $scope): ?>
                        <option value="<?php echo htmlspecialchars($scope['value']); ?>"><?php echo htmlspecialchars($scope['label']); ?></option>
<?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item filter-grow">
                    <label for="searchKeyword">关键词</label>
                    <input type="text" id="searchKeyword" name="search_keyword" placeholder="输入搜索关键词">
                </div>
                <div class="filter-item filter-actions">
                    <label class="filter-actions-label" aria-hidden="true">&nbsp;</label>
                    <div class="logs-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm" id="logSearchBtn">搜索</button>
                        <button type="button" class="btn btn-text btn-sm" id="logResetBtn">重置</button>
                        <button type="button" class="btn btn-refresh" id="logRefreshBtn"><span class="btn-icon" aria-hidden="true">↻</span>刷新日志</button>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section class="logs-table-block">
        <div class="logs-meta" id="logMeta">加载中...</div>
        <div class="table-wrap logs-table-wrap">
            <table class="logs-table" id="logTable">
                <thead id="logTableHead"></thead>
                <tbody id="logTableBody">
                    <tr><td colspan="20" class="empty-cell">加载中...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="logs-pagination" id="logPagination">
            <button type="button" class="btn btn-text" id="logPrevBtn">上一页</button>
            <span class="logs-page-info" id="logPageInfo">第 1 / 1 页</span>
            <button type="button" class="btn btn-text" id="logNextBtn">下一页</button>
            <div class="logs-jump">
                <label for="logJumpInput">跳转到</label>
                <input type="number" id="logJumpInput" min="1" value="1">
                <span>页</span>
                <button type="button" class="btn btn-text" id="logJumpBtn">跳转</button>
            </div>
        </div>
    </section>

    <div id="msgBox" class="msg-box hidden"></div>
</div>

<script>
window.MDM_LOG_PAGE = <?php echo json_encode($logPageConfig, JSON_UNESCAPED_UNICODE); ?>;
</script>
