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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->onDelete('cascade');
            $table->enum('theme', ['light', 'dark', 'system'])->default('system');
            $table->string('language', 10)->default('en');
            $table->string('currency', 3)->default('USD');
            $table->boolean('allow_personalized_ads')->default(true);
            $table->boolean('allow_analytics')->default(true);
            $table->boolean('allow_location')->default(false);
            $table->boolean('newsletter_subscribed')->default(false);
            $table->boolean('sms_notifications')->default(false);
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('app_settings');
    }
};
