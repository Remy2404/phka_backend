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
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('query');
            $table->json('filters')->nullable();
            $table->integer('result_count')->nullable();
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->timestamp('searched_at');
        });

        Schema::create('trending_searches', function (Blueprint $table) {
            $table->id();
            $table->string('query')->unique();
            $table->integer('search_count')->default(1);
            $table->timestamp('last_searched_at');
            $table->timestamps();
        });

        // Update existing price_alerts table
        // Schema::table('price_alerts', function (Blueprint $table) {
        //     $table->decimal('current_price', 10, 2)->nullable()->after('target_price');
        //     $table->boolean('is_triggered')->default(false)->after('current_price');
        //     $table->boolean('is_notified')->default(false)->after('is_triggered');
        //     $table->timestamp('triggered_at')->nullable()->after('updated_at');
        //     $table->timestamp('notified_at')->nullable()->after('triggered_at');
        // });

        // Update existing recently_viewed table - remove unique constraint and timestamps
        // Schema::table('recently_viewed', function (Blueprint $table) {
        //     $table->dropUnique('unique_recent_view');
        //     $table->dropColumn('created_at');
        //     $table->dropColumn('updated_at');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('recently_viewed', function (Blueprint $table) {
        //     $table->timestamps();
        //     $table->unique(['user_id', 'product_id'], 'unique_recent_view');
        // });

        // Schema::table('price_alerts', function (Blueprint $table) {
        //     $table->dropColumn(['current_price', 'is_triggered', 'is_notified', 'triggered_at', 'notified_at']);
        // });

        Schema::dropIfExists('trending_searches');
        Schema::dropIfExists('search_history');
    }
};
