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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('full_name')->nullable()->after('last_name');
            $table->renameColumn('avatar', 'avatar_url');
            $table->json('skin_concerns')->nullable()->after('skin_type');
            $table->json('beauty_preferences')->nullable()->after('skin_concerns');
            $table->enum('loyalty_tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze')->after('loyalty_points');
            $table->timestamp('last_login_at')->nullable()->after('updated_at');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->string('google_id')->unique()->nullable()->after('is_verified');
            $table->string('apple_id')->unique()->nullable()->after('google_id');
            $table->string('facebook_id')->unique()->nullable()->after('apple_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'full_name',
                'skin_concerns',
                'beauty_preferences',
                'loyalty_tier',
                'last_login_at',
                'is_verified',
                'google_id',
                'apple_id',
                'facebook_id'
            ]);
            $table->renameColumn('avatar_url', 'avatar');
        });
    }
};
