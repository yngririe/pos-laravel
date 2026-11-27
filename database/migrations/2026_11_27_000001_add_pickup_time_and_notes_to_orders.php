<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Clock time (Manila) the customer will collect a Pick Up order.
            if (!Schema::hasColumn('orders', 'pickup_time')) {
                $table->time('pickup_time')->nullable()->after('customer_name');
            }
            // Optional free-text note for any order type.
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->string('notes', 255)->nullable()->after('pickup_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('orders', 'pickup_time')) {
                $table->dropColumn('pickup_time');
            }
        });
    }
};
