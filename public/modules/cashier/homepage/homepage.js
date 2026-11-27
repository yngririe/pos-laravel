(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────
    const state = {
        order: [],
        orderType: 'dine-in',
        paymentMethod: 'cash',
        discountType: 'none',
    };

    // Dine in / Take out orders let each item carry its own type.
    const TYPE_LABELS = { 'dine-in': 'Dine in', 'take-out': 'Take out' };
    function isPerItemType(t) { return t === 'dine-in' || t === 'take-out'; }

    // ── DOM refs ───────────────────────────────────────────────────────────
    const menuGrid        = document.getElementById('menu-grid');
    const orderList       = document.getElementById('order-items-list');
    const orderEmpty      = document.getElementById('order-empty');
    const subtotalEl      = document.getElementById('subtotal-value');
    const deliveryFeeRow = document.getElementById('delivery-fee-row');
    const deliveryFeeEl  = document.getElementById('delivery-fee-value');
    const discountValEl   = document.getElementById('discount-value');
    const totalEl         = document.getElementById('total-value');
    const placeOrderBtn   = document.getElementById('place-order-btn');
    const amountInput     = document.getElementById('amount-input');
    const amountWrap      = document.getElementById('amount-wrap');
    const discountSelect  = document.getElementById('discount-select');
    const beeperInput     = document.getElementById('beeper-input');
    const beeperWrap      = document.getElementById('beeper-wrap');
    const beeperError     = document.getElementById('beeper-error');
    const beeperLabel     = document.getElementById('beeper-label');
    const pickupTimeWrap  = document.getElementById('pickup-time-wrap');
    const pickupTimeInput = document.getElementById('pickup-time-input');
    const notesInput      = document.getElementById('notes-input');
    const changeDisplay   = document.getElementById('change-display');
    const changeAmount    = document.getElementById('change-amount');
    const toastContainer  = document.getElementById('toast-container');

    // ── Home screens (Start → Dine in/Take out → Menu & ordering) ──────────
    const screenStart        = document.getElementById('screen-start');
    const screenType         = document.getElementById('screen-type');
    const screenOrder        = document.getElementById('screen-order');
    const startOrderBtn      = document.getElementById('start-order-btn');
    const pickDineInBtn      = document.getElementById('pick-dine-in');
    const pickTakeOutBtn     = document.getElementById('pick-take-out');
    const pickDeliveryBtn    = document.getElementById('pick-delivery');
    const pickPickupBtn      = document.getElementById('pick-pickup');
    const changeOrderTypeBtn = document.getElementById('change-order-type-btn');
    const orderTypeBadgeIcon = document.getElementById('order-type-badge-icon');
    const orderTypeBadgeLbl  = document.getElementById('order-type-badge-label');

    // ── Profile Dropdown & Logout ──────────────────────────────────────────
    const profileBtn      = document.getElementById('profile-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    const logoutBtn       = document.getElementById('logout-btn');

    if (profileBtn) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('open');
        });
    }
    document.addEventListener('click', () => {
        if (profileDropdown) profileDropdown.classList.remove('open');
    });
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = logoutBtn.dataset.logoutUrl;
        });
    }

    // ── Date/Time Clock ────────────────────────────────────────────────────
    function updateClock() {
        const now    = new Date();
        const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['January','February','March','April','May','June',
                        'July','August','September','October','November','December'];
        const dayName = days[now.getDay()];
        const month   = months[now.getMonth()];
        const date    = now.getDate();
        const year    = now.getFullYear();
        let h         = now.getHours();
        const ampm    = h >= 12 ? 'PM' : 'AM';
        h             = h % 12 || 12;
        const m       = String(now.getMinutes()).padStart(2, '0');
        const dayEl   = document.getElementById('current-day');
        const dateEl  = document.getElementById('current-date');
        if (dayEl)  dayEl.textContent  = dayName;
        if (dateEl) dateEl.textContent = `${month} ${date}, ${year} at ${h}:${m} ${ampm}`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ── Home screen navigation ──────────────────────────────────────────────
    // screen-start  -> tap "Start new order"      -> screen-type
    // screen-type   -> tap "Dine in" / "Take out"  -> screen-order (type set)
    // screen-order  -> order placed successfully   -> back to screen-start
    function showScreen(name) {
        screenStart.classList.toggle('screen--active', name === 'start');
        screenType.classList.toggle('screen--active',  name === 'type');
        screenOrder.classList.toggle('screen--active', name === 'order');
    }

    // ── Auto-incrementing beeper number ─────────────────────────────────────
    // Starts at 1, wraps back to 1 after 16. After each order is placed, the
    // NEXT suggested beeper is whatever was just used + 1 — whether that
    // number was auto-filled or typed in manually by the cashier. e.g.
    // beeper is at #5, cashier types #1 for the next order -> the order
    // after that suggests #2. Types #3 -> the one after suggests #4.
    // Persisted across reloads via localStorage.
    const BEEPER_MAX = 16;
    const BEEPER_STORAGE_KEY = 'twistroll_next_beeper';

    function getNextBeeper() {
        const v = parseInt(localStorage.getItem(BEEPER_STORAGE_KEY), 10);
        return (!isNaN(v) && v >= 1 && v <= BEEPER_MAX) ? v : 1;
    }

    function setNextBeeper(v) {
        const wrapped = v > BEEPER_MAX ? 1 : v;
        try { localStorage.setItem(BEEPER_STORAGE_KEY, String(wrapped)); } catch (e) { /* storage unavailable */ }
    }

    function updateOrderTypeBadge(type) {
        if (!orderTypeBadgeIcon || !orderTypeBadgeLbl) return;
        const labels = { 'dine-in': 'Dine in', 'take-out': 'Take out', 'delivery': 'Delivery', 'pick-up': 'Pick up' };
        orderTypeBadgeLbl.textContent = labels[type] || 'Dine in';
        orderTypeBadgeIcon.src = BASE_URL + (type === 'dine-in' ? 'assets/images/dine-in.png' : 'assets/images/take-out.png');
    }

    function syncBeeperForOrderType(type) {
        const delivery = type === 'delivery';
        const optionalBeeper = type === 'pick-up';

        beeperWrap.style.display = '';
        beeperError.classList.remove('visible');
        beeperWrap.classList.remove('beeper-error');

        // Pickup time only applies to Pick Up orders; notes apply to every order.
        if (pickupTimeWrap) pickupTimeWrap.style.display = optionalBeeper ? '' : 'none';
        if (pickupTimeInput) pickupTimeInput.value = '';

        if (delivery) {
            beeperLabel.textContent = 'Customer name';
            beeperInput.value = '';
            beeperInput.inputMode = 'text';
            beeperInput.placeholder = 'Enter customer name';
        } else if (optionalBeeper) {
            beeperLabel.textContent = 'Beeper #';
            beeperInput.value = '';
            beeperInput.inputMode = 'numeric';
            beeperInput.placeholder = 'Optional (1-16)';
        } else {
            beeperLabel.textContent = 'Beeper #';
            beeperInput.inputMode = 'numeric';
            beeperInput.placeholder = 'Enter number (1-16)';
            beeperInput.value = getNextBeeper();
        }
    }

    function chooseOrderType(type) {
        state.orderType = type;
        // Re-tag every item with the newly chosen type (or clear it for Delivery / Pick up),
        // then merge rows that became identical.
        state.order.forEach(o => { o.type = isPerItemType(type) ? type : null; });
        mergeDuplicateRows();
        updateOrderTypeBadge(type);
        syncBeeperForOrderType(type);
        renderOrder();
        showScreen('order');
    }

    if (startOrderBtn)  startOrderBtn.addEventListener('click', () => showScreen('type'));
    if (pickDineInBtn)  pickDineInBtn.addEventListener('click', () => chooseOrderType('dine-in'));
    if (pickTakeOutBtn) pickTakeOutBtn.addEventListener('click', () => chooseOrderType('take-out'));
    if (pickDeliveryBtn) pickDeliveryBtn.addEventListener('click', () => chooseOrderType('delivery'));
    if (pickPickupBtn) pickPickupBtn.addEventListener('click', () => chooseOrderType('pick-up'));
    if (changeOrderTypeBtn) changeOrderTypeBtn.addEventListener('click', () => showScreen('type'));

    // ── Discount Calculation ───────────────────────────────────────────────
    // Discounts apply once to the lowest-priced unit in the order.
    // Percentage discounts are rounded to a whole peso so totals never contain centavos.
    function getItemDiscount(price) {
        if (state.discountType === '20') return Math.round(price * 0.20);
        if (state.discountType === '10') return Math.round(price * 0.10);
        if (state.discountType === 'minus10') return Math.min(10, price);
        return 0;
    }

    function calcTotals() {
        let subtotal = 0;
        state.order.forEach(item => {
            subtotal += item.price * item.qty;
        });

        let totalDiscount = 0;

        if (state.discountType !== 'none' && state.order.length > 0) {
            const cheapest = state.order.reduce(
                (min, item) => item.price < min.price ? item : min,
                state.order[0]
            );

            totalDiscount = getItemDiscount(cheapest.price);
        }

        const deliveryFee = state.orderType === 'delivery' ? 30 : 0;
        const total = Math.round(subtotal - totalDiscount + deliveryFee);

        return {
            subtotal: Math.round(subtotal),
            totalDiscount,
            deliveryFee,
            total
        };
    }

    // ── Add to Order ───────────────────────────────────────────────────────
    menuGrid.addEventListener('click', e => {
        const card = e.target.closest('.menu-card');
        if (!card) return;

        const id    = parseInt(card.dataset.id);
        const name  = card.dataset.name;
        const price = parseInt(card.dataset.price);

        const itemType = isPerItemType(state.orderType) ? state.orderType : null;
        const existing = state.order.find(o => o.id === id && o.type === itemType);
        if (existing) {
            existing.qty++;
        } else {
            state.order.push({ id, name, price, qty: 1, type: itemType });
        }

        renderOrder();

        card.style.borderColor = 'var(--green-100)';
        card.style.transform   = 'scale(0.97)';
        setTimeout(() => {
            card.style.borderColor = '';
            card.style.transform   = '';
        }, 300);
    });

    function mergeDuplicateRows() {
        const merged = [];
        state.order.forEach(o => {
            const same = merged.find(m => m.id === o.id && m.type === o.type);
            if (same) same.qty += o.qty; else merged.push(o);
        });
        state.order = merged;
    }

    function cheapestRow() {
        return state.order.length
            ? state.order.reduce((min, o) => o.price < min.price ? o : min, state.order[0])
            : null;
    }

    // ── Render Order ───────────────────────────────────────────────────────
    function renderOrder() {
        orderList.querySelectorAll('.order-item').forEach(el => el.remove());

        if (state.order.length === 0) {
            orderEmpty.style.display = 'flex';
        } else {
            orderEmpty.style.display = 'none';
            state.order.forEach(item => orderList.appendChild(createOrderItemEl(item)));
        }

        updateTotals();
    }

    function createOrderItemEl(item) {
        const row = document.createElement('div');
        row.className  = 'order-item';
        row.dataset.id = item.id;

        const typeSelect = isPerItemType(state.orderType)
            ? `<select class="order-item__type" aria-label="Order type for ${escHtml(item.name)}">
                   <option value="dine-in"${item.type === 'dine-in' ? ' selected' : ''}>Dine in</option>
                   <option value="take-out"${item.type === 'take-out' ? ' selected' : ''}>Take out</option>
               </select>`
            : '';

        const cheapest  = cheapestRow();
        const isCheapest = cheapest === item;
        const disc       = (state.discountType !== 'none' && isCheapest) ? getItemDiscount(item.price) : 0;
        const effPrice   = item.price - disc;

        row.innerHTML = `
            <div style="flex:1;min-width:0;">
                <span class="order-item__name" title="${escHtml(item.name)}">${escHtml(item.name)}</span>
                <span class="order-item__price-sub">Php ${effPrice}</span>
                ${typeSelect}
            </div>
            <div class="order-item__qty">
                <button class="qty-btn" data-action="dec" aria-label="Decrease">−</button>
                <span class="qty-num">${item.qty}</span>
                <button class="qty-btn" data-action="inc" aria-label="Increase">+</button>
            </div>
            <button class="order-item__remove" aria-label="Remove item">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;

        const typeEl = row.querySelector('.order-item__type');
        if (typeEl) {
            typeEl.addEventListener('change', () => {
                // Qty above 1: move just one unit to the other type (splits the row).
                if (item.qty > 1) {
                    item.qty--;
                    state.order.splice(state.order.indexOf(item) + 1, 0, { ...item, qty: 1, type: typeEl.value });
                } else {
                    item.type = typeEl.value;
                }
                mergeDuplicateRows();
                renderOrder();
            });
        }
        row.querySelector('[data-action="dec"]').addEventListener('click', () => {
            item.qty--;
            if (item.qty <= 0) state.order = state.order.filter(x => x !== item);
            renderOrder();
        });
        row.querySelector('[data-action="inc"]').addEventListener('click', () => {
            item.qty++;
            renderOrder();
        });
        row.querySelector('.order-item__remove').addEventListener('click', () => {
            state.order = state.order.filter(x => x !== item);
            renderOrder();
        });

        return row;
    }

    // ── Update Totals ──────────────────────────────────────────────────────
    function updateTotals() {
        const { subtotal, totalDiscount, deliveryFee, total } = calcTotals();

        subtotalEl.textContent = `Php ${subtotal.toLocaleString()}`;

        discountValEl.textContent = totalDiscount > 0
            ? `−Php ${totalDiscount.toLocaleString()}`
            : 'Php 0';

        if (deliveryFeeRow) {
            deliveryFeeRow.style.display = deliveryFee > 0 ? 'flex' : 'none';
        }

        if (deliveryFeeEl) {
            deliveryFeeEl.textContent = `Php ${deliveryFee.toLocaleString()}`;
        }

        totalEl.textContent = `Php ${total.toLocaleString()}`;
        placeOrderBtn.textContent = `Place order – Php ${total.toLocaleString()}`;

        updatePlaceOrderState();
        updateChangeDisplay();
    }

    // ── Validate & enable/disable place order button ───────────────────────
    function updatePlaceOrderState() {
        const { total } = calcTotals();
        const hasItems  = state.order.length > 0;
        const beeper    = beeperInput.value.trim();
        const isCash    = state.paymentMethod === 'cash';
        const beeperRequired = state.orderType === 'dine-in' || state.orderType === 'take-out';
        const beeperOptional = state.orderType === 'pick-up';
        const deliveryNameRequired = state.orderType === 'delivery';

        let canPlace = hasItems;
        if (beeperRequired && (!beeper || parseInt(beeper) < 1 || parseInt(beeper) > BEEPER_MAX)) canPlace = false;
        if (beeperOptional && beeper && (parseInt(beeper) < 1 || parseInt(beeper) > BEEPER_MAX)) canPlace = false;
        if (deliveryNameRequired && beeper.length < 1) canPlace = false;
        if (isCash) {
            const amount = parseFloat(amountInput.value) || 0;
            if (amount <= 0 || amount < total) canPlace = false;
        }

        placeOrderBtn.disabled = !canPlace;
    }

    // ── Change Display (cash only) ─────────────────────────────────────────
    function updateChangeDisplay() {
        const { total } = calcTotals();
        const amount    = parseFloat(amountInput.value) || 0;
        const isCash    = state.paymentMethod === 'cash';

        if (isCash && amount > 0 && amount >= total && total > 0) {
            const change = amount - total;
            changeAmount.textContent    = `Php ${change.toFixed(2)}`;
            changeDisplay.style.display = 'flex';
        } else {
            changeDisplay.style.display = 'none';
        }
    }

    // ── Show/hide cash vs gcash inputs ─────────────────────────────────────
    function syncPaymentInputs() {
        const isCash = state.paymentMethod === 'cash';
        amountWrap.style.display = isCash ? '' : 'none';
        if (!isCash) {
            changeDisplay.style.display = 'none';
            amountInput.value = '';
        }
    }

    // ── Discount Selection ────────────────────────────────────────────────
    discountSelect.addEventListener('change', () => {
        state.discountType = discountSelect.value;
        renderOrder();
    });

    // ── Payment Method ─────────────────────────────────────────────────────
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('payment-btn--active'));
            btn.classList.add('payment-btn--active');
            state.paymentMethod = btn.dataset.method;
            syncPaymentInputs();
            updateTotals();
        });
    });

    // ── Beeper input ────────────────────────────────────────────────────────
    beeperInput.addEventListener('input', () => {
        // Hard-clamp: strip anything that would push the value past 16
        if (state.orderType !== 'delivery' && beeperInput.value !== '') {
            let num = parseInt(beeperInput.value, 10);
            if (!isNaN(num) && num > BEEPER_MAX) {
                beeperInput.value = String(BEEPER_MAX);
            }
        }

        const val = beeperInput.value.trim();
        const num = parseInt(val, 10);
        const required = state.orderType === 'dine-in' || state.orderType === 'take-out';
        if (state.orderType === 'delivery') {
            const missingName = val.length === 0;
            beeperWrap.classList.toggle('beeper-error', missingName);
            beeperError.classList.toggle('visible', missingName);
            beeperError.textContent = 'Customer name is required for delivery.';
            updatePlaceOrderState();
            return;
        }
        if ((required && !val) || (val && (num < 1 || num > BEEPER_MAX))) {
            beeperWrap.classList.add('beeper-error');
            beeperError.classList.add('visible');
            beeperError.textContent = (!val && required)
                ? 'Beeper number is required.'
                : `Beeper number must be 1-${BEEPER_MAX}.`;
        } else {
            beeperWrap.classList.remove('beeper-error');
            beeperError.classList.remove('visible');
        }
        updatePlaceOrderState();
    });

    // ── Cash amount input ──────────────────────────────────────────────────
    amountInput.addEventListener('input', () => {
        updateChangeDisplay();
        updatePlaceOrderState();
    });

    // ══════════════════════════════════════════════════════════════════════
    //  BLUETOOTH PRINTER
    // ══════════════════════════════════════════════════════════════════════

    // Inject the printer status button into the navbar right area
    function injectPrinterStatusBtn() {
        const navRight = document.querySelector('.navbar__right');
        if (!navRight || document.getElementById('printer-status-btn')) return;

        const btn = document.createElement('button');
        btn.id        = 'printer-status-btn';
        btn.className = 'printer-status-btn printer-status-btn--disconnected';
        btn.title     = 'Connect receipt printer';
        btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>
                <rect x="9" y="11" width="14" height="10" rx="1"/>
                <line x1="12" y1="17" x2="12" y2="17"/>
                <line x1="16" y1="17" x2="16" y2="17"/>
            </svg>
            <span id="printer-status-label">Printer</span>
        `;
        // Insert before the profile menu
        const profileMenu = navRight.querySelector('.profile-menu');
        navRight.insertBefore(btn, profileMenu);

        btn.addEventListener('click', handlePrinterBtnClick);
        btn.addEventListener('contextmenu', handlePrinterBtnRightClick);
        btn.title += ' (right-click to switch printers)';
    }

    function updatePrinterBtn(connected) {
        const btn   = document.getElementById('printer-status-btn');
        const label = document.getElementById('printer-status-label');
        if (!btn) return;
        if (connected) {
            btn.className = 'printer-status-btn printer-status-btn--connected';
            btn.title     = 'Printer connected — click to disconnect';
            if (label) label.textContent = 'Connected';
        } else {
            btn.className = 'printer-status-btn printer-status-btn--disconnected';
            btn.title     = 'Connect receipt printer';
            if (label) label.textContent = 'Printer';
        }
    }

       async function reconnectRememberedPrinter() {
            if (!window.BluetoothPrinter) {
                console.warn('[Printer] BluetoothPrinter is not available.');
                updatePrinterBtn(false);
                return;
            }

            if (BluetoothPrinter.isConnected()) {
                console.log('[Printer] Persistent printer already connected.');
                updatePrinterBtn(true);
                return;
            }

            console.log('[Printer] Printer is not connected. Use Connect Printer.');
            updatePrinterBtn(false);
        }

    async function handlePrinterBtnRightClick(e) {
        e.preventDefault();
        if (typeof BluetoothPrinter === 'undefined') return;

        if (BluetoothPrinter.isConnected()) {
            BluetoothPrinter.disconnect();
            updatePrinterBtn(false);
        }
        BluetoothPrinter.forgetDevice();
        showToast('Forgot saved printer — pick a new one.', 'success');

        const btn = document.getElementById('printer-status-btn');
        if (btn) btn.style.opacity = '0.5';
        try {
            await BluetoothPrinter.connect();
            updatePrinterBtn(true);
            showToast('Printer connected!', 'success');
        } catch (err) {
            showToast('Printer: ' + err.message, 'error');
            updatePrinterBtn(false);
        } finally {
            if (btn) btn.style.opacity = '';
        }
    }

    async function handlePrinterBtnClick() {
        if (typeof BluetoothPrinter === 'undefined') {
            showToast('Bluetooth printer script not loaded.', 'error');
            return;
        }
        if (BluetoothPrinter.isConnected()) {
            BluetoothPrinter.disconnect();
            updatePrinterBtn(false);
            showToast('Printer disconnected.', 'success');
            return;
        }
        const btn = document.getElementById('printer-status-btn');
        if (btn) btn.style.opacity = '0.5';
        try {
            await BluetoothPrinter.connect();
            updatePrinterBtn(true);
            showToast('Printer connected!', 'success');
        } catch (err) {
            showToast('Printer: ' + err.message, 'error');
            updatePrinterBtn(false);
        } finally {
            if (btn) btn.style.opacity = '';
        }
    }

    // Fetch receipt settings from the server
    async function fetchReceiptSettings() {
        try {
            const res  = await fetch(BASE_URL + 'receipt-settings');
            const data = await res.json();
            return data.settings || {};
        } catch {
            return {};
        }
    }

    // Auto-print after a successful order
    async function autoPrint(orderPayload) {
        if (typeof BluetoothPrinter === 'undefined') {
            console.warn(
                '[Printer] BluetoothPrinter is not loaded.'
            );

            return;
        }

        const rememberedPrinter =
            localStorage.getItem('twistroll_printer_id');

        // User has never connected a printer.
        // Do not automatically open the Bluetooth picker.
        if (!rememberedPrinter) {
            console.log(
                '[Printer] No remembered printer. Auto-print skipped.'
            );

            return;
        }

        try {
            // Page navigation destroys the old GATT connection,
            // so restore it before printing.
            if (!BluetoothPrinter.isConnected()) {
                console.log(
                    '[Printer] Reconnecting before print...'
                );

                await BluetoothPrinter.reconnect();
            }

            const settings =
                await fetchReceiptSettings();

            await BluetoothPrinter.print(
                orderPayload,
                settings
            );

            updatePrinterBtn(true);

            showToast(
                'Receipt printed!',
                'success'
            );

        } catch (err) {
            console.error(
                '[Printer] Print error:',
                err
            );

            updatePrinterBtn(false);

            showToast(
                'Printer disconnected. Click Printer to reconnect.',
                'error'
            );
        }
    }

    // ── Place Order ────────────────────────────────────────────────────────
    placeOrderBtn.addEventListener('click', () => {
        if (placeOrderBtn.disabled) return;

        const { total, totalDiscount, subtotal } = calcTotals();
        const beeper   = beeperInput.value.trim();
        const isCash   = state.paymentMethod === 'cash';
        const amount   = parseFloat(amountInput.value) || 0;

        const beeperRequired = state.orderType === 'dine-in' || state.orderType === 'take-out';
        const beeperOptional = state.orderType === 'pick-up';
        const delivery = state.orderType === 'delivery';
        if (delivery && !beeper) {
            beeperWrap.classList.add('beeper-error');
            beeperError.classList.add('visible');
            beeperError.textContent = 'Customer name is required for delivery.';
            beeperInput.focus();
            return;
        }
        if ((beeperRequired && (!beeper || parseInt(beeper) < 1 || parseInt(beeper) > BEEPER_MAX)) ||
            (beeperOptional && beeper && (parseInt(beeper) < 1 || parseInt(beeper) > BEEPER_MAX))) {
            beeperWrap.classList.add('beeper-error');
            beeperError.classList.add('visible');
            beeperError.textContent = beeperRequired && !beeper
                ? 'Beeper number is required.'
                : `Beeper number must be 1-${BEEPER_MAX}.`;
            beeperInput.focus();
            return;
        }
        if (isCash && (amount <= 0 || amount < total)) {
            showToast('Amount paid must be equal to or greater than the total.', 'error');
            amountInput.focus();
            return;
        }

        const change = isCash ? (amount - total) : 0;

        // Same rule as the server: if every item ended up the same type, the order takes that type.
        let effectiveType = state.orderType;
        if (isPerItemType(effectiveType)) {
            const types = Array.from(new Set(state.order.map(o => o.type)));
            if (types.length === 1) effectiveType = types[0];
        }

        const payload = {
            beeper_number:   state.orderType === 'delivery' || !beeper ? null : parseInt(beeper, 10),
            customer_name:   state.orderType === 'delivery' ? beeper : null,
            pickup_time:     state.orderType === 'pick-up' && pickupTimeInput.value ? pickupTimeInput.value : null,
            notes:           notesInput.value.trim() || null,
            order_type:      effectiveType,
            payment_method:  state.paymentMethod,
            amount_paid:     isCash ? amount : total,
            discount_type:  state.discountType,
            subtotal,
            discount:        totalDiscount,
            total,
            change_amount:   change,
            items:           state.order.map(o => ({
                id:    o.id,
                name:  o.name,
                price: o.price,
                qty:   o.qty,
                order_type: o.type,
            }))
        };

        placeOrderBtn.disabled    = true;
        placeOrderBtn.textContent = 'Placing order...';

        fetch(BASE_URL + 'orders', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success && data.message === 'beeper_in_use') {
                showToast('Beeper number is currently in use.', 'error');
                placeOrderBtn.disabled = false;
                updateTotals();
                return;
            }
            if (data.success) {
                // ── Back to the start screen immediately ─────────────────
                showScreen('start');

                // ── Advance the auto beeper counter ──────────────────────
                // Any successfully assigned beeper advances the automatic suggestion.
                // This includes an optional beeper manually assigned to Pick Up.
                if (state.orderType !== 'delivery' && beeper) {
                    const usedBeeper = parseInt(beeper, 10);
                    if (!isNaN(usedBeeper)) setNextBeeper(usedBeeper + 1);
                }

                showToast('Order has been placed!', 'success');

                // ── AUTO PRINT ──────────────────────────────────────────
                autoPrint(payload);
                // ───────────────────────────────────────────────────────

                // Reset state
                state.order           = [];
                state.discountType = 'none';
                discountSelect.value = 'none';
                amountInput.value      = '';
                beeperInput.value      = '';
                if (pickupTimeInput) pickupTimeInput.value = '';
                if (notesInput) notesInput.value = '';
                beeperWrap.classList.remove('beeper-error');
                beeperError.classList.remove('visible');
                changeDisplay.style.display = 'none';
                renderOrder();
            } else {
                showToast('Failed to place order: ' + (data.message || 'Unknown error'), 'error');
                placeOrderBtn.disabled = false;
                updateTotals();
            }
        })
        .catch(() => {
            showToast('Network error. Please try again.', 'error');
            placeOrderBtn.disabled = false;
            updateTotals();
        });
    });

    // ── Toast ──────────────────────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast       = document.createElement('div');
        toast.className   = `toast toast--${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity    = '0';
            toast.style.transform  = 'translateY(10px)';
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // ── Escape HTML ────────────────────────────────────────────────────────
    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                  .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ── Init ───────────────────────────────────────────────────────────────
    syncPaymentInputs();
    renderOrder();
    injectPrinterStatusBtn();

    // Reconnect the previously selected printer whenever Home loads.
    reconnectRememberedPrinter();

})();