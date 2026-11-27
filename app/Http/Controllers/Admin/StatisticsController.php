<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Pos;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin-only. The live business window is 3:00 PM -> 2:00 AM Manila time.
 * Records are grouped under the calendar date on which that 3:00 PM shift started.
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

        // Live shift: 3:00 PM through 1:59:59 AM. From 2:00 AM until 2:59:59 PM
        // the live finance panel is closed/reset; the completed shift remains in Sales Archive.
        $business_open = $nowPht->hour >= 15 || $nowPht->hour < 5;
        $bizNow = $nowPht->copy();

        if ($nowPht->hour < 5) {
            $bizNow->subDay();
        }
        $today = $bizNow->format('Y-m-d');

        // ── Dashboard ──────────────────────────────────────────────────────
        $emptyTodayData = ['c' => 0, 's' => 0, 'cash_sales' => 0, 'gcash_sales' => 0];
        if ($business_open) {
            $today_data = Pos::one("
                SELECT COUNT(*) as c,
                       COALESCE(SUM(total),0) as s,
                       COALESCE(SUM(CASE WHEN payment_method='cash' THEN total ELSE 0 END),0) as cash_sales,
                       COALESCE(SUM(CASE WHEN payment_method='gcash' THEN total ELSE 0 END),0) as gcash_sales
                FROM orders
                WHERE $BIZ_DATE = ? AND status = 'served' $BRANCH_ONLY
            ", [$today]) ?? $emptyTodayData;

            $today_cash_record = Pos::one("
                SELECT petty_cash
                FROM daily_cash_records
                WHERE branch_id = ? AND business_date = ?
            ", [$branch_id, $today]) ?? ['petty_cash' => 0];

            $today_expenses = Pos::all("
                SELECT id, amount, note, created_at
                FROM daily_expenses
                WHERE branch_id = ? AND business_date = ?
                ORDER BY created_at DESC, id DESC
            ", [$branch_id, $today]);
        } else {
            // Do not carry yesterday's figures into the live panel after closing time.
            $today_data = $emptyTodayData;
            $today_cash_record = ['petty_cash' => 0];
            $today_expenses = [];
        }
        $today_expense_total = array_sum(array_map(fn ($row) => (float) $row['amount'], $today_expenses));
        $today_total_money = (float)$today_cash_record['petty_cash'] + (float)$today_data['s'] - $today_expense_total;

        $monthly = Pos::one("
            SELECT COALESCE(SUM(total),0) as total_sales, COUNT(*) as total_orders
            FROM orders
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND status = 'served' $BRANCH_ONLY
        ", [$selected_year, $selected_month]);

        $best_items = Pos::all("
            SELECT oi.name, oi.menu_item_id, SUM(oi.quantity) as total_qty
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND o.status IN ('pending','served') $BRANCH
            GROUP BY oi.name,oi.menu_item_id ORDER BY total_qty DESC LIMIT 3
        ", [$selected_year, $selected_month]);

        $least_items = Pos::all("
            SELECT
                m.id AS menu_item_id,
                m.name,
                COALESCE(SUM(
                    CASE
                        WHEN $BIZ_YEAR = ?
                        AND $BIZ_MONTH = ?
                        AND o.status IN ('pending','served')
                        AND o.branch_id = ?
                        THEN oi.quantity
                        ELSE 0
                    END
                ), 0) AS total_qty
            FROM menu_items m
            LEFT JOIN order_items oi ON oi.menu_item_id = m.id
            LEFT JOIN orders o ON o.id = oi.order_id
            WHERE m.is_active = 1
            GROUP BY m.id, m.name
            ORDER BY total_qty ASC, m.name ASC
            LIMIT 3
        ", [$selected_year, $selected_month, $branch_id]);

        $img_map = [];
        foreach (Pos::all('SELECT id, image FROM menu_items') as $row) {
            $img_map[$row['id']] = $row['image'];
        }

        $daily_data = Pos::all("
            SELECT $BIZ_DATE as sale_date,
                   COUNT(*) as order_count,
                   COALESCE(SUM(total),0) as total_sales
            FROM orders
            WHERE $BIZ_YEAR=? AND $BIZ_MONTH=? AND status = 'served' $BRANCH_ONLY
            GROUP BY $BIZ_DATE ORDER BY sale_date ASC
        ", [$chart_year, $chart_month]);

        $weekly_raw = array_reverse(Pos::all("
            SELECT YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 15 HOUR, 1) as yw,
                   MIN($BIZ_DATE) as week_start,
                   MAX($BIZ_DATE) as week_end,
                   COALESCE(SUM(total),0) as total_sales,
                   COUNT(*) as order_count
            FROM orders
            WHERE status = 'served' $BRANCH_ONLY
            GROUP BY YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 15 HOUR, 1)
            ORDER BY yw DESC LIMIT 5
        "));

        $monthly_trend = Pos::all("
            SELECT $BIZ_MONTH as mo,
                   COALESCE(SUM(total),0) as total_sales,
                   COUNT(*) as order_count
            FROM orders
            WHERE $BIZ_YEAR=? AND status = 'served' $BRANCH_ONLY
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

        $sales_days_by_month = [];
        foreach (Pos::all("
            SELECT MONTH(business_date) as mo, COUNT(*) as cnt
            FROM daily_cash_records
            WHERE YEAR(business_date)=? AND branch_id=?
            GROUP BY MONTH(business_date)
        ", [$selected_year, $branch_id]) as $r) {
            $sales_days_by_month[$r['mo']] = $r['cnt'];
        }

        // ── Section detail (served / voids / all orders for the month) ─────
        $section_orders = [];
        $section_stats  = [];
        $high_date = $low_date = null;
        $bar_data  = [];

        $sales_archive = [];

        if ($sidebar_open && $selected_section === 'sales') {
            // Build the archive from every date that has petty cash, an expense, or a served order.
            // This keeps a completed business day in Files even when no petty cash was entered.
            $sales_archive = Pos::all("
                SELECT days.business_date,
                       COALESCE((SELECT d.petty_cash FROM daily_cash_records d WHERE d.branch_id=? AND d.business_date=days.business_date LIMIT 1),0) as petty_cash,
                       COALESCE((SELECT SUM(e.amount) FROM daily_expenses e WHERE e.branch_id=? AND e.business_date=days.business_date),0) as expense,
                       COALESCE((SELECT SUM(o.total) FROM orders o WHERE o.branch_id=? AND o.status='served' AND o.payment_method='cash' AND DATE(CONVERT_TZ(o.created_at,'+00:00','+08:00') - INTERVAL 15 HOUR)=days.business_date),0) as cash_sales,
                       COALESCE((SELECT SUM(o.total) FROM orders o WHERE o.branch_id=? AND o.status='served' AND o.payment_method='gcash' AND DATE(CONVERT_TZ(o.created_at,'+00:00','+08:00') - INTERVAL 15 HOUR)=days.business_date),0) as gcash_sales,
                       COALESCE((SELECT SUM(o.total) FROM orders o WHERE o.branch_id=? AND o.status='served' AND DATE(CONVERT_TZ(o.created_at,'+00:00','+08:00') - INTERVAL 15 HOUR)=days.business_date),0) as total_sales
                FROM (
                    SELECT business_date FROM daily_cash_records WHERE branch_id=?
                    UNION
                    SELECT business_date FROM daily_expenses WHERE branch_id=?
                    UNION
                    SELECT DATE(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 15 HOUR) as business_date FROM orders WHERE branch_id=? AND status='served'
                ) days
                WHERE YEAR(days.business_date)=? AND MONTH(days.business_date)=?
                ORDER BY days.business_date DESC
            ", [$branch_id, $branch_id, $branch_id, $branch_id, $branch_id, $branch_id, $branch_id, $branch_id, $selected_year, $selected_month]);

            // The active shift stays in the live panel. It becomes part of Files/Sales Archive after closing at 2:00 AM.
            if ($business_open) {
                $sales_archive = array_values(array_filter($sales_archive, fn ($row) => $row['business_date'] !== $today));
            }

            $archive_expenses = Pos::all("
                SELECT id, business_date, amount, note, created_at
                FROM daily_expenses
                WHERE branch_id=? AND YEAR(business_date)=? AND MONTH(business_date)=?
                ORDER BY business_date DESC, created_at ASC, id ASC
            ", [$branch_id, $selected_year, $selected_month]);

            $expenses_by_day = [];
            foreach ($archive_expenses as $expense) {
                $expenses_by_day[$expense['business_date']][] = $expense;
            }

            foreach ($sales_archive as &$row) {
                $row['total_money'] = (float)$row['petty_cash'] + (float)$row['total_sales'] - (float)$row['expense'];
                $row['expenses'] = $expenses_by_day[$row['business_date']] ?? [];
            }
            unset($row);

            $bar_data = array_map(fn ($r) => ['d' => $r['business_date'], 'sales' => $r['total_sales']], array_reverse($sales_archive));
            $section_stats = [
                'total' => count($sales_archive),
                'sum' => array_sum(array_column($sales_archive, 'total_sales')),
                'highest' => $sales_archive ? max(array_column($sales_archive, 'total_sales')) : 0,
                'lowest' => $sales_archive ? min(array_column($sales_archive, 'total_sales')) : 0,
            ];
        } elseif ($sidebar_open && $selected_section) {
            $status_filter = match ($selected_section) {
                'served' => "'served'",
                'voids'  => "'voided'",
                default  => "'pending','served','voided'",
            };
            $order_by = $selected_section === 'served' ? 'o.served_at DESC' : 'o.created_at DESC';

            $section_orders = Pos::localizeRows(Pos::all("
                SELECT o.id, o.beeper_number, o.customer_name,
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
                SELECT $BIZ_DATE as d, COALESCE(SUM(total),0) as sales
                FROM orders
                WHERE {$BIZ_YEAR}=? AND {$BIZ_MONTH}=? AND branch_id = $branch_id AND status IN ($status_filter)
                GROUP BY $BIZ_DATE ORDER BY d
            ", [$selected_year, $selected_month]);
        }

        $daily_json   = json_encode($daily_data);
        $weekly_json  = json_encode($weekly_raw);
        $monthly_json = json_encode($monthly_trend);
        $bar_json     = json_encode($bar_data);

        return view('admin.statistics.index', $shared + compact(
            'today', 'business_open', 'today_data', 'today_cash_record', 'today_expenses', 'today_expense_total', 'today_total_money', 'monthly', 'best_items', 'least_items', 'img_map',
            'daily_data', 'weekly_raw', 'monthly_trend',
            'annual_by_month', 'annual_total', 'voids_by_month', 'sales_days_by_month', 'sales_archive',
            'section_orders', 'section_stats', 'high_date', 'low_date', 'bar_data',
            'daily_json', 'weekly_json', 'monthly_json', 'bar_json',
            'selected_year', 'selected_month', 'selected_section', 'sidebar_open',
            'chart_year', 'chart_month', 'months_list', 'year_range'
        ));
    }
}
