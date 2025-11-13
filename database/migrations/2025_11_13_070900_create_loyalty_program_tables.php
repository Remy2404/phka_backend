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
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('points_required');
            $table->enum('reward_type', ['discount', 'free_shipping', 'free_product', 'voucher']);
            $table->json('reward_value'); // JSON with reward-specific data
            $table->integer('stock_quantity')->nullable();
            $table->integer('usage_limit_per_user')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['earn', 'redeem', 'expire', 'refund', 'bonus']);
            $table->integer('points');
            $table->string('source_type')->nullable(); // 'purchase', 'review', 'referral', etc.
            $table->integer('source_id')->nullable(); // order_id, review_id, etc.
            $table->integer('points_before');
            $table->integer('points_after');
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('redeemed_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reward_id')->constrained('loyalty_rewards')->onDelete('restrict');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('points_spent');
            $table->string('reward_code')->unique()->nullable();
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redeemed_rewards');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_rewards');
    }
};
