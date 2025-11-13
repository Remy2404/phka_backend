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
        Schema::table('product_variants', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['name', 'price', 'sale_price', 'attributes', 'image']);
            
            // Add new columns to match schema
            $table->string('variant_type')->after('product_id'); // 'size', 'color', 'scent', 'volume'
            $table->string('variant_value')->after('variant_type'); // '30ml', 'Rose', 'Red', etc.
            $table->decimal('price_modifier', 8, 2)->default(0.0)->after('variant_value');
            $table->string('color_hex')->nullable()->after('stock_quantity'); // for color variants
            $table->string('image_url')->nullable()->after('color_hex');
            $table->boolean('is_available')->default(true)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['variant_type', 'variant_value', 'price_modifier', 'color_hex', 'image_url', 'is_available']);
            
            // Restore old columns
            $table->string('name')->after('product_id');
            $table->decimal('price', 10, 2)->nullable()->after('sku');
            $table->decimal('sale_price', 10, 2)->nullable()->after('price');
            $table->json('attributes')->after('stock_quantity');
            $table->string('image')->nullable()->after('attributes');
        });
    }
};
