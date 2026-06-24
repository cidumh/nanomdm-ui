(function () {
    var welcomeUser = document.getElementById('welcomeUser');
    var logoutBtn = document.getElementById('logoutBtn');
    var statDeviceTotal = document.getElementById('statDeviceTotal');
    var statTodayActive = document.getElementById('statTodayActive');
    var statTodayComm = document.getElementById('statTodayComm');

    MDM.api('api/auth/check.php')
        .then(function (res) {
            if (res.code !== 0) {
                window.location.href = 'index.php';
                return;
            }
            if (welcomeUser && res.data) {
                welcomeUser.textContent = res.data.username;
            }
        })
        .catch(function () {
            window.location.href = 'index.php';
        });

    MDM.api('api/dashboard/stats.php')
        .then(function (res) {
            if (res.code === 0 && res.data) {
                if (statDeviceTotal) statDeviceTotal.textContent = res.data.device_total;
                if (statTodayActive) statTodayActive.textContent = res.data.today_active;
                if (statTodayComm) statTodayComm.textContent = res.data.today_comm;
            }
        });

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            MDM.api('api/auth/logout.php')
                .then(function () {
                    window.location.href = 'index.php';
                });
        });
    }
})();
