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
        // Product search and filtering indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('price');
            $table->index('rating');
            $table->index('is_featured');
            $table->index('is_active');
        });

        // Order management indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });

        // User data indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('is_active');
        });

        // Cart and wishlist indexes
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('cart_id');
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->index('wishlist_id');
        });

        // Reviews indexes
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('user_id');
            $table->index('rating');
        });

        // Full-text search indexes (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT ft_products_name_desc (name, description)');
            DB::statement('ALTER TABLE categories ADD FULLTEXT ft_categories_name (name)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products DROP INDEX ft_products_name_desc');
            DB::statement('ALTER TABLE categories DROP INDEX ft_categories_name');
        }

        // Drop regular indexes
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex(['rating']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropIndex(['wishlist_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['email']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['price']);
        });
    }
};