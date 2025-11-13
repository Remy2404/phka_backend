<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Update status enum to match schema
            $table->enum('status', ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'refunded', 'failed'])->default('pending')->change();
            
            // Add missing fields
            $table->integer('loyalty_points_used')->default(0)->after('discount_amount');
            $table->decimal('loyalty_discount', 10, 2)->default(0.00)->after('loyalty_points_used');
            $table->string('transaction_id')->nullable()->after('payment_status');
            $table->string('shipping_method')->nullable()->after('shipping_address_id');
            $table->string('tracking_number')->nullable()->after('shipping_method');
            $table->string('carrier')->nullable()->after('tracking_number');
            $table->date('estimated_delivery')->nullable()->after('carrier');
            $table->text('customer_notes')->nullable()->after('notes');
            $table->text('admin_notes')->nullable()->after('customer_notes');
            $table->integer('loyalty_points_earned')->default(0)->after('admin_notes');
            $table->timestamp('confirmed_at')->nullable()->after('ordered_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_points_used',
                'loyalty_discount',
                'transaction_id',
                'shipping_method',
                'tracking_number',
                'carrier',
                'estimated_delivery',
                'customer_notes',
                'admin_notes',
                'loyalty_points_earned',
                'confirmed_at',
                'cancelled_at'
            ]);
            // Revert status enum
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending')->change();
        });
    }
};
