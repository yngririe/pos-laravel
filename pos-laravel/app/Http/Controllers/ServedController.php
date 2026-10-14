<?php

namespace App\Http\Controllers;

use App\Support\Pos;
use Carbon\Carbon;

class ServedController extends Controller
{
    public function index()
    {
        $branch_id = Pos::branchId();
        $now       = Carbon::now(Pos::TZ);

        // The served list resets every Monday at 5:00 AM (Manila time).
        $lastReset = $now->copy()->startOfWeek(Carbon::MONDAY)->setTime(5, 0, 0);
        if ($now->lt($lastReset)) {
            $lastReset->subDays(7);
        }
        $reset_datetime = $lastReset->format('Y-m-d H:i:s');                    // shown to the user (Manila)
        $resetUtc       = $lastReset->copy()->utc()->format('Y-m-d H:i:s');     // compared with served_at (UTC)

        $bizDate = Pos::bizDate('o.served_at');

        $served_orders = Pos::all("
            SELECT o.*,
                   GROUP_CONCAT(oi.name     ORDER BY oi.id SEPARATOR '||') AS item_names,
                   GROUP_CONCAT(oi.price    ORDER BY oi.id SEPARATOR '||') AS item_prices,
                   GROUP_CONCAT(oi.quantity ORDER BY oi.id SEPARATOR '||') AS item_qtys,
                   {$bizDate} AS biz_date
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE o.status = 'served' AND o.served_at >= ? AND o.branch_id = ?
            GROUP BY o.id
            ORDER BY o.served_at DESC, o.created_at DESC
        ", [$resetUtc, $branch_id]);

        $served_orders = Pos::localizeRows($served_orders);

        $unique_biz_dates = [];
        foreach ($served_orders as $o) {
            $bd = $o['biz_date'];
            if ($bd && !in_array($bd, $unique_biz_dates)) {
                $unique_biz_dates[] = $bd;
            }
        }
        sort($unique_biz_dates);

        return view('pages.served', Pos::shared('served') + compact('served_orders', 'unique_biz_dates', 'reset_datetime'));
    }
}
