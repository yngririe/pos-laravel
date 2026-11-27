<?php

namespace App\Http\Controllers;

use App\Support\Pos;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('pages.statistics', $shared + compact(
            'today', 'business_open', 'today_data', 'today_cash_record', 'today_expenses', 'today_expense_total', 'today_total_money', 'monthly', 'best_items', 'least_items', 'img_map',
            'daily_data', 'weekly_raw', 'monthly_trend',
            'annual_by_month', 'annual_total', 'voids_by_month', 'sales_days_by_month', 'sales_archive',
            'section_orders', 'section_stats', 'high_date', 'low_date', 'bar_data',
            'daily_json', 'weekly_json', 'monthly_json', 'bar_json',
            'selected_year', 'selected_month', 'selected_section', 'sidebar_open',
            'chart_year', 'chart_month', 'months_list', 'year_range'
        ));
    }

    /** Save or update the starting petty cash for one business day. */
    public function saveDailyCash(Request $request)
    {
        $data = $request->validate([
            'business_date' => ['required', 'date'],
            'petty_cash' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $nowPht = Carbon::now(Pos::TZ);
        $businessOpen = $nowPht->hour >= 15 || $nowPht->hour < 5;
        $activeDate = ($nowPht->hour < 5 ? $nowPht->copy()->subDay() : $nowPht)->format('Y-m-d');
        if (!$businessOpen || $data['business_date'] !== $activeDate) {
            return back()->withErrors(['business_day' => 'Petty cash can only be changed during the active business hours (3:00 PM–5:00 AM).']);
        }

        $key = [
            'branch_id' => Pos::branchId(),
            'business_date' => $data['business_date'],
        ];

        $amountToAdd = round((float) $data['petty_cash'], 2);

        $existing = DB::table('daily_cash_records')
            ->where($key)
            ->first();

        if ($existing) {
            DB::table('daily_cash_records')
                ->where($key)
                ->update([
                    'petty_cash' => (float) $existing->petty_cash + $amountToAdd,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('daily_cash_records')->insert([
                ...$key,
                'petty_cash' => $amountToAdd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('cash_record_saved', true);
    }

    /** Add another expense to the current business day. Expenses are intentionally separate rows. */
    public function addExpense(Request $request)
    {
        $data = $request->validate([
            'business_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $nowPht = Carbon::now(Pos::TZ);
        $businessOpen = $nowPht->hour >= 15 || $nowPht->hour < 5;
        $activeDate = ($nowPht->hour < 5 ? $nowPht->copy()->subDay() : $nowPht)->format('Y-m-d');
        if (!$businessOpen || $data['business_date'] !== $activeDate) {
            return back()->withErrors(['business_day' => 'Expenses can only be added during the active business hours (3:00 PM–5:00 AM).']);
        }

        DB::table('daily_expenses')->insert([
            'branch_id' => Pos::branchId(),
            'business_date' => $data['business_date'],
            'amount' => round((float)$data['amount'], 2),
            'note' => trim($data['note']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('expense_added', true);
    }
    /** Update an expense from the current active business day. */
        public function updateExpense(Request $request, int $expense)
        {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
                'note' => ['required', 'string', 'max:255'],
            ]);

            $nowPht = Carbon::now(Pos::TZ);
            $businessOpen = $nowPht->hour >= 15 || $nowPht->hour < 5;

            $activeDate = (
                $nowPht->hour < 5
                    ? $nowPht->copy()->subDay()
                    : $nowPht
            )->format('Y-m-d');

            if (!$businessOpen) {
                return back()->withErrors([
                    'business_day' => 'Expenses can only be edited during the active business hours (3:00 PM–5:00 AM).'
                ]);
            }

            $existing = DB::table('daily_expenses')
                ->where('id', $expense)
                ->where('branch_id', Pos::branchId())
                ->where('business_date', $activeDate)
                ->first();

            if (!$existing) {
                return back()->withErrors([
                    'expense' => 'Expense not found or it no longer belongs to the active business day.'
                ]);
            }

            DB::table('daily_expenses')
                ->where('id', $expense)
                ->where('branch_id', Pos::branchId())
                ->update([
                    'amount' => round((float) $data['amount'], 2),
                    'note' => trim($data['note']),
                    'updated_at' => now(),
                ]);

            return back()->with('expense_updated', true);
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
        $orderCols = "o.id, o.branch_id, o.beeper_number, o.customer_name, o.order_type, o.payment_method,
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
            SELECT YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 15 HOUR, 1) AS yw,
                   MIN($BIZ_DATE) AS week_start,
                   MAX($BIZ_DATE) AS week_end,
                   COUNT(*) AS total_orders,
                   COUNT(CASE WHEN status='served' THEN 1 END) AS served,
                   COUNT(CASE WHEN status='voided' THEN 1 END) AS voided,
                   COALESCE(SUM(CASE WHEN status='served' THEN total    ELSE 0 END),0) AS total_sales,
                   COALESCE(SUM(CASE WHEN status='served' THEN discount ELSE 0 END),0) AS total_discounts
            FROM orders
            WHERE $wWhere AND status IN ('served','voided')
            GROUP BY YEARWEEK(CONVERT_TZ(created_at,'+00:00','+08:00') - INTERVAL 15 HOUR, 1)
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
            $branchCols = "o.id, o.beeper_number, o.customer_name, o.order_type, o.payment_method,
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
