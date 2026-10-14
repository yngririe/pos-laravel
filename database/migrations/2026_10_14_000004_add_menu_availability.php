<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('menu_items') && !Schema::hasColumn('menu_items', 'is_active')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'is_active')) {
            Schema::table('menu_items', fn (Blueprint $table) => $table->dropColumn('is_active'));
        }
    }
};
