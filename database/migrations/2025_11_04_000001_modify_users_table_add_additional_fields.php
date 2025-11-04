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
            $table->string('phone')->nullable()->after('password');
            $table->string('avatar')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('avatar');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('birth_date');
            $table->enum('skin_type', ['dry', 'oily', 'combination', 'normal', 'sensitive'])->nullable()->after('gender');
            $table->integer('loyalty_points')->default(0)->after('skin_type');
            $table->boolean('is_active')->default(true)->after('loyalty_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar', 'birth_date', 'gender', 'skin_type', 'loyalty_points', 'is_active']);
        });
    }
};