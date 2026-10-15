(function () {
    'use strict';

    const endpoint = window.ORDER_NOTIFICATIONS_URL;
    const ordersUrl = window.ORDERS_PAGE_URL || '/orders';
    if (!endpoint) return;

    const knownKey = 'twistroll_known_admin_orders_v2';
    const readKey = 'twistroll_read_admin_orders_v2';
    const dataKey = 'twistroll_admin_order_notifications_v2';
    const maxStored = 60;

    const loadArray = (key) => {
        try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (_) { return []; }
    };

    let known = new Set(loadArray(knownKey).map(String));
    let read = new Set(loadArray(readKey).map(String));
    let notifications = loadArray(dataKey);
    let audioReady = false;
    let audioContext = null;
    let bellButton = null;
    let bellBadge = null;
    let panel = null;
    let list = null;

    const save = () => {
        try {
            localStorage.setItem(knownKey, JSON.stringify(Array.from(known).slice(-maxStored)));
            localStorage.setItem(readKey, JSON.stringify(Array.from(read).slice(-maxStored)));
            localStorage.setItem(dataKey, JSON.stringify(notifications.slice(0, maxStored)));
        } catch (_) {}
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    function label(order) {
        if (order.order_type === 'delivery' && order.customer_name) return order.customer_name;
        if (order.beeper_number) return `Beeper #${order.beeper_number}`;
        return `Order #${String(order.id).padStart(4, '0')}`;
    }

    function typeLabel(type) {
        return ({'dine-in':'Dine In','take-out':'Take Out','delivery':'Delivery','pick-up':'Pick Up'})[type] || type;
    }

    function formatTime(value) {
        if (!value) return '';
        const d = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return '';
        return d.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
    }

    function primeAudio() {
        if (audioReady) return;
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            audioContext = audioContext || new Ctx();
            if (audioContext.state === 'suspended') audioContext.resume();
            audioReady = true;
        } catch (_) {}
    }

    ['pointerdown', 'keydown', 'touchstart'].forEach(eventName => {
        document.addEventListener(eventName, primeAudio, {once: true, passive: true});
    });

    function playNotificationSound() {
        try {
            if (!audioReady || !audioContext) return;
            if (audioContext.state === 'suspended') audioContext.resume();
            const now = audioContext.currentTime;
            [0, 0.13].forEach((delay, index) => {
                const osc = audioContext.createOscillator();
                const gain = audioContext.createGain();
                osc.type = 'sine';
                osc.frequency.value = index === 0 ? 740 : 980;
                gain.gain.setValueAtTime(0.0001, now + delay);
                gain.gain.exponentialRampToValueAtTime(0.09, now + delay + 0.012);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.16);
                osc.connect(gain); gain.connect(audioContext.destination);
                osc.start(now + delay); osc.stop(now + delay + 0.18);
            });
        } catch (_) {}
    }

    function unreadCount() {
        return notifications.filter(n => !read.has(String(n.id))).length;
    }

    function updateBadge() {
        if (!bellBadge) return;
        const count = unreadCount();
        bellBadge.textContent = count > 99 ? '99+' : String(count);
        bellBadge.hidden = count === 0;
        bellButton?.classList.toggle('has-unread', count > 0);
        bellButton?.setAttribute('aria-label', count ? `Notifications, ${count} unread` : 'Notifications');
    }

    function markRead(id) {
        read.add(String(id));
        save();
        renderList();
        updateBadge();
    }

    function markAllRead() {
        notifications.forEach(n => read.add(String(n.id)));
        save();
        renderList();
        updateBadge();
    }

    function renderList() {
        if (!list) return;
        if (!notifications.length) {
            list.innerHTML = '<div class="order-notification-empty">No notifications yet.</div>';
            return;
        }

        list.innerHTML = notifications.slice(0, 20).map(order => {
            const id = String(order.id);
            const unread = !read.has(id);
            return `<a class="order-notification-item${unread ? ' is-unread' : ''}" href="${escapeHtml(ordersUrl)}" data-notification-id="${escapeHtml(id)}">
                <span class="order-notification-item__dot" aria-hidden="true"></span>
                <span class="order-notification-item__body">
                    <strong>New order from Admin</strong>
                    <span>${escapeHtml(typeLabel(order.order_type))} · ${escapeHtml(label(order))}</span>
                    <small>₱${Number(order.total || 0).toLocaleString()}${formatTime(order.created_at) ? ` · ${escapeHtml(formatTime(order.created_at))}` : ''}</small>
                </span>
                <span class="order-notification-item__chevron">›</span>
            </a>`;
        }).join('');

        list.querySelectorAll('[data-notification-id]').forEach(item => {
            item.addEventListener('click', () => markRead(item.dataset.notificationId));
        });
    }

    function ensureBell() {
        if (bellButton) return;
        const navbarRight = document.querySelector('.navbar__right');
        const profileMenu = navbarRight?.querySelector('.profile-menu');
        if (!navbarRight || !profileMenu) return;

        const wrap = document.createElement('div');
        wrap.className = 'order-notification-center';
        wrap.innerHTML = `
            <button type="button" class="order-notification-bell" aria-label="Notifications" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <span class="order-notification-badge" hidden>0</span>
            </button>
            <div class="order-notification-panel" hidden>
                <div class="order-notification-panel__head">
                    <div><strong>Notifications</strong><small>Admin orders</small></div>
                    <button type="button" class="order-notification-read-all">Mark all as read</button>
                </div>
                <div class="order-notification-list"></div>
            </div>`;

        navbarRight.insertBefore(wrap, profileMenu);
        bellButton = wrap.querySelector('.order-notification-bell');
        bellBadge = wrap.querySelector('.order-notification-badge');
        panel = wrap.querySelector('.order-notification-panel');
        list = wrap.querySelector('.order-notification-list');

        bellButton.addEventListener('click', (event) => {
            event.stopPropagation();
            const opening = panel.hidden;
            panel.hidden = !opening;
            bellButton.setAttribute('aria-expanded', opening ? 'true' : 'false');
            if (opening) renderList();
        });
        wrap.querySelector('.order-notification-read-all').addEventListener('click', markAllRead);
        document.addEventListener('click', (event) => {
            if (!wrap.contains(event.target)) {
                panel.hidden = true;
                bellButton.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                panel.hidden = true;
                bellButton.setAttribute('aria-expanded', 'false');
                bellButton.focus();
            }
        });

        renderList();
        updateBadge();
    }

    function showToast(order) {
        const existing = document.querySelector('.admin-order-notice');
        if (existing) existing.remove();
        const notice = document.createElement('a');
        notice.className = 'admin-order-notice';
        notice.href = ordersUrl;
        notice.dataset.notificationId = String(order.id);
        notice.innerHTML = `<span class="admin-order-notice__dot"></span><span><strong>New order from Admin</strong><small>${escapeHtml(typeLabel(order.order_type))} · ${escapeHtml(label(order))} · ₱${Number(order.total || 0).toLocaleString()}</small></span><span class="admin-order-notice__arrow">View →</span>`;
        notice.addEventListener('click', () => markRead(order.id));
        document.body.appendChild(notice);
        requestAnimationFrame(() => notice.classList.add('show'));
        setTimeout(() => { notice.classList.remove('show'); setTimeout(() => notice.remove(), 250); }, 8000);
    }

    function addNotification(order, announce) {
        const id = String(order.id);
        known.add(id);
        notifications = [order, ...notifications.filter(n => String(n.id) !== id)].slice(0, maxStored);
        save();
        renderList();
        updateBadge();
        if (announce) {
            playNotificationSound();
            showToast(order);
        }
    }

    async function poll() {
        try {
            const response = await fetch(endpoint, {headers: {'Accept':'application/json'}, cache:'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            ensureBell();
            const fresh = (data.orders || []).filter(o => !known.has(String(o.id))).reverse();
            fresh.forEach((order, index) => setTimeout(() => addNotification(order, true), index * 500));
        } catch (_) {}
    }

    const style = document.createElement('style');
    style.textContent = `
      .order-notification-center{position:relative;display:flex;align-items:center;margin-right:8px}
      .order-notification-bell{position:relative;width:44px;height:44px;border:1px solid rgba(215,189,80,.72);border-radius:50%;background:#fffdf0;color:#173c27;display:grid;place-items:center;cursor:pointer;transition:background .18s ease,transform .18s ease,box-shadow .18s ease}
      .order-notification-bell:hover,.order-notification-bell:focus-visible{background:#f7efc9;box-shadow:0 5px 16px rgba(23,60,39,.12);outline:2px solid transparent}
      .order-notification-bell:active{transform:scale(.96)}
      .order-notification-bell svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
      .order-notification-bell.has-unread svg{animation:orderBellRing .55s ease}
      .order-notification-badge{position:absolute;top:-5px;right:-5px;min-width:20px;height:20px;padding:0 5px;border:2px solid #fffdf0;border-radius:999px;background:#a71912;color:white;font-size:10px;font-weight:800;line-height:16px;text-align:center}
      .order-notification-panel{position:absolute;right:0;top:54px;width:min(390px,calc(100vw - 28px));max-height:min(520px,calc(100dvh - 110px));overflow:hidden;border:1px solid #ddcb78;border-radius:18px;background:#fffdf0;box-shadow:0 18px 45px rgba(23,60,39,.2);z-index:100000}
      .order-notification-panel__head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 16px;border-bottom:1px solid rgba(221,203,120,.6)}
      .order-notification-panel__head strong{display:block;color:#173c27;font-size:15px}.order-notification-panel__head small{display:block;margin-top:1px;color:#7b806f;font-size:11px}
      .order-notification-read-all{border:0;background:transparent;color:#173c27;font:inherit;font-size:11px;font-weight:700;cursor:pointer;padding:8px;border-radius:9px}.order-notification-read-all:hover{background:#f5edc7}
      .order-notification-list{max-height:min(430px,calc(100dvh - 185px));overflow:auto;overscroll-behavior:contain}
      .order-notification-item{display:grid;grid-template-columns:9px minmax(0,1fr) 14px;align-items:center;gap:11px;padding:13px 15px;color:#173c27;text-decoration:none;border-bottom:1px solid rgba(221,203,120,.38);transition:background .15s ease}
      .order-notification-item:hover,.order-notification-item:focus-visible{background:#f8f1cf;outline:none}.order-notification-item.is-unread{background:#fff8d9}
      .order-notification-item__dot{width:8px;height:8px;border-radius:50%;background:transparent}.order-notification-item.is-unread .order-notification-item__dot{background:#d0aa29;box-shadow:0 0 0 4px rgba(208,170,41,.13)}
      .order-notification-item__body{min-width:0}.order-notification-item__body strong{display:block;font-size:12.5px;margin-bottom:3px}.order-notification-item__body span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:#425548}.order-notification-item__body small{display:block;margin-top:3px;font-size:11px;color:#7a806f}.order-notification-item__chevron{font-size:21px;color:#809083}
      .order-notification-empty{padding:30px 18px;text-align:center;color:#7a806f;font-size:12px}
      .admin-order-notice{position:fixed;right:22px;top:96px;z-index:99999;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:12px;min-width:310px;max-width:430px;padding:14px 16px;border:1px solid #d9c56d;border-radius:16px;background:#fffdf0;color:#173c27;text-decoration:none;box-shadow:0 14px 34px rgba(23,60,39,.18);opacity:0;transform:translateY(-10px);transition:.22s ease;font-family:inherit}
      .admin-order-notice.show{opacity:1;transform:translateY(0)}.admin-order-notice__dot{width:10px;height:10px;border-radius:50%;background:#d7bd50;box-shadow:0 0 0 5px rgba(215,189,80,.18)}
      .admin-order-notice strong{display:block;font-size:14px;margin-bottom:3px}.admin-order-notice small{display:block;font-size:12px;color:#5e6d62}.admin-order-notice__arrow{font-size:12px;font-weight:700;white-space:nowrap}
      @keyframes orderBellRing{0%,100%{transform:rotate(0)}25%{transform:rotate(12deg)}50%{transform:rotate(-10deg)}75%{transform:rotate(7deg)}}
      @media(max-width:900px){.order-notification-center{margin-right:3px}.order-notification-bell{width:38px;height:38px}.order-notification-bell svg{width:19px;height:19px}.order-notification-panel{right:-44px;top:46px}}
      @media(max-width:700px){.admin-order-notice{left:14px;right:14px;top:78px;min-width:0;max-width:none}.order-notification-panel{position:fixed;left:14px;right:14px;top:72px;width:auto;max-height:calc(100dvh - 88px)}}
      @media(prefers-reduced-motion:reduce){.admin-order-notice,.order-notification-bell{transition:none}.order-notification-bell.has-unread svg{animation:none}}
    `;
    document.head.appendChild(style);

    poll();
    setInterval(poll, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
