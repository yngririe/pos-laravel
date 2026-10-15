(function () {
    'use strict';

    // ── STATE ──────────────────────────────────────────────
    let currentFilter = 'all';
    let currentSearch  = '';
    let currentPage    = 1;
    let currentView    = 'grid';
    const ORDER_VIEW_KEY = 'twistroll_orders_view';

    // ── HELPERS ────────────────────────────────────────────
    function getAllCards() {
        return Array.from(document.querySelectorAll('#orders-grid .order-card'));
    }

    function getFilteredCards() {
        const val = currentSearch;
        return getAllCards().filter(card => {
            const idEl = card.querySelector('.order-id');
            const beeper = (idEl?.dataset.beeper || '').trim();
            const customer = (idEl?.dataset.customer || '').trim().toLowerCase();
            const items  = Array.from(card.querySelectorAll('.order-row span:first-child'))
                .map(el => el.textContent.toLowerCase().replace(/^\d+x\s*/i, '')).join(' ');
            return (currentFilter === 'all' || card.dataset.type === currentFilter)
                && (val === '' || beeper === val || customer.includes(val) || items.includes(val));
        });
    }

    // Reads the *actual* rendered column count from the grid so pagination
    // stays correct across laptop / tablet landscape / tablet portrait —
    // the grid uses auto-fill, so column count isn't fixed.
    function getColumnsCount() {
        const grid = document.getElementById('orders-grid');
        if (!grid) return 1;
        const cols = getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length;
        return cols || 1;
    }

    // ── PAGINATION ─────────────────────────────────────────
    function renderPagination(totalItems, itemsPerPage) {
        const pagWrap = document.getElementById('orders-pagination');
        if (!pagWrap) return;

        const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        if (totalItems === 0 || totalPages <= 1) {
            pagWrap.innerHTML = '';
            pagWrap.style.display = 'none';
            return;
        }
        pagWrap.style.display = 'flex';

        let html = `<button class="page-btn page-nav" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>&#8592;</button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        html += `<button class="page-btn page-nav" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>&#8594;</button>`;
        pagWrap.innerHTML = html;

        pagWrap.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.disabled) return;
                const p = parseInt(this.dataset.page);
                if (!p || p < 1 || p > totalPages) return;
                currentPage = p;
                applyPagination();
                document.querySelector('.orders-page').scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    function applyPagination() {
        const all      = getAllCards();
        const filtered = getFilteredCards();
        const cols     = getColumnsCount();
        const itemsPerPage = currentView === 'list' ? 8 : cols * 2; // compact list shows more orders

        all.forEach(c => { c.style.display = 'none'; });

        if (filtered.length === 0) {
            showEmpty(true);
            renderPagination(0, itemsPerPage);
            return;
        }
        showEmpty(false);

        const totalPages = Math.max(1, Math.ceil(filtered.length / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * itemsPerPage;
        // Let CSS choose the correct layout: flex for Box cards, grid for List rows.
        // Setting inline display:flex here was overriding .list-view .order-card { display:grid }
        // and caused the oversized/awkward List View.
        filtered.slice(start, start + itemsPerPage).forEach(c => { c.style.removeProperty('display'); });

        renderPagination(filtered.length, itemsPerPage);
    }
    window.applyPagination = applyPagination;

    // Recompute on resize/rotation — column count can change
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(applyPagination, 150);
    });

    // ── FILTER ─────────────────────────────────────────────
    function filterOrders(type, el) {
        currentFilter = type;
        currentPage = 1;
        document.querySelectorAll('.filter').forEach(btn => btn.classList.remove('active'));
        if (el) el.classList.add('active');
        applyPagination();
    }
    window.filterOrders = filterOrders;

    // ── BOX / LIST VIEW ────────────────────────────────────
    const ordersGrid = document.getElementById('orders-grid');
    const viewButtons = Array.from(document.querySelectorAll('.view-toggle-btn'));

    function setOrderView(view, persist = true) {
        currentView = view === 'list' ? 'list' : 'grid';
        if (ordersGrid) ordersGrid.classList.toggle('list-view', currentView === 'list');
        viewButtons.forEach(btn => {
            const selected = btn.dataset.view === currentView;
            btn.classList.toggle('active', selected);
            btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        if (persist) {
            try { localStorage.setItem(ORDER_VIEW_KEY, currentView); } catch (e) { /* storage unavailable */ }
        }
        currentPage = 1;
        applyPagination();
    }

    viewButtons.forEach(btn => btn.addEventListener('click', () => setOrderView(btn.dataset.view)));

    try {
        const savedView = localStorage.getItem(ORDER_VIEW_KEY);
        if (savedView === 'list') currentView = 'list';
    } catch (e) { /* storage unavailable */ }
    if (ordersGrid) ordersGrid.classList.toggle('list-view', currentView === 'list');
    viewButtons.forEach(btn => {
        const selected = btn.dataset.view === currentView;
        btn.classList.toggle('active', selected);
        btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });

    // ── MARK AS SERVED ─────────────────────────────────────
    function markServed(btn) {
        const card    = btn.closest('.order-card');
        const orderId = btn.dataset.id;
        btn.disabled = true; btn.textContent = 'Saving...';
        fetch(BASE_URL + 'orders/serve', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0'; card.style.transform = 'scale(0.95)';
                setTimeout(() => { card.remove(); applyPagination(); }, 300);
            } else {
                btn.disabled = false; btn.textContent = 'Mark as served';
                alert('Failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(() => { btn.disabled = false; btn.textContent = 'Mark as served'; alert('Network error.'); });
    }
    window.markServed = markServed;

    function showEmpty(show) {
        let empty = document.getElementById('empty');
        if (!empty && show) {
            empty = document.createElement('div');
            empty.id = 'empty'; empty.className = 'orders-empty';
            empty.textContent = 'No pending orders yet.';
            document.getElementById('orders-grid').appendChild(empty);
        }
        if (empty) empty.style.display = show ? 'block' : 'none';
    }

    // ── SEARCH ─────────────────────────────────────────────
    const searchInput = document.getElementById('orderSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentSearch = this.value.trim().toLowerCase();
            currentPage = 1;
            applyPagination();
        });
    }

    // ── PROFILE DROPDOWN ───────────────────────────────────
    const profileBtn = document.getElementById('profile-btn');
    const dropdown   = document.getElementById('profile-dropdown');
    const logoutBtn  = document.getElementById('logout-btn');
    if (profileBtn && dropdown) {
        profileBtn.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
        document.addEventListener('click', () => dropdown.classList.remove('open'));
    }
    if (logoutBtn) logoutBtn.addEventListener('click', () => { window.location.href = logoutBtn.dataset.logoutUrl; });

    // ── ORDER MENU (⋮) ─────────────────────────────────────
    document.querySelectorAll('.order-menu-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.order-menu').forEach(m => { if (m !== this.nextElementSibling) m.classList.remove('open'); });
            this.nextElementSibling.classList.toggle('open');
        });
    });
    document.addEventListener('click', () => document.querySelectorAll('.order-menu').forEach(m => m.classList.remove('open')));

    // ══════════════════════════════════════════════════════
    //  EDIT MODAL STATE
    // ══════════════════════════════════════════════════════
    const epm = {
        orderId:         null,
        items:           [],
        orderType:       'dine-in',
        paymentMethod:   'cash',
        discountType: 'none',
    };
    let originalTotal   = 0;

    // ── DOM ────────────────────────────────────────────────
    const editModal       = document.getElementById('editModal');
    const epmClose        = document.getElementById('editModalClose');
    const epmTypeWrap     = document.getElementById('epm-type-wrap');
    const epmTypeInput    = document.getElementById('epm-type');
    const epmBeeper       = document.getElementById('epm-beeper');
    const epmBeeperWrap   = document.getElementById('epm-beeper-wrap');
    const epmCustomerWrap = document.getElementById('epm-customer-wrap');
    const epmCustomerName = document.getElementById('epm-customer-name');

    const BEEPER_MAX = 16;
    const BEEPER_STORAGE_KEY = 'twistroll_next_beeper';

    function setNextBeeperAfterEdit(value) {
        const n = parseInt(value, 10);
        if (Number.isNaN(n)) return;
        const next = n >= BEEPER_MAX ? 1 : n + 1;
        try { localStorage.setItem(BEEPER_STORAGE_KEY, String(next)); } catch (e) { /* storage unavailable */ }
    }

    function syncEditBeeperUI() {
        if (!epmBeeperWrap || !epmBeeper) return;
        const delivery = epm.orderType === 'delivery';
        const pickup = epm.orderType === 'pick-up';
        epmBeeperWrap.style.display = delivery ? 'none' : '';
        epmCustomerWrap.style.display = delivery ? '' : 'none';
        epmBeeper.disabled = delivery;
        epmCustomerName.disabled = !delivery;
        if (delivery) {
            epmBeeper.value = '';
            epmBeeper.placeholder = '';
        } else if (pickup) {
            epmBeeper.placeholder = 'Optional (1-16)';
        } else {
            epmBeeper.placeholder = 'Required (1-16)';
        }
    }
    const epmItemsList    = document.getElementById('epm-items-list');
    const epmAddBtn       = document.getElementById('epm-add-btn');
    const epmMenuPicker   = document.getElementById('epm-menu-picker');
    const epmPickerGrid   = document.getElementById('epm-picker-grid');
    const epmDiscSelect   = document.getElementById('epm-discount-select');
    const epmDiscVal      = document.getElementById('epm-discount-val');
    const epmSubtotalVal  = document.getElementById('epm-subtotal-val');
    const epmTotalVal     = document.getElementById('epm-total-val');
    const epmPaymentWrap  = document.getElementById('epm-payment-wrap');
    const epmPaymentInput = document.getElementById('epm-payment');
    const epmAmountWrap   = document.getElementById('epm-amount-wrap');
    const epmAmountInput  = document.getElementById('epm-amount-input');
    const epmTotalBtn     = document.getElementById('epm-total-btn');
    const epmSaveBtn      = document.getElementById('epm-save-btn');
    const epmOrderId      = document.getElementById('epm-order-id');

    // ── HELPERS ────────────────────────────────────────────
    function getItemDiscount(price) {
        if (epm.discountType === '20') return Math.round(price * 0.20);
        if (epm.discountType === '10') return Math.round(price * 0.10);
        if (epm.discountType === 'minus10') return Math.min(10, price);
        return 0;
    }

    function calcEpmTotals() {
        let subtotal = 0;
        epm.items.forEach(item => { subtotal += item.price * item.qty; });
        let disc = 0;
        if (epm.discountType !== 'none' && epm.items.length > 0) {
            const cheapest = epm.items.reduce((m, o) => o.price < m.price ? o : m, epm.items[0]);
            disc = getItemDiscount(cheapest.price);
        }
        return { subtotal: Math.round(subtotal), disc, total: Math.round(subtotal - disc) };
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ── RENDER ITEMS ───────────────────────────────────────
    function renderEpmItems() {
        epmItemsList.innerHTML = '';
        if (epm.items.length === 0) {
            epmItemsList.innerHTML = `<div class="order-empty" style="display:flex;">
                <svg class="order-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span class="order-empty__text">No items yet</span></div>`;
        } else {
            const cheapest = epm.items.reduce((m, o) => o.price < m.price ? o : m, epm.items[0]);
            epm.items.forEach(item => {
                const disc     = (epm.discountType !== 'none' && item.id === cheapest.id) ? getItemDiscount(item.price) : 0;
                const effPrice = item.price - disc;
                const row      = document.createElement('div');
                row.className  = 'order-item';
                row.dataset.id = item.id;
                row.innerHTML  = `
                    <div style="flex:1;min-width:0;">
                        <span class="order-item__name">${escHtml(item.name)}</span>
                        <span class="order-item__price-sub">Php ${effPrice}</span>
                    </div>
                    <div class="order-item__qty">
                        <button class="qty-btn" data-action="dec">−</button>
                        <span class="qty-num">${item.qty}</span>
                        <button class="qty-btn" data-action="inc">+</button>
                    </div>
                    <button class="order-item__remove">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>`;
                row.querySelector('[data-action="dec"]').addEventListener('click', () => {
                    const o = epm.items.find(x => x.id === item.id); if (!o) return;
                    o.qty--;
                    if (o.qty <= 0) epm.items = epm.items.filter(x => x.id !== item.id);
                    renderEpmItems(); updateEpmTotals();
                });
                row.querySelector('[data-action="inc"]').addEventListener('click', () => {
                    const o = epm.items.find(x => x.id === item.id);
                    if (o) { o.qty++; renderEpmItems(); updateEpmTotals(); }
                });
                row.querySelector('.order-item__remove').addEventListener('click', () => {
                    epm.items = epm.items.filter(x => x.id !== item.id);
                    renderEpmItems(); updateEpmTotals();
                });
                epmItemsList.appendChild(row);
            });
        }
        updateEpmTotals();
    }

    // ── TOTALS ─────────────────────────────────────────────
    function updateEpmTotals() {
        const { subtotal, disc, total } = calcEpmTotals();
        epmSubtotalVal.textContent = `Php ${subtotal.toLocaleString()}`;
        epmDiscVal.textContent     = disc > 0 ? `−Php ${disc.toLocaleString()}` : 'Php 0';
        epmTotalVal.textContent    = `Php ${total.toLocaleString()}`;
        epmTotalBtn.textContent    = `Place order – Php ${total.toLocaleString()}`;
        syncEpmPaymentUI();
    }

    function syncEpmPaymentUI() {
        const isGcash = epm.paymentMethod === 'gcash';
        epmAmountWrap.style.display = isGcash ? 'none' : '';
    }

    // ── MENU PICKER ────────────────────────────────────────
    function renderPickerGrid() {
        epmPickerGrid.innerHTML = '';
        const items = (typeof MENU_ITEMS !== 'undefined') ? MENU_ITEMS : [];
        items.forEach(item => {
            const card = document.createElement('button');
            card.type = 'button'; card.className = 'epm-picker-card';
            card.innerHTML = `<img src="${BASE_URL}${escHtml(item.image)}" alt="${escHtml(item.name)}" onerror="this.style.display='none'">
                <span class="epm-picker-name">${escHtml(item.name)}</span>
                <span class="epm-picker-price">Php ${item.price}</span>`;
            card.addEventListener('click', () => {
                const ex = epm.items.find(o => o.id === item.id);
                if (ex) { ex.qty++; } else { epm.items.push({ id: item.id, name: item.name, price: item.price, qty: 1 }); }
                card.style.transform = 'scale(0.94)';
                setTimeout(() => { card.style.transform = ''; }, 200);
                renderEpmItems(); updateEpmTotals();
            });
            epmPickerGrid.appendChild(card);
        });
    }

    epmAddBtn.addEventListener('click', () => {
        const open = epmMenuPicker.classList.toggle('open');
        epmAddBtn.textContent = open ? 'close ✕' : 'add order +';
        if (open) renderPickerGrid();
    });

    // ── TYPE BUTTONS ───────────────────────────────────────
    epmTypeWrap.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            epmTypeWrap.querySelectorAll('.type-btn').forEach(b => b.classList.remove('type-btn--active'));
            this.classList.add('type-btn--active');
            epm.orderType = this.dataset.type; epmTypeInput.value = this.dataset.type;
            syncEditBeeperUI();
        });
    });

    // ── PAYMENT BUTTONS ────────────────────────────────────
    epmPaymentWrap.querySelectorAll('.payment-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            epmPaymentWrap.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('payment-btn--active'));
            this.classList.add('payment-btn--active');
            epm.paymentMethod = this.dataset.method; epmPaymentInput.value = this.dataset.method;
            syncEpmPaymentUI();
        });
    });

    // ── DISCOUNT SELECTION ─────────────────────────────────
    epmDiscSelect.addEventListener('change', () => {
        epm.discountType = epmDiscSelect.value; renderEpmItems(); updateEpmTotals();
    });

    function inferDiscountType(savedDiscount) {
        if (!savedDiscount || epm.items.length === 0) return 'none';
        const lowest = epm.items.reduce((m, o) => o.price < m.price ? o : m, epm.items[0]).price;
        if (savedDiscount === Math.round(lowest * 0.20)) return '20';
        if (savedDiscount === Math.round(lowest * 0.10)) return '10';
        if (savedDiscount === Math.min(10, lowest)) return 'minus10';
        return 'none';
    }

    // ── OPEN EDIT ──────────────────────────────────────────
    document.querySelectorAll('.edit-order-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            epmMenuPicker.classList.remove('open'); epmAddBtn.textContent = 'add order +';

            epm.orderId           = this.dataset.id;
            epm.orderType         = this.dataset.type;
            epm.paymentMethod     = this.dataset.payment;
            epm.discountType      = inferDiscountType(parseFloat(this.dataset.discount) || 0);
            originalTotal         = parseFloat(this.dataset.total) || 0;

            try { epm.items = JSON.parse(this.dataset.items) || []; } catch(e) { epm.items = []; }

            epmOrderId.value      = epm.orderId;
            epmTypeInput.value    = epm.orderType;
            epmPaymentInput.value = epm.paymentMethod;
            epmBeeper.value       = this.dataset.beeper || '';
            epmCustomerName.value = this.dataset.customer || '';
            epmAmountInput.value  = '';
            syncEditBeeperUI();

            epmTypeWrap.querySelectorAll('.type-btn').forEach(b =>
                b.classList.toggle('type-btn--active', b.dataset.type === epm.orderType));
            epmPaymentWrap.querySelectorAll('.payment-btn').forEach(b =>
                b.classList.toggle('payment-btn--active', b.dataset.method === epm.paymentMethod));
            epmDiscSelect.value = epm.discountType;

            renderEpmItems();
            syncEpmPaymentUI();
            editModal.classList.add('show');
        });
    });

    // ── CLOSE EDIT ─────────────────────────────────────────
    function closeEditModal() {
        editModal.classList.remove('show');
        epmMenuPicker.classList.remove('open');
        epmAddBtn.textContent = 'add order +';
    }
    window.closeEditModal = closeEditModal;
    epmClose.addEventListener('click', closeEditModal);
    editModal.addEventListener('click', function (e) { if (e.target === this) closeEditModal(); });

    // ── SUBMIT SAVE ────────────────────────────────────────
    function submitEditOrder({ subtotal, disc, total, beeper, customerName }) {
        const amountPaid = epm.paymentMethod === 'cash'
            ? (parseFloat(epmAmountInput.value) || total)
            : total;

        const payload = {
            order_id:            parseInt(epm.orderId),
            beeper_number:       beeper,
            customer_name:       epm.orderType === 'delivery' ? customerName : null,
            order_type:          epm.orderType,
            payment_method:      epm.paymentMethod,
            amount_paid:         amountPaid,
            subtotal,
            discount:            disc,
            discount_type:       epm.discountType,
            total,
            items: epm.items.map(o => ({ id: o.id, name: o.name, price: o.price, qty: o.qty })),
        };

        epmSaveBtn.disabled = true; epmSaveBtn.textContent = 'Saving...';

        fetch(BASE_URL + 'orders/update', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // A beeper assigned/changed on Pick Up also becomes the next sequence source.
                // Dine In / Take Out edits behave the same; Delivery never advances it.
                if (epm.orderType !== 'delivery' && beeper !== null) setNextBeeperAfterEdit(beeper);
                location.reload();
            }
            else {
                alert('Failed: ' + (data.message || 'Unknown error'));
                epmSaveBtn.disabled = false; epmSaveBtn.textContent = 'Save Changes';
            }
        })
        .catch(() => {
            alert('Network error.');
            epmSaveBtn.disabled = false; epmSaveBtn.textContent = 'Save Changes';
        });
    }

    // ── SAVE BUTTON ────────────────────────────────────────
    epmSaveBtn.addEventListener('click', function () {
        const { subtotal, disc, total } = calcEpmTotals();
        const rawBeeper = epmBeeper.value.trim();
        const beeper = rawBeeper ? parseInt(rawBeeper, 10) : null;
        const beeperRequired = epm.orderType === 'dine-in' || epm.orderType === 'take-out';
        const customerName = epmCustomerName.value.trim();
        if (epm.orderType === 'delivery' && !customerName) {
            epmCustomerName.focus(); epmCustomerName.style.borderColor = '#d9534f';
            setTimeout(() => { epmCustomerName.style.borderColor = ''; }, 1500); return;
        }
        if ((beeperRequired && (!beeper || beeper < 1 || beeper > 16)) ||
            (epm.orderType === 'pick-up' && beeper !== null && (beeper < 1 || beeper > 16))) {
            epmBeeper.focus(); epmBeeper.style.borderColor = '#d9534f';
            setTimeout(() => { epmBeeper.style.borderColor = ''; }, 1500); return;
        }
        if (epm.items.length === 0) { alert('Please add at least one item.'); return; }
        submitEditOrder({ subtotal, disc, total, beeper, customerName });
    });

    // ══════════════════════════════════════════════════════
    //  VOID MODAL
    // ══════════════════════════════════════════════════════
    const voidModal      = document.getElementById('voidModal');
    const confirmVoidBtn = document.getElementById('confirmVoidBtn');
    const voidModalMsg   = document.getElementById('voidModalMsg');
    let voidOrderId      = null;

    function closeVoidModal() { voidModal.classList.remove('show'); }
    window.closeVoidModal = closeVoidModal;
    voidModal.addEventListener('click', function (e) { if (e.target === this) closeVoidModal(); });

    document.querySelectorAll('.void-order-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            voidOrderId = this.dataset.id;
            const label = this.dataset.label;
            const total = parseFloat(this.dataset.total) || 0;
            voidModalMsg.innerHTML = `You're about to void <strong>${label}</strong> (₱${total.toLocaleString()}).<br><br>
                It will be marked as <strong style="color:#D8C36F;">Voided</strong> and appear in Statistics under Voids. This cannot be undone.`;
            voidModal.classList.add('show');
        });
    });

    if (confirmVoidBtn) {
        confirmVoidBtn.addEventListener('click', function () {
            confirmVoidBtn.disabled = true; confirmVoidBtn.textContent = 'Voiding...';
            fetch(BASE_URL + 'orders/void', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: voidOrderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`.order-card[data-id="${voidOrderId}"]`);
                    closeVoidModal();
                    if (card) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0'; card.style.transform = 'scale(0.95)';
                        setTimeout(() => { card.remove(); applyPagination(); }, 300);
                    }
                } else {
                    alert('Failed to void: ' + (data.message || 'Unknown error'));
                    confirmVoidBtn.disabled = false; confirmVoidBtn.textContent = 'Yes, Void it';
                }
            })
            .catch(() => { alert('Network error.'); confirmVoidBtn.disabled = false; confirmVoidBtn.textContent = 'Yes, Void it'; });
        });
    }

    // ── CLOCK ──────────────────────────────────────────────
    function updateClock() {
        const now    = new Date();
        const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        let h = now.getHours(); const ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
        const m = String(now.getMinutes()).padStart(2, '0');
        const dayEl = document.getElementById('current-day'); const dateEl = document.getElementById('current-date');
        if (dayEl)  dayEl.textContent  = days[now.getDay()];
        if (dateEl) dateEl.textContent = `${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()} at ${h}:${m} ${ampm}`;
    }
    updateClock(); setInterval(updateClock, 1000);

    // ── INITIAL PAGINATION ─────────────────────────────────
    applyPagination();

})();