<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name', 100)->nullable()->after('beeper_number');
            }
            if (!Schema::hasColumn('orders', 'created_by_user_id')) {
                $table->unsignedInteger('created_by_user_id')->nullable()->index()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('orders', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
        });
    }
};
