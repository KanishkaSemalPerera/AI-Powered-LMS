<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // One quiz per lesson.
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            // Minimum percentage needed to pass.
            $table->unsignedTinyInteger('pass_mark')->default(70);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
