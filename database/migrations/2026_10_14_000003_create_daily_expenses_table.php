<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->date('business_date');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->index(['branch_id', 'business_date']);
        });

        // Preserve any expense entered using the previous one-expense-per-day version.
        if (Schema::hasTable('daily_cash_records') && Schema::hasColumn('daily_cash_records', 'expense')) {
            foreach (DB::table('daily_cash_records')->where('expense', '>', 0)->get() as $row) {
                DB::table('daily_expenses')->insert([
                    'branch_id' => $row->branch_id,
                    'business_date' => $row->business_date,
                    'amount' => $row->expense,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_expenses');
    }
};
