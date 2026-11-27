<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Per-item service type for Dine in / Take out orders ('dine-in' | 'take-out').
            // NULL = follow the order's own type (delivery, pick up and all older orders).
            if (!Schema::hasColumn('order_items', 'order_type')) {
                $table->string('order_type', 20)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });
    }
};
