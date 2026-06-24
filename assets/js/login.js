(function () {
    var form = document.getElementById('loginForm');
    var msgBox = document.getElementById('msgBox');
    var loginBtn = document.getElementById('loginBtn');
    var captchaImg = document.getElementById('captchaImg');

    if (!form) return;

    function loadCaptcha() {
        if (captchaImg) {
            captchaImg.src = 'api/auth/captcha.php?t=' + Date.now();
        }
    }

    if (captchaImg) {
        captchaImg.addEventListener('click', loadCaptcha);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        MDM.hideMsg(msgBox);

        loginBtn.disabled = true;
        loginBtn.textContent = '登录中...';

        var data = {
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            captcha: document.getElementById('captcha').value
        };

        MDM.api('api/auth/login.php', { method: 'POST', body: data })
            .then(function (res) {
                if (res.code === 0) {
                    MDM.showMsg(msgBox, res.msg, 'success');
                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 600);
                } else {
                    MDM.showMsg(msgBox, res.msg, 'error');
                    loadCaptcha();
                    document.getElementById('captcha').value = '';
                    loginBtn.disabled = false;
                    loginBtn.textContent = '登 录';
                }
            })
            .catch(function () {
                MDM.showMsg(msgBox, '网络请求失败，请稍后重试', 'error');
                loginBtn.disabled = false;
                loginBtn.textContent = '登 录';
            });
    });
})();
