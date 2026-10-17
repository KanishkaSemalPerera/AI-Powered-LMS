<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            // Map of question_id => chosen option index.
            $table->json('answers');
            $table->unsignedInteger('correct_count');
            $table->unsignedInteger('total_questions');
            $table->unsignedTinyInteger('score_percent');
            $table->boolean('passed');
            $table->timestamps();

            $table->index(['user_id', 'quiz_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
