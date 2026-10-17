<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            // Stored alongside section_id so access checks (is the user enrolled in this
            // lesson's course?) need no extra join.
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->enum('type', ['video', 'text'])->default('video');
            $table->string('video_path')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['section_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
