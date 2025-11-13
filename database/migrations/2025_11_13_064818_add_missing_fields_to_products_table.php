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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null')->after('category_id');
            $table->dropColumn('brand'); // Remove the old brand string column
            $table->decimal('base_price', 10, 2)->after('price');
            $table->decimal('original_price', 10, 2)->nullable()->after('base_price');
            $table->boolean('is_on_sale')->default(false)->after('original_price');
            $table->integer('discount_percentage')->default(0)->after('is_on_sale');
            $table->string('barcode')->nullable()->after('sku');
            $table->text('ingredients')->nullable()->after('dimensions');
            $table->text('how_to_use')->nullable()->after('ingredients');
            $table->text('benefits')->nullable()->after('how_to_use');
            $table->text('warnings')->nullable()->after('benefits');
            $table->json('skin_types')->nullable()->after('warnings');
            $table->json('skin_concerns')->nullable()->after('skin_types');
            $table->boolean('is_vegan')->default(false)->after('skin_concerns');
            $table->boolean('is_cruelty_free')->default(false)->after('is_vegan');
            $table->boolean('is_organic')->default(false)->after('is_cruelty_free');
            $table->boolean('is_paraben_free')->default(false)->after('is_organic');
            $table->boolean('is_sulfate_free')->default(false)->after('is_paraben_free');
            $table->integer('view_count')->default(0)->after('review_count');
            $table->integer('purchase_count')->default(0)->after('view_count');
            $table->boolean('is_new_arrival')->default(false)->after('is_featured');
            $table->boolean('is_best_seller')->default(false)->after('is_new_arrival');
            $table->boolean('is_limited_edition')->default(false)->after('is_best_seller');
            $table->timestamp('published_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn([
                'brand_id',
                'base_price',
                'original_price',
                'is_on_sale',
                'discount_percentage',
                'barcode',
                'ingredients',
                'how_to_use',
                'benefits',
                'warnings',
                'skin_types',
                'skin_concerns',
                'is_vegan',
                'is_cruelty_free',
                'is_organic',
                'is_paraben_free',
                'is_sulfate_free',
                'view_count',
                'purchase_count',
                'is_new_arrival',
                'is_best_seller',
                'is_limited_edition',
                'published_at'
            ]);
            $table->string('brand')->nullable()->after('category_id');
        });
    }
};
