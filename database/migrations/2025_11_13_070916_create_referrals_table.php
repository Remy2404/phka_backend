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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');
            $table->string('referred_email');
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('referral_code')->unique();
            $table->enum('status', ['pending', 'registered', 'completed'])->default('pending');
            $table->integer('referrer_reward_points')->default(0);
            $table->integer('referred_reward_points')->default(0);
            $table->boolean('referrer_reward_given')->default(false);
            $table->boolean('referred_reward_given')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
