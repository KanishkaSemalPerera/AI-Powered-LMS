<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            // Usage logs survive user deletion for cost reporting.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('feature', ['quiz_generator', 'study_assistant', 'description_helper']);
            // What the request was about, e.g. a Lesson or Course.
            $table->nullableMorphs('subject');
            $table->string('model', 100);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->enum('status', ['success', 'error']);
            $table->string('error_message', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'feature', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
