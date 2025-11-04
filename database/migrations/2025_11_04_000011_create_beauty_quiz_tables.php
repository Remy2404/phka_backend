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
        Schema::create('beauty_quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('skin_type_focus', ['all', 'dry', 'oily', 'combination', 'normal', 'sensitive'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('beauty_quizzes')->onDelete('cascade');
            $table->text('question');
            $table->enum('question_type', ['single_choice', 'multiple_choice', 'text_input'])->default('single_choice');
            $table->json('options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained('beauty_quizzes')->onDelete('cascade');
            $table->json('answers');
            $table->json('recommendations')->nullable();
            $table->enum('skin_type_result', ['dry', 'oily', 'combination', 'normal', 'sensitive'])->nullable();
            $table->timestamp('completed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('beauty_quizzes');
    }
};