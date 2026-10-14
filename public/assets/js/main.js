(function () {
    'use strict';

    // ── DateTime ──────────────────────────────
    const DAYS = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const MONTHS = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];

    function updateDateTime() {
        const dayEl  = document.getElementById('current-day');
        const dateEl = document.getElementById('current-date');
        if (!dayEl || !dateEl) return;

        const now = new Date();
        let h = now.getHours();
        const m = String(now.getMinutes()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;

        dayEl.textContent  = DAYS[now.getDay()];
        dateEl.textContent = `${MONTHS[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()} at ${h}:${m} ${ampm}`;
    }

    updateDateTime();
    setInterval(updateDateTime, 30000);


    // ── Toast (usable by ALL screens) ─────────
    window.showToast = function (msg, type = 'success') {
        let toast = document.getElementById('pos-toast');

        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'pos-toast';
            toast.style.cssText = `
                position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(20px);
                background: #1a1a1a; color: #fff; padding: 13px 22px; border-radius: 10px;
                font-size: 13.5px; font-family: 'DM Sans', sans-serif; line-height: 1.5;
                white-space: pre-line; max-width: 320px; text-align: center;
                z-index: 9999; opacity: 0; transition: opacity 0.25s, transform 0.25s;
                box-shadow: 0 8px 24px rgba(0,0,0,0.25);
            `;
            document.body.appendChild(toast);
        }

        toast.style.background = type === 'error' ? '#c0392b' : '#2D6A4F';
        toast.textContent = msg;
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';

        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(10px)';
        }, 3500);
    };


    // ── Shared Helpers ────────────────────────
    window.escHtml = function (str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    };


    // ── Profile Dropdown ──────────────────────
    const profileBtn = document.getElementById('profile-btn');
    const profileMenu = document.getElementById('profile-menu');
    const dropdown = document.getElementById('profile-dropdown');

    if (profileBtn && dropdown && profileMenu) {

        // Toggle dropdown
        profileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!profileMenu.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

    }


    // ── Excel Button (optional placeholder) ───
    const excelBtn = document.getElementById('excel-btn');

    if (excelBtn) {
        excelBtn.addEventListener('click', function () {
            showToast("Excel export coming soon!");
        });
    }

})();