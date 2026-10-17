<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            // Restrict: an instructor with courses must be deactivated, not deleted.
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->string('subtitle', 255)->nullable();
            $table->longText('description')->nullable();
            $table->json('what_you_will_learn')->nullable();
            $table->json('requirements')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('beginner');
            $table->string('language', 50)->default('English');
            $table->enum('status', ['draft', 'pending', 'published', 'rejected'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_featured')->default(false);

            // Denormalised aggregates, kept in sync by the app, so catalogue sorting stays fast.
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('students_count')->default(0);

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['status', 'is_featured']);
            $table->index('price');
            $table->index('rating_avg');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
