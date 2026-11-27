<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Twist & Roll POS</title>

    <meta name="theme-color" content="#1C3924">

    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #ffffff;
        }

        #pos-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>

<iframe
    id="pos-frame"
    src="{{ route('pos.home') }}"
    title="Twist & Roll POS"
></iframe>

<script src="{{ asset('assets/js/escpos_bluetooth.js?v=20261017-persistent1') }}"></script>

<script>
    /*
     * The Bluetooth connection lives in THIS page.
     *
     * The iframe can change from:
     * Home -> Orders -> Served -> Statistics
     *
     * without destroying this JavaScript context.
     */

    window.getPersistentBluetoothPrinter = function () {
        return window.BluetoothPrinter;
    };

    /*
     * If a page inside the iframe requests a full logout,
     * allow it to navigate the whole browser window.
     */
    window.posNavigateTop = function (url) {
        window.location.href = url;
    };
</script>

</body>
</html>