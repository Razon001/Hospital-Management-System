// Shared UI behavior: mobile sidebar toggle + auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('appSidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        sidebar && sidebar.classList.remove('open');
        overlay && overlay.classList.remove('show');
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay && overlay.classList.toggle('show');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Auto-dismiss flash alerts after 6 seconds
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                var a = bootstrap.Alert.getOrCreateInstance(alertEl);
                a.close();
            } else {
                alertEl.style.display = 'none';
            }
        }, 6000);
    });
});
