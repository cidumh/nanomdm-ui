<div class="settings-page">
    <div class="page-head">
        <h2 class="page-title">系统设置</h2>
        <p class="page-desc">修改面板名称、管理员账户及数据库连接配置</p>
    </div>

    <form id="settingsForm" class="settings-form">
        <section class="settings-block">
            <h3 class="block-title">面板设置</h3>
            <div class="form-row">
                <label for="site_name">面板名称</label>
                <input type="text" id="site_name" name="site_name" required>
            </div>
        </section>

        <section class="settings-block">
            <h3 class="block-title">管理员账户</h3>
            <p class="block-tip">修改用户名或密码后，当前登录状态将失效，需重新登录</p>
            <div class="form-row">
                <label for="admin_user">管理员用户名</label>
                <input type="text" id="admin_user" name="admin_user" autocomplete="username" required>
            </div>
            <div class="form-row">
                <label for="admin_pass">新密码</label>
                <input type="password" id="admin_pass" name="admin_pass" placeholder="不修改请留空" autocomplete="new-password">
            </div>
            <div class="form-row">
                <label for="admin_pass2">确认新密码</label>
                <input type="password" id="admin_pass2" name="admin_pass2" placeholder="不修改请留空" autocomplete="new-password">
            </div>
        </section>

        <section class="settings-block">
            <h3 class="block-title">页脚备案</h3>
            <p class="block-tip">配置网站底部 ICP 备案与公安备案显示内容及查询链接，留空则不显示对应备案</p>
            <div class="form-row-inline">
                <div class="form-row">
                    <label for="footer_icp_text">ICP 备案号</label>
                    <input type="text" id="footer_icp_text" name="footer_icp_text" placeholder="粤ICP备2024204088号">
                </div>
                <div class="form-row">
                    <label for="footer_icp_url">ICP 查询链接</label>
                    <input type="url" id="footer_icp_url" name="footer_icp_url" placeholder="https://beian.miit.gov.cn/">
                </div>
            </div>
            <div class="form-row-inline">
                <div class="form-row">
                    <label for="footer_ga_text">公安备案号</label>
                    <input type="text" id="footer_ga_text" name="footer_ga_text" placeholder="粤公网安备44510302000351号">
                </div>
                <div class="form-row">
                    <label for="footer_ga_url">公安备案查询链接</label>
                    <input type="url" id="footer_ga_url" name="footer_ga_url" placeholder="http://www.beian.gov.cn/portal/registerSystemInfo">
                </div>
            </div>
        </section>

        <section class="settings-block">
            <h3 class="block-title">数据库连接设置</h3>
            <p class="block-tip">修改后将测试连接并更新配置文件，密码留空则保持原密码不变</p>
            <div class="form-row">
                <label for="db_host">连接地址</label>
                <input type="text" id="db_host" name="db_host" required>
            </div>
            <div class="form-row">
                <label for="db_port">端口</label>
                <input type="number" id="db_port" name="db_port" required>
            </div>
            <div class="form-row">
                <label for="db_name">数据库名</label>
                <input type="text" id="db_name" name="db_name" required>
            </div>
            <div class="form-row">
                <label for="db_user">用户名</label>
                <input type="text" id="db_user" name="db_user" required>
            </div>
            <div class="form-row">
                <label for="db_pass">密码</label>
                <input type="password" id="db_pass" name="db_pass" placeholder="不修改请留空">
            </div>
        </section>

        <div class="form-actions settings-actions">
            <button type="submit" class="btn btn-primary" id="saveBtn">保存设置</button>
        </div>
    </form>

    <div id="msgBox" class="msg-box hidden"></div>
</div>
