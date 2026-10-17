<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // Set for paid enrollments; null for free courses.
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            // Resume point for the course player.
            $table->foreignId('last_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
