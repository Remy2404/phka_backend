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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_shipping']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('minimum_order', 10, 2)->default(0.00);
            $table->decimal('maximum_discount', 10, 2)->nullable();
            $table->json('applicable_categories')->nullable();
            $table->json('applicable_products')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_user')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promo_code_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_applied', 10, 2);
            $table->timestamp('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code_usage');
        Schema::dropIfExists('promo_codes');
    }
};
