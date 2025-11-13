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
        // Update quiz_questions table
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
            $table->enum('question_type', ['single_choice', 'multiple_choice', 'scale', 'text'])->default('single_choice')->change();
            $table->string('category')->nullable()->after('question_type');
            $table->text('help_text')->nullable()->after('category');
            $table->boolean('is_required')->default(true)->after('sort_order');
            $table->boolean('is_active')->default(true)->after('is_required');
        });

        // Update quiz_results table
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn(['quiz_id', 'answers', 'skin_type_result']);
            $table->string('skin_type')->nullable()->after('user_id');
            $table->json('skin_concerns')->nullable()->after('skin_type');
            $table->json('beauty_goals')->nullable()->after('skin_concerns');
            $table->json('preferences')->nullable()->after('beauty_goals');
            $table->json('recommended_products')->nullable()->after('preferences');
            $table->text('recommended_routine')->nullable()->after('recommended_products');
        });

        // Create quiz_answers table
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_result_id')->constrained('quiz_results')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('quiz_questions')->onDelete('cascade');
            $table->text('answer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');

        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropColumn(['skin_type', 'skin_concerns', 'beauty_goals', 'preferences', 'recommended_products', 'recommended_routine']);
            $table->foreignId('quiz_id')->constrained('beauty_quizzes')->onDelete('cascade')->after('user_id');
            $table->json('answers')->after('quiz_id');
            $table->enum('skin_type_result', ['dry', 'oily', 'combination', 'normal', 'sensitive'])->nullable()->after('recommendations');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn(['category', 'help_text', 'is_required', 'is_active']);
            $table->enum('question_type', ['single_choice', 'multiple_choice', 'text_input'])->default('single_choice')->change();
            $table->foreignId('quiz_id')->constrained('beauty_quizzes')->onDelete('cascade')->after('id');
        });
    }
};
