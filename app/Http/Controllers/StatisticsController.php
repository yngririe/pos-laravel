<?php

namespace App\Http\Controllers;

use App\Support\Pos;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin-only. A "business day" runs 5:00 AM -> 5:00 AM Manila time, see Pos::bizDate().
 */
class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $shared    = Pos::shared('statistics');
        $branch_id = (int) $shared['branch_id'];

        $BRANCH      = "AND o.branch_id = {$branch_id}";
        $BRANCH_ONLY = "AND branch_id = {$branch_id}";

        $BIZ_DATE  = Pos::bizDate();
        $BIZ_YEAR  = Pos::bizYear();
        $BIZ_MONTH = Pos::bizMonth();

        $nowPht = Carbon::now(Pos::TZ);

        $selected_year    = $request->has('year')       ? (int) $request->query('year')       : $nowPht->year;
        $selected_month   = $request->has('month')      ? (int) $request->query('month')      : $nowPht->month;
        $selected_section = $request->query('section');
        $sidebar_open     = $request->has('sidebar');
        $chart_year       = $request->has('chart_year')  ? (int) $request->query('chart_year')  : $nowPht->year;
        $chart_month      = $request->has('chart_month') ? (int) $request->query('chart_month') : $nowPht->month;

        $months_list = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $year_range = range(2026, 2036);

        // Before 5 AM we are still in the previous business day.
        $bizNow = $nowPht->copy();
        if ($bizNow->hour < 5) {
            $bizNow->subDay();
        }
        $today = $bizNow->format('Y-m-d');

        // ── Dashboard ──────────────────────────────────────────────────────
        $today_data = Pos::one("
            SELECT COUNT(*) as c, COALESCE(SUM(total),0) as s
            FROM orders
            WHERE $BIZ_DATE = ? AND status IN ('pending','served') $BRANCH_ONLY
        ", [$today]);

        $monthly = Pos::one("
            SELECT COALESCE(SUM(total),0) as total_sales, COUNT(*) as total_orders
            FROM orders
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND status IN ('pending','served') $BRANCH_ONLY
        ", [$selected_year, $selected_month]);

        $best_items = Pos::all("
            SELECT oi.name, oi.menu_item_id, SUM(oi.quantity) as total_qty
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND o.status IN ('pending','served') $BRANCH
            GROUP BY oi.name,oi.menu_item_id ORDER BY total_qty DESC LIMIT 3
        ", [$selected_year, $selected_month]);

        $least_items = Pos::all("
            SELECT oi.name, oi.menu_item_id, SUM(oi.quantity) as total_qty
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND o.status IN ('pending','served') $BRANCH
            GROUP BY oi.name,oi.menu_item_id ORDER BY total_qty ASC LIMIT 3
        ", [$selected_year, $selected_month]);

        $img_map = [];
        foreach (Pos::all('SELECT id, image FROM menu_items') as $row) {
            $img_map[$row['id']] = $row['image'];
        }

        $daily_data = Pos::all("
            SELECT $BIZ_DATE as sale_date,
                   COUNT(*) as order_count,
                   COALESCE(SUM(total),0) as total_sales
            FROM orders
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND status IN ('pending','served') $BRANCH_ONLY
            GROUP BY $BIZ_DATE ORDER BY sale_date ASC
        ", [$chart_year, $chart_month]);

        $weekly_raw = array_reverse(Pos::all("
            SELECT YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 5 HOUR, 1) as yw,
                   MIN($BIZ_DATE) as week_start,
                   MAX($BIZ_DATE) as week_end,
                   COALESCE(SUM(total),0) as total_sales,
                   COUNT(*) as order_count
            FROM orders
            WHERE status IN ('pending','served') $BRANCH_ONLY
            GROUP BY YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 5 HOUR, 1)
            ORDER BY yw DESC LIMIT 5
        "));

        $monthly_trend = Pos::all("
            SELECT $BIZ_MONTH as mo,
                   COALESCE(SUM(total),0) as total_sales,
                   COUNT(*) as order_count
            FROM orders
            WHERE $BIZ_YEAR=? AND status IN ('pending','served') $BRANCH_ONLY
            GROUP BY $BIZ_MONTH ORDER BY mo ASC
        ", [$selected_year]);

        // ── Sidebar ────────────────────────────────────────────────────────
        $annual_by_month = [];
        foreach (Pos::all("
            SELECT $BIZ_MONTH as mo,
                   COALESCE(SUM(CASE WHEN status='served' THEN total ELSE 0 END),0) as total,
                   COUNT(CASE WHEN status IN ('pending','served','voided') THEN 1 END) as orders,
                   COUNT(CASE WHEN status='served' THEN 1 END) as served
            FROM orders
            WHERE $BIZ_YEAR=? AND status IN ('pending','served','voided') $BRANCH_ONLY
            GROUP BY $BIZ_MONTH
            ORDER BY mo
        ", [$selected_year]) as $r) {
            $annual_by_month[$r['mo']] = $r;
        }

        $annual_total = Pos::val("
            SELECT COALESCE(SUM(total),0)
            FROM orders
            WHERE $BIZ_YEAR=? AND status = 'served' $BRANCH_ONLY
        ", [$selected_year]);

        $voids_by_month = [];
        foreach (Pos::all("
            SELECT $BIZ_MONTH as mo, COUNT(*) as cnt
            FROM orders
            WHERE $BIZ_YEAR=? AND status='voided' $BRANCH_ONLY
            GROUP BY $BIZ_MONTH
        ", [$selected_year]) as $r) {
            $voids_by_month[$r['mo']] = $r['cnt'];
        }

        // ── Section detail (served / voids / all orders for the month) ─────
        $section_orders = [];
        $section_stats  = [];
        $high_date = $low_date = null;
        $bar_data  = [];

        if ($sidebar_open && $selected_section) {
            $status_filter = match ($selected_section) {
                'served' => "'served'",
                'voids'  => "'voided'",
                default  => "'pending','served','voided'",
            };
            $order_by = $selected_section === 'served' ? 'o.served_at DESC' : 'o.created_at DESC';

            $section_orders = Pos::localizeRows(Pos::all("
                SELECT o.id, o.beeper_number,
                       o.created_at, o.served_at,
                       o.order_type, o.payment_method,
                       o.gcash_reference, o.gcash_reference_extra, o.gcash_extra_amount,
                       o.subtotal, o.discount, o.refund_amount, o.total, o.status,
                       GROUP_CONCAT(CONCAT(oi.quantity,'x ',oi.name,'|',oi.price) SEPARATOR ';;') AS items_data
                FROM orders o
                LEFT JOIN order_items oi ON oi.order_id = o.id
                WHERE {$BIZ_YEAR}=? AND {$BIZ_MONTH}=?
                  AND o.branch_id = $branch_id
                  AND o.status IN ($status_filter)
                GROUP BY o.id
                ORDER BY $order_by
            ", [$selected_year, $selected_month]));

            $section_stats = Pos::one("
                SELECT COUNT(*) as total, MAX(total) as highest, MIN(total) as lowest,
                       COALESCE(SUM(total),0) as sum
                FROM orders
                WHERE {$BIZ_YEAR}=? AND {$BIZ_MONTH}=? AND branch_id = $branch_id AND status IN ($status_filter)
            ", [$selected_year, $selected_month]);

            $high_date = Pos::val("
                SELECT $BIZ_DATE FROM orders
                WHERE {$BIZ_YEAR}=? AND {$BIZ_MONTH}=? AND branch_id = $branch_id AND status IN ($status_filter)
                ORDER BY total DESC LIMIT 1
            ", [$selected_year, $selected_month]);

            $low_date = Pos::val("
                SELECT $BIZ_DATE FROM orders
                WHERE {$BIZ_YEAR}=? AND {$BIZ_MONTH}=? AND branch_id = $branch_id AND status IN ($status_filter)
                ORDER BY total ASC LIMIT 1
            ", [$selected_year, $selected_month]);

            $bar_data = Pos::all("
                SELECT $BIZ_DATE as d, COUNT(*) as cnt
                FROM orders
                WHERE {$BIZ_YEAR}=? AND {$BIZ_MONTH}=? AND branch_id = $branch_id AND status IN ($status_filter)
                GROUP BY $BIZ_DATE ORDER BY d
            ", [$selected_year, $selected_month]);
        }

        $daily_json   = json_encode($daily_data);
        $weekly_json  = json_encode($weekly_raw);
        $monthly_json = json_encode($monthly_trend);
        $bar_json     = json_encode($bar_data);

        return view('pages.statistics', $shared + compact(
            'today', 'today_data', 'monthly', 'best_items', 'least_items', 'img_map',
            'daily_data', 'weekly_raw', 'monthly_trend',
            'annual_by_month', 'annual_total', 'voids_by_month',
            'section_orders', 'section_stats', 'high_date', 'low_date', 'bar_data',
            'daily_json', 'weekly_json', 'monthly_json', 'bar_json',
            'selected_year', 'selected_month', 'selected_section', 'sidebar_open',
            'chart_year', 'chart_month', 'months_list', 'year_range'
        ));
    }

    /** JSON feed for the Excel export (the browser builds the .xlsx). */
    public function report(Request $request): JsonResponse
    {
        $branch_id = Pos::branchId();
        $merge     = $request->query('merge') === '1';

        $BIZ_DATE  = Pos::bizDate();
        $BIZ_YEAR  = Pos::bizYear();
        $BIZ_MONTH = Pos::bizMonth();

        $nowPht      = Carbon::now(Pos::TZ);
        $year        = $request->has('year')         ? (int) $request->query('year')         : $nowPht->year;
        $month       = $request->has('month')        ? (int) $request->query('month')        : $nowPht->month;
        $filterMonth = $request->has('filter_month') ? (int) $request->query('filter_month') : 0;

        // Branch names (merge mode lists every branch).
        $branch_names = [];
        if ($merge) {
            foreach (Pos::all('SELECT id, branch_name FROM users ORDER BY id') as $row) {
                $branch_names[$row['id']] = $row['branch_name'];
            }
        } else {
            $branch_names[$branch_id] = Pos::val('SELECT branch_name FROM users WHERE id = ?', [$branch_id]) ?? 'Branch';
        }

        $branch_where   = $merge ? '1=1' : "branch_id = $branch_id";
        $branch_where_o = $merge ? '1=1' : "o.branch_id = $branch_id";

        // ── Orders ─────────────────────────────────────────────────────────
        $orderCols = "o.id, o.branch_id, o.beeper_number, o.order_type, o.payment_method,
                      o.gcash_reference, o.gcash_reference_extra, o.gcash_extra_amount,
                      o.subtotal, o.discount, o.refund_amount, o.total, o.change_amount,
                      o.status, o.created_at, o.served_at,
                      GROUP_CONCAT(CONCAT(oi.quantity,'x ',oi.name) ORDER BY oi.id SEPARATOR ', ') AS items_str";

        if ($filterMonth > 0) {
            $orders = Pos::all("
                SELECT $orderCols
                FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id
                WHERE $BIZ_YEAR = ? AND $BIZ_MONTH = ? AND o.status IN ('served','voided') AND $branch_where_o
                GROUP BY o.id ORDER BY o.created_at ASC
            ", [$year, $filterMonth]);
        } else {
            $orders = Pos::all("
                SELECT $orderCols
                FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id
                WHERE $BIZ_YEAR = ? AND o.status IN ('served','voided') AND $branch_where_o
                GROUP BY o.id ORDER BY o.created_at ASC
            ", [$year]);
        }
        $orders = Pos::localizeRows($orders);
        foreach ($orders as &$o) {
            $o['branch_name'] = $branch_names[$o['branch_id']] ?? 'Branch ' . $o['branch_id'];
        }
        unset($o);

        // ── Daily summary ──────────────────────────────────────────────────
        $dailyMonth = $filterMonth;
        $dailySql = "
            SELECT $BIZ_DATE AS d,
                   COUNT(*) AS total_orders,
                   COUNT(CASE WHEN status='served' THEN 1 END) AS served,
                   COUNT(CASE WHEN status='voided' THEN 1 END) AS voided,
                   COALESCE(SUM(CASE WHEN status='served' THEN total    ELSE 0 END),0) AS total_sales,
                   COALESCE(SUM(CASE WHEN status='served' THEN discount ELSE 0 END),0) AS total_discounts
            FROM orders
            WHERE $BIZ_YEAR=? %s AND status IN ('served','voided') AND $branch_where
            GROUP BY $BIZ_DATE ORDER BY d ASC";

        $emptyDay = fn (string $key) => [
            'd' => $key, 'total_orders' => 0, 'served' => 0,
            'voided' => 0, 'total_sales' => 0, 'total_discounts' => 0,
        ];

        $daily = [];
        if ($filterMonth > 0) {
            $byDate = [];
            foreach (Pos::all(sprintf($dailySql, "AND $BIZ_MONTH=?"), [$year, $dailyMonth]) as $r) {
                $byDate[$r['d']] = $r;
            }
            $days = Carbon::create($year, $dailyMonth, 1)->daysInMonth;
            for ($d = 1; $d <= $days; $d++) {
                $key     = sprintf('%04d-%02d-%02d', $year, $dailyMonth, $d);
                $daily[] = $byDate[$key] ?? $emptyDay($key);
            }
        } else {
            $byDate = [];
            foreach (Pos::all(sprintf($dailySql, ''), [$year]) as $r) {
                $byDate[$r['d']] = $r;
            }
            for ($mo = 1; $mo <= 12; $mo++) {
                $days = Carbon::create($year, $mo, 1)->daysInMonth;
                for ($d = 1; $d <= $days; $d++) {
                    $key     = sprintf('%04d-%02d-%02d', $year, $mo, $d);
                    $daily[] = $byDate[$key] ?? $emptyDay($key);
                }
            }
        }

        // ── Weekly ─────────────────────────────────────────────────────────
        $wWhere  = "$BIZ_YEAR=? AND $branch_where";
        $wParams = [$year];
        if ($filterMonth > 0) {
            $wWhere   .= " AND $BIZ_MONTH=?";
            $wParams[] = $filterMonth;
        }
        $weekly = Pos::all("
            SELECT YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 5 HOUR, 1) AS yw,
                   MIN($BIZ_DATE) AS week_start,
                   MAX($BIZ_DATE) AS week_end,
                   COUNT(*) AS total_orders,
                   COUNT(CASE WHEN status='served' THEN 1 END) AS served,
                   COUNT(CASE WHEN status='voided' THEN 1 END) AS voided,
                   COALESCE(SUM(CASE WHEN status='served' THEN total    ELSE 0 END),0) AS total_sales,
                   COALESCE(SUM(CASE WHEN status='served' THEN discount ELSE 0 END),0) AS total_discounts
            FROM orders
            WHERE $wWhere AND status IN ('served','voided')
            GROUP BY YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 5 HOUR, 1)
            ORDER BY yw ASC
        ", $wParams);

        // ── Monthly ────────────────────────────────────────────────────────
        $monthlyByMo = [];
        foreach (Pos::all("
            SELECT $BIZ_MONTH AS mo,
                   COUNT(*) AS total_orders,
                   COUNT(CASE WHEN status='served'  THEN 1 END) AS served,
                   COUNT(CASE WHEN status='pending' THEN 1 END) AS pending,
                   COUNT(CASE WHEN status='voided'  THEN 1 END) AS voided,
                   COALESCE(SUM(CASE WHEN status='served' THEN total    ELSE 0 END),0) AS total_sales,
                   COALESCE(SUM(CASE WHEN status='served' THEN discount ELSE 0 END),0) AS total_discounts,
                   COALESCE(AVG(CASE WHEN status='served' THEN total ELSE NULL END),0) AS avg_order
            FROM orders
            WHERE $BIZ_YEAR=? AND $branch_where AND status IN ('served','voided')
            GROUP BY $BIZ_MONTH ORDER BY mo
        ", [$year]) as $r) {
            $monthlyByMo[(int) $r['mo']] = $r;
        }
        $monthly = [];
        for ($mo = 1; $mo <= 12; $mo++) {
            $monthly[] = $monthlyByMo[$mo] ?? [
                'mo' => $mo, 'total_orders' => 0, 'served' => 0,
                'voided' => 0, 'total_sales' => 0, 'total_discounts' => 0, 'avg_order' => 0,
            ];
        }

        // ── Annual ─────────────────────────────────────────────────────────
        $annual = Pos::all("
            SELECT $BIZ_YEAR AS yr,
                   COUNT(*) AS total_orders,
                   COUNT(CASE WHEN status='served'  THEN 1 END) AS served,
                   COUNT(CASE WHEN status='pending' THEN 1 END) AS pending,
                   COUNT(CASE WHEN status='voided'  THEN 1 END) AS voided,
                   COALESCE(SUM(CASE WHEN status='served' THEN total    ELSE 0 END),0) AS total_sales,
                   COALESCE(SUM(CASE WHEN status='served' THEN discount ELSE 0 END),0) AS total_discounts
            FROM orders
            WHERE $branch_where AND status IN ('served','voided')
            GROUP BY $BIZ_YEAR ORDER BY yr ASC
        ");

        // ── Top items ──────────────────────────────────────────────────────
        $topSql = "
            SELECT oi.name,
                   SUM(oi.quantity)                      AS total_qty,
                   COALESCE(SUM(oi.quantity*oi.price),0) AS total_revenue
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            WHERE $BIZ_YEAR=? %s AND o.status IN ('served') AND $branch_where_o
            GROUP BY oi.name ORDER BY total_qty DESC";
        $top_items = $filterMonth > 0
            ? Pos::all(sprintf($topSql, "AND $BIZ_MONTH=?"), [$year, $filterMonth])
            : Pos::all(sprintf($topSql, ''), [$year]);

        // ── Per-branch sheets (merge mode) ─────────────────────────────────
        $branches_data = [];
        if ($merge) {
            $branchCols = "o.id, o.beeper_number, o.order_type, o.payment_method,
                           o.subtotal, o.discount, o.total, o.status, o.created_at, o.served_at,
                           GROUP_CONCAT(CONCAT(oi.quantity,'x ',oi.name) ORDER BY oi.id SEPARATOR ', ') AS items_str";

            foreach (Pos::all('SELECT id, branch_name FROM users ORDER BY id') as $branch) {
                if ($filterMonth > 0) {
                    $rows = Pos::all("
                        SELECT $branchCols
                        FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id
                        WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND o.status IN ('served','voided') AND o.branch_id=?
                        GROUP BY o.id ORDER BY o.created_at ASC
                    ", [$year, $filterMonth, $branch['id']]);
                } else {
                    $rows = Pos::all("
                        SELECT $branchCols
                        FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id
                        WHERE $BIZ_YEAR=? AND o.status IN ('served','voided') AND o.branch_id=?
                        GROUP BY o.id ORDER BY o.created_at ASC
                    ", [$year, $branch['id']]);
                }

                $branches_data[] = [
                    'branch_id'   => $branch['id'],
                    'branch_name' => $branch['branch_name'],
                    'orders'      => Pos::localizeRows($rows),
                ];
            }
        }

        return response()->json([
            'year'          => $year,
            'month'         => $month,
            'filter_month'  => $filterMonth,
            'daily_month'   => $dailyMonth,
            'merged'        => $merge,
            'branch_names'  => array_values($branch_names),
            'orders'        => $orders,
            'branches_data' => $branches_data,
            'daily'         => $daily,
            'weekly'        => $weekly,
            'monthly'       => $monthly,
            'annual'        => $annual,
            'top_items'     => $top_items,
        ]);
    }
}
