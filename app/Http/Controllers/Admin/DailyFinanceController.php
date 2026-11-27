<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Pos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only. The petty cash and expenses shown on the live Statistics panel
 * for the current business day (3:00 PM -> 2:00 AM Manila time).
 */
class DailyFinanceController extends Controller
{
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
}
