<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original pos_system.sql schema one-to-one.
 *
 * Every table is guarded with hasTable(), so this works both ways:
 *   - fresh database  -> `php artisan migrate` creates everything
 *   - you already imported pos_system.sql -> `php artisan migrate` leaves the tables alone
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->unique('uq_menu_name');
                $table->decimal('price', 10, 2);
                $table->decimal('discounted_price', 10, 2)->nullable();
                $table->string('category', 50);
                $table->string('image', 255)->nullable();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('full_name', 100)->default('Admin');
                $table->string('branch_name', 120)->default('Main Branch');
                $table->string('username', 50)->unique('uq_username');
                $table->string('email', 100)->default('');
                $table->string('phone', 20)->nullable()->default('');
                $table->string('status', 20)->default('active');
                $table->string('avatar', 255)->nullable()->default('');
                $table->string('password', 255);
                $table->enum('role', ['admin', 'cashier'])->default('admin');
                $table->integer('branch_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('branch_id')->default(1)->index('idx_branch_id');
                $table->integer('beeper_number')->nullable();
                $table->string('order_type', 20)->default('dine-in');
                $table->string('payment_method', 20)->default('cash');
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->decimal('change_amount', 10, 2)->default(0);
                $table->string('gcash_reference', 100)->nullable();
                $table->string('gcash_reference_extra', 100)->nullable();
                $table->decimal('gcash_extra_amount', 10, 2)->nullable()->default(0);
                $table->decimal('refund_amount', 10, 2)->nullable()->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamp('created_at')->useCurrent();   // UTC
                $table->dateTime('served_at')->nullable();       // UTC
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('order_id');
                $table->integer('menu_item_id')->nullable();
                $table->string('name', 100);
                $table->decimal('price', 10, 2);
                $table->integer('quantity')->default(1);

                $table->foreign('order_id', 'order_items_ibfk_1')
                    ->references('id')->on('orders')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->increments('id');
                $table->string('email', 255)->index();
                $table->string('token', 64);        // SHA-256 of the emailed token
                $table->dateTime('expires_at');     // UTC
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('receipt_settings')) {
            Schema::create('receipt_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('store_name', 120)->default('Twist & Roll');
                $table->string('logo_path', 255)->default('assets/images/logo.png');
                $table->string('store_address', 255)->default('');
                $table->string('store_contact', 60)->default('');
                $table->string('fb_page_url', 255)->default('');
                $table->string('store_tin', 60)->default('');
                $table->string('receipt_header', 255)->default('');
                $table->string('receipt_footer', 255)->default('Thank you for dining with us!');
                $table->boolean('show_discount')->default(true);
                $table->boolean('show_cashier')->default(true);
                $table->boolean('show_order_type')->default(true);
                $table->boolean('show_payment_type')->default(true);
                $table->boolean('show_beeper')->default(true);
                $table->boolean('dark_mode')->default(false);
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_settings');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('users');
        Schema::dropIfExists('menu_items');
    }
};
