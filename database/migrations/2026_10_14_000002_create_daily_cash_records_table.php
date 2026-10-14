<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cash_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->date('business_date');
            $table->decimal('petty_cash', 12, 2)->default(0);
            $table->decimal('expense', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'business_date']);
            $table->index('business_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cash_records');
    }
};
