<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Shown to the instructor in the course builder ("intro.mp4 · 48 MB").
            $table->string('video_original_name')->nullable()->after('video_path');
            $table->unsignedBigInteger('video_size')->nullable()->after('video_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_original_name', 'video_size']);
        });
    }
};
