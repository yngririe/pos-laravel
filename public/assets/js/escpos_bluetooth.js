/**
 * escpos_bluetooth.js
 * Handles Web Bluetooth connection to a generic 58mm ESC/POS thermal printer
 * and auto-prints a receipt after a successful order.
 */

const BluetoothPrinter = (function () {
    'use strict';

    // Common ESC/POS Bluetooth service UUIDs
    const CANDIDATE_SERVICES = [
        '000018f0-0000-1000-8000-00805f9b34fb',
        '0000ff00-0000-1000-8000-00805f9b34fb',
        '0000ffe0-0000-1000-8000-00805f9b34fb',
        '00001101-0000-1000-8000-00805f9b34fb',
        'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
        '49535343-fe7d-4ae5-8fa9-9fafd205e455',
    ];

    const CANDIDATE_CHARS = [
        '00002af1-0000-1000-8000-00805f9b34fb',
        '0000ff02-0000-1000-8000-00805f9b34fb',
        '0000ff01-0000-1000-8000-00805f9b34fb',
        '0000ffe1-0000-1000-8000-00805f9b34fb',
        'bef8d6c9-9c21-4c9e-b632-bd58c1009f9f',
        '49535343-8841-43f4-a8d4-ecbe34729bb3',
    ];

    // State
    let _device     = null;
    let _server     = null;
    let _char       = null;
    let _connected  = false;
    let _connecting = null;

    // ESC/POS helpers
    const ESC = 0x1B;
    const GS  = 0x1D;
    const LF  = 0x0A;

    function cmd(...bytes) {
        return new Uint8Array(bytes);
    }

    const INIT        = cmd(ESC, 0x40);
    const ALIGN_CTR   = cmd(ESC, 0x61, 0x01);
    const ALIGN_LEFT  = cmd(ESC, 0x61, 0x00);
    const BOLD_ON     = cmd(ESC, 0x45, 0x01);
    const BOLD_OFF    = cmd(ESC, 0x45, 0x00);
    const FONT_NORMAL = cmd(ESC, 0x21, 0x00);
    const FONT_LARGE  = cmd(ESC, 0x21, 0x30);
    const FONT_MED    = cmd(ESC, 0x21, 0x10);
    const CUT         = cmd(GS, 0x56, 0x42, 0x00);
    const FEED2       = cmd(LF, LF);

    function encode(str) {
        const bytes = [];

        for (let i = 0; i < str.length; i++) {
            const c = str.charCodeAt(i);
            bytes.push(c < 256 ? c : 0x3F);
        }

        return new Uint8Array(bytes);
    }

    function padLine(left, right, width) {
        width = width || 32;

        const pad = width - left.length - right.length;

        if (pad <= 0) {
            return (left + ' ' + right).substring(0, width);
        }

        return left + ' '.repeat(pad) + right;
    }

    function divider(char, width) {
        width = width || 32;

        return (char || '-').repeat(width);
    }

    function mergeBytes(arrays) {
        const total = arrays.reduce(
            (sum, array) => sum + array.length,
            0
        );

        const output = new Uint8Array(total);

        let offset = 0;

        for (const array of arrays) {
            output.set(array, offset);
            offset += array.length;
        }

        return output;
    }

    const PAPER_WIDTH = 32;

    function padCols(cols, widths) {
        let output = '';

        for (let i = 0; i < cols.length; i++) {
            const width = widths[i];

            let text = String(cols[i]);

            if (text.length > width) {
                text = text.substring(0, width);
            }

            if (i === cols.length - 1) {
                output += text.padStart(width, ' ');
            } else if (i === 0) {
                output += text.padEnd(width, ' ');
            } else {
                const pad = width - text.length;
                const left = Math.floor(pad / 2);
                const right = pad - left;

                output +=
                    ' '.repeat(left) +
                    text +
                    ' '.repeat(right);
            }
        }

        return output;
    }

    // =========================================================
    // LOGO
    // =========================================================

    function loadImage(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();

            img.crossOrigin = 'anonymous';

            img.onload = () => resolve(img);

            img.onerror = () => {
                reject(
                    new Error(
                        'logo image failed to load'
                    )
                );
            };

            img.src = url;
        });
    }

    function imageToRasterCmd(img, maxWidthPx) {
        let width =
            img.naturalWidth ||
            img.width;

        let height =
            img.naturalHeight ||
            img.height;

        if (!width || !height) {
            return new Uint8Array(0);
        }

        if (width > maxWidthPx) {
            height = Math.round(
                height * (maxWidthPx / width)
            );

            width = maxWidthPx;
        }

        width = width - (width % 8);

        if (width <= 0 || height <= 0) {
            return new Uint8Array(0);
        }

        const canvas =
            document.createElement('canvas');

        canvas.width = width;
        canvas.height = height;

        const ctx =
            canvas.getContext('2d');

        ctx.fillStyle = '#fff';

        ctx.fillRect(
            0,
            0,
            width,
            height
        );

        ctx.drawImage(
            img,
            0,
            0,
            width,
            height
        );

        const { data } =
            ctx.getImageData(
                0,
                0,
                width,
                height
            );

        const bytesPerRow =
            width / 8;

        const bitmap =
            new Uint8Array(
                bytesPerRow * height
            );

        for (
            let y = 0;
            y < height;
            y++
        ) {
            for (
                let x = 0;
                x < width;
                x++
            ) {
                const index =
                    (y * width + x) * 4;

                const r = data[index];
                const g = data[index + 1];
                const b = data[index + 2];
                const a = data[index + 3];

                const luminance =
                    0.299 * r +
                    0.587 * g +
                    0.114 * b;

                const isBlack =
                    a > 40 &&
                    luminance < 150;

                if (isBlack) {
                    bitmap[
                        y * bytesPerRow +
                        (x >> 3)
                    ] |=
                        0x80 >>
                        (x % 8);
                }
            }
        }

        const xL =
            bytesPerRow & 0xFF;

        const xH =
            (bytesPerRow >> 8) & 0xFF;

        const yL =
            height & 0xFF;

        const yH =
            (height >> 8) & 0xFF;

        const header = cmd(
            GS,
            0x76,
            0x30,
            0x00,
            xL,
            xH,
            yL,
            yH
        );

        return mergeBytes([
            header,
            bitmap
        ]);
    }

    async function logoRasterBytes(
        logoUrl
    ) {
        if (!logoUrl) {
            return new Uint8Array(0);
        }

        try {
            const img =
                await loadImage(
                    logoUrl
                );

            return imageToRasterCmd(
                img,
                260
            );
        } catch (error) {
            console.warn(
                '[Printer] Logo not printed:',
                error.message
            );

            return new Uint8Array(0);
        }
    }

    // =========================================================
    // BUILD RECEIPT
    // =========================================================

    async function buildReceipt(
        order,
        settings
    ) {
        const parts = [];

        const push = (...arrays) => {
            parts.push(...arrays);
        };

        push(INIT);

        // Header
        push(ALIGN_CTR);

        const logoUrl =
            settings.logo_path
                ? (
                    typeof BASE_URL !==
                    'undefined'
                        ? BASE_URL
                        : ''
                ) +
                settings.logo_path
                : null;

        const logoBytes =
            await logoRasterBytes(
                logoUrl
            );

        if (logoBytes.length > 0) {
            push(
                logoBytes,
                cmd(LF)
            );
        } else {
            push(BOLD_ON);

            push(
                encode(
                    settings.store_name ||
                    'Twist & Roll'
                ),
                cmd(LF)
            );

            push(BOLD_OFF);
        }

        push(
            encode(
                (
                    settings.store_name ||
                    'Twist & Roll'
                ).toUpperCase()
            ),
            cmd(LF)
        );

        if (settings.store_address) {
            push(
                encode(
                    settings.store_address
                ),
                cmd(LF)
            );
        }

        if (settings.receipt_header) {
            push(
                BOLD_ON,
                encode(
                    settings.receipt_header
                ),
                BOLD_OFF,
                cmd(LF)
            );
        }

        push(ALIGN_LEFT);

        push(
            encode(
                divider(
                    '=',
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        // Order information
        const now = new Date();

        const dateStr =
            now.toLocaleDateString(
                'en-PH',
                {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                }
            );

        const timeStr =
            now.toLocaleTimeString(
                'en-PH',
                {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }
            );

        if (
            settings.show_cashier &&
            settings.cashier_name
        ) {
            push(
                encode(
                    padLine(
                        'Cashier:',
                        settings.cashier_name,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        if (settings.show_order_type) {
            const typeLabels = {
                'dine-in': 'Dine In',
                'take-out': 'Take Out',
                'delivery': 'Delivery',
                'pick-up': 'Pick Up'
            };

            const typeLabel =
                typeLabels[
                    order.order_type
                ] ||
                order.order_type ||
                'Unknown';

            push(
                encode(
                    padLine(
                        'Order Type:',
                        typeLabel,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        const payMethod =
            order.payment_method === 'cash'
                ? 'Cash'
                : 'GCash';

        if (
            settings.show_payment_type
        ) {
            push(
                encode(
                    padLine(
                        'Payment Type:',
                        payMethod,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        push(
            encode(
                padLine(
                    'Date:',
                    `${dateStr}  ${timeStr}`,
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        if (
            settings.show_beeper &&
            order.beeper_number
        ) {
            push(BOLD_ON);

            push(
                encode(
                    padLine(
                        'Beeper #:',
                        String(
                            order.beeper_number
                        ),
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );

            push(BOLD_OFF);
        }

        push(
            encode(
                divider(
                    '-',
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        // Items
        const COLS = [
            16,
            6,
            10
        ];

        push(BOLD_ON);

        push(
            encode(
                padCols(
                    [
                        'Item',
                        'Qty',
                        'Price'
                    ],
                    COLS
                )
            ),
            cmd(LF)
        );

        push(BOLD_OFF);

        push(
            encode(
                divider(
                    '-',
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        for (
            const item of order.items
        ) {
            const itemTotal =
                item.price *
                item.qty;

            const priceStr =
                itemTotal.toLocaleString();

            if (
                item.name.length >
                COLS[0]
            ) {
                push(
                    encode(
                        item.name.substring(
                            0,
                            PAPER_WIDTH
                        )
                    ),
                    cmd(LF)
                );

                push(
                    encode(
                        padCols(
                            [
                                '',
                                String(
                                    item.qty
                                ),
                                priceStr
                            ],
                            COLS
                        )
                    ),
                    cmd(LF)
                );
            } else {
                push(
                    encode(
                        padCols(
                            [
                                item.name,
                                String(
                                    item.qty
                                ),
                                priceStr
                            ],
                            COLS
                        )
                    ),
                    cmd(LF)
                );
            }
        }
                push(
            encode(
                divider(
                    '-',
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        // Totals
        push(
            encode(
                padLine(
                    'Subtotal',
                    order.subtotal.toLocaleString(),
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        if (
            settings.show_discount &&
            order.discount > 0
        ) {
            push(
                encode(
                    padLine(
                        'Discount',
                        `-${order.discount.toLocaleString()}`,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        if (
            order.order_type === 'delivery'
        ) {
            push(
                encode(
                    padLine(
                        'Delivery Fee',
                        '30',
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        push(
            encode(
                padLine(
                    'TOTAL',
                    Number(
                        order.total
                    ).toFixed(0),
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        if (
            order.payment_method === 'cash' &&
            order.change_amount > 0
        ) {
            push(
                encode(
                    padLine(
                        'Cash Paid:',
                        `Php ${parseFloat(
                            order.amount_paid
                        ).toLocaleString()}`,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );

            push(
                encode(
                    padLine(
                        'Change:',
                        `Php ${parseFloat(
                            order.change_amount
                        ).toFixed(2)}`,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        if (
            order.payment_method === 'gcash' &&
            order.gcash_reference
        ) {
            push(
                encode(
                    padLine(
                        'GCash Ref:',
                        order.gcash_reference,
                        PAPER_WIDTH
                    )
                ),
                cmd(LF)
            );
        }

        push(
            encode(
                divider(
                    '-',
                    PAPER_WIDTH
                )
            ),
            cmd(LF)
        );

        // Footer
        push(
            ALIGN_CTR,
            BOLD_ON
        );

        push(
            encode(
                'THANK YOU !!!'
            ),
            cmd(LF)
        );

        push(BOLD_OFF);

        if (
            settings.receipt_footer
        ) {
            push(
                encode(
                    settings.receipt_footer
                ),
                cmd(LF)
            );
        }

        push(FEED2);
        push(CUT);

        return mergeBytes(parts);
    }

    // =========================================================
    // SEND DATA TO PRINTER
    // =========================================================

    async function sendChunked(
        characteristic,
        data,
        chunkSize
    ) {
        chunkSize = chunkSize || 100;

        for (
            let i = 0;
            i < data.length;
            i += chunkSize
        ) {
            const chunk =
                data.slice(
                    i,
                    i + chunkSize
                );

            if (
                characteristic.properties
                    .writeWithoutResponse &&
                characteristic
                    .writeValueWithoutResponse
            ) {
                await characteristic
                    .writeValueWithoutResponse(
                        chunk
                    );
            } else {
                await characteristic
                    .writeValue(
                        chunk
                    );
            }

            await new Promise(
                resolve =>
                    setTimeout(
                        resolve,
                        30
                    )
            );
        }
    }

    // =========================================================
    // PRINTER MEMORY
    // =========================================================

    function forgetDevice() {
        localStorage.removeItem(
            'twistroll_printer_id'
        );

        if (
            _device?.gatt?.connected
        ) {
            _device.gatt.disconnect();
        }

        _device = null;
        _server = null;
        _char = null;
        _connected = false;
    }

    // =========================================================
    // DISCONNECT EVENT
    // =========================================================

    function handleGattDisconnected() {
        _connected = false;
        _char = null;
        _server = null;

        console.log(
            '[Printer] GATT disconnected'
        );
    }

    // =========================================================
    // CONNECT
    // =========================================================

    async function connect(
        showPicker = true
    ) {
        if (!navigator.bluetooth) {
            throw new Error(
                'Web Bluetooth is not supported in this browser.\n' +
                'Please use Chrome or Edge on Android/Windows.'
            );
        }

        // Already connected
        if (
            _connected &&
            _char &&
            _device?.gatt?.connected
        ) {
            return true;
        }

        // Prevent two reconnect attempts
        // from running at the same time.
        if (_connecting) {
            return _connecting;
        }

        _connecting =
            (async () => {

                const rememberedId =
                    localStorage.getItem(
                        'twistroll_printer_id'
                    );

                // Try to recover the printer
                // previously allowed by the user.
                if (
                    !_device &&
                    rememberedId &&
                    navigator.bluetooth.getDevices
                ) {
                    try {
                        const knownDevices =
                            await navigator.bluetooth
                                .getDevices();

                        _device =
                            knownDevices.find(
                                device =>
                                    device.id ===
                                    rememberedId
                            ) || null;

                    } catch (error) {
                        console.warn(
                            '[Printer] Could not read remembered devices:',
                            error
                        );

                        _device = null;
                    }
                }

                // Silent reconnect:
                // NEVER open the Bluetooth chooser.
                if (
                    !_device &&
                    !showPicker
                ) {
                    throw new Error(
                        'Remembered printer is not available.'
                    );
                }

                // Manual connection.
                // User clicked the Printer button,
                // so showing the chooser is allowed.
                if (!_device) {
                    _device =
                        await navigator.bluetooth
                            .requestDevice({
                                acceptAllDevices: true,
                                optionalServices:
                                    CANDIDATE_SERVICES
                            });

                    localStorage.setItem(
                        'twistroll_printer_id',
                        _device.id
                    );
                }

                // Avoid duplicate disconnect listeners.
                _device.removeEventListener(
                    'gattserverdisconnected',
                    handleGattDisconnected
                );

                _device.addEventListener(
                    'gattserverdisconnected',
                    handleGattDisconnected
                );

                // Connect to GATT if needed.
                if (
                    _device.gatt.connected
                ) {
                    _server =
                        _device.gatt;
                } else {
                    _server =
                        await _device.gatt
                            .connect();
                }

                console.log(
                    '[Printer] GATT connected to',
                    _device.name
                );

                // Try known services and
                // characteristics first.
                for (
                    const svcUUID
                    of CANDIDATE_SERVICES
                ) {
                    let service;

                    try {
                        service =
                            await _server
                                .getPrimaryService(
                                    svcUUID
                                );
                    } catch {
                        continue;
                    }

                    for (
                        const charUUID
                        of CANDIDATE_CHARS
                    ) {
                        try {
                            const characteristic =
                                await service
                                    .getCharacteristic(
                                        charUUID
                                    );

                            if (
                                characteristic
                                    .properties
                                    .write ||
                                characteristic
                                    .properties
                                    .writeWithoutResponse
                            ) {
                                _char =
                                    characteristic;

                                _connected =
                                    true;

                                console.log(
                                    '[Printer] Ready —',
                                    svcUUID,
                                    '/',
                                    charUUID
                                );

                                return true;
                            }

                        } catch {
                            continue;
                        }
                    }
                }

                // Fallback:
                // search every available service.
                try {
                    const services =
                        await _server
                            .getPrimaryServices();

                    for (
                        const service
                        of services
                    ) {
                        const characteristics =
                            await service
                                .getCharacteristics();

                        for (
                            const characteristic
                            of characteristics
                        ) {
                            if (
                                characteristic
                                    .properties
                                    .write ||
                                characteristic
                                    .properties
                                    .writeWithoutResponse
                            ) {
                                _char =
                                    characteristic;

                                _connected =
                                    true;

                                console.log(
                                    '[Printer] Fallback char found:',
                                    characteristic.uuid
                                );

                                return true;
                            }
                        }
                    }

                } catch (error) {
                    console.warn(
                        '[Printer] Fallback enumeration failed:',
                        error
                    );
                }

                throw new Error(
                    'Connected to "' +
                    (
                        _device.name ||
                        'printer'
                    ) +
                    '" but could not find a writable characteristic.'
                );
            })();

        try {
            return await _connecting;
        } finally {
            _connecting = null;
        }
    }

    // =========================================================
    // ENSURE CONNECTION
    // =========================================================

    async function ensureConnected(
        showPicker = true
    ) {
        if (
            _connected &&
            _char &&
            _device?.gatt?.connected
        ) {
            return true;
        }

        return connect(
            showPicker
        );
    }

    // =========================================================
    // PRINT
    // =========================================================

    async function print(
        order,
        settings
    ) {
        /*
         * print() does not need to open the
         * chooser if we're already connected.
         *
         * If this is called after a page reload,
         * ensureConnected() can restore the
         * previously granted device.
         */
        await ensureConnected(
            false
        );

        const bytes =
            await buildReceipt(
                order,
                settings
            );

        if (
            !_char ||
            !_device?.gatt?.connected
        ) {
            throw new Error(
                'Printer connection was lost.'
            );
        }

        await sendChunked(
            _char,
            bytes
        );

        console.log(
            '[Printer] Print job sent —',
            bytes.length,
            'bytes'
        );

        return true;
    }

    // =========================================================
    // MANUAL DISCONNECT
    // =========================================================

    function disconnect() {
        if (
            _device?.gatt?.connected
        ) {
            _device.gatt.disconnect();
        }

        _connected = false;
        _char = null;
        _server = null;
    }

    // =========================================================
    // STATUS
    // =========================================================

    function isConnected() {
        return (
            _connected &&
            !!_char &&
            !!_device?.gatt?.connected
        );
    }

    // =========================================================
    // PUBLIC API
    // =========================================================

    return {
        print,

        disconnect,

        isConnected,

        // Manual connect.
        // Can show Bluetooth chooser.
        connect: function () {
            return ensureConnected(
                true
            );
        },

        // Automatic reconnect.
        // NEVER shows Bluetooth chooser.
        reconnect: function () {
            return ensureConnected(
                false
            );
        },

        forgetDevice
    };

})();
// Expose the persistent printer instance to iframe pages.
window.BluetoothPrinter = BluetoothPrinter;