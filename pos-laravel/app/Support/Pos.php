<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Shared helpers for the POS pages.
 *
 * TIME: every timestamp is stored in UTC (created_at, served_at, reset tokens).
 * Anything shown to the user or bucketed into a "business day" is converted to
 * Asia/Manila. The business day starts at 5:00 AM Manila time.
 */
class Pos
{
    public const TZ = 'Asia/Manila';

    // ── Business-day SQL fragments (column is stored in UTC) ───────────────
    public static function bizDate(string $col = 'created_at'): string
    {
        return "DATE(CONVERT_TZ($col,'+00:00','+08:00') - INTERVAL 5 HOUR)";
    }

    public static function bizYear(string $col = 'created_at'): string
    {
        return "YEAR(CONVERT_TZ($col,'+00:00','+08:00') - INTERVAL 5 HOUR)";
    }

    public static function bizMonth(string $col = 'created_at'): string
    {
        return "MONTH(CONVERT_TZ($col,'+00:00','+08:00') - INTERVAL 5 HOUR)";
    }

    // ── Branch / user context ──────────────────────────────────────────────
    /** Admins own their branch (branch_id = own id when unset); cashiers inherit their admin's. */
    public static function branchId(): int
    {
        $user = Auth::user();
        return (int) ($user->branch_id ?: $user->id);
    }

    public static function branchName(): string
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return $user->branch_name ?: 'Main Branch';
        }
        return DB::table('users')->where('id', self::branchId())->value('branch_name') ?: 'Main Branch';
    }

    /** Variables every authenticated page template expects. */
    public static function shared(string $currentPage): array
    {
        $user = Auth::user();

        return [
            'base_url'     => rtrim(url('/'), '/') . '/',
            'current_page' => $currentPage,
            'branch_id'    => self::branchId(),
            'branch_name'  => self::branchName(),
            'role'         => $user->role,
            'my_user_id'   => $user->id,
            'nav_user'     => ['avatar' => $user->avatar],
            'dark_mode'    => (int) (DB::table('receipt_settings')->where('id', 1)->value('dark_mode') ?: 0),
        ];
    }

    /** Menu items + the price => discount-amount map the front-end uses. */
    public static function menu(): array
    {
        $items = array_map(function ($row) {
            // DECIMAL arrives as "229.00"; cast so it matches the DISCOUNT_MAP keys.
            $row['price'] = (int) round($row['price']);
            return $row;
        }, self::all('SELECT id, name, price, category, image FROM menu_items ORDER BY id'));

        $discountMap = [];
        foreach (self::all('SELECT price, discounted_price FROM menu_items WHERE discounted_price IS NOT NULL') as $row) {
            $discountMap[(int) round($row['price'])] = (int) round($row['price'] - $row['discounted_price']);
        }

        return [$items, $discountMap];
    }

    // ── PDO-style query helpers (keep the legacy templates' array syntax) ──
    public static function all(string $sql, array $bindings = []): array
    {
        return array_map(fn ($r) => (array) $r, DB::select($sql, $bindings));
    }

    public static function one(string $sql, array $bindings = []): ?array
    {
        $rows = self::all($sql, $bindings);
        return $rows[0] ?? null;
    }

    public static function val(string $sql, array $bindings = [])
    {
        $row = self::one($sql, $bindings);
        return $row ? array_values($row)[0] : null;
    }

    // ── Time helpers ───────────────────────────────────────────────────────
    public static function nowUtc(): string
    {
        return Carbon::now('UTC')->format('Y-m-d H:i:s');
    }

    /** UTC DB string -> Manila local "Y-m-d H:i:s" (null stays null). */
    public static function local(?string $utc): ?string
    {
        if (!$utc) {
            return null;
        }
        return Carbon::parse($utc, 'UTC')->timezone(self::TZ)->format('Y-m-d H:i:s');
    }

    /** Convert created_at / served_at on each row to Manila local strings. */
    public static function localizeRows(array $rows, array $columns = ['created_at', 'served_at']): array
    {
        foreach ($rows as &$row) {
            foreach ($columns as $col) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = self::local($row[$col]);
                }
            }
        }
        unset($row);
        return $rows;
    }

    // ── Password rule shared by profile / cashier / branch / reset ─────────
    public static function passwordError(string $password, string $confirm): ?string
    {
        if (strlen($password) < 8)              return 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password))  return 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password))  return 'Password must contain at least one number.';
        if (!preg_match('/[\W_]/', $password))  return 'Password must contain at least one special character.';
        if ($password !== $confirm)             return 'Passwords do not match.';
        return null;
    }
}
