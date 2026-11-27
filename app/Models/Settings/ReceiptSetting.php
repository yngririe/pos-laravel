<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    const CREATED_AT = null;   // table only has updated_at

    protected $fillable = [
        'store_name', 'logo_path', 'store_address', 'store_contact', 'fb_page_url', 'store_tin',
        'receipt_header', 'receipt_footer',
        'show_discount', 'show_cashier', 'show_order_type', 'show_payment_type', 'show_beeper',
        'dark_mode',
    ];
}
