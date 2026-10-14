<?php

namespace App\Http\Controllers;

use App\Support\Pos;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        [$menu_items, $discount_map] = Pos::menu();

        return view('pages.homepage', Pos::shared('home') + compact('menu_items', 'discount_map'));
    }

    /** Receipt printing settings + the logged-in cashier's name (JSON, used by homepage.js). */
    public function receiptSettings()
    {
        $settings = Pos::one('SELECT * FROM receipt_settings WHERE id = 1') ?? [
            'store_name'      => 'Twist & Roll',
            'store_address'   => '',
            'store_contact'   => '',
            'receipt_header'  => '',
            'receipt_footer'  => 'Thank you for dining with us!',
            'show_discount'   => 1,
            'show_cashier'    => 1,
            'show_order_type' => 1,
            'show_beeper'     => 1,
        ];

        $settings['cashier_name'] = DB::table('users')->where('id', auth()->id())->value('full_name') ?? '';

        foreach (['show_discount', 'show_cashier', 'show_order_type', 'show_beeper'] as $flag) {
            $settings[$flag] = (bool) ($settings[$flag] ?? false);
        }

        return response()->json(['success' => true, 'settings' => $settings]);
    }
}
