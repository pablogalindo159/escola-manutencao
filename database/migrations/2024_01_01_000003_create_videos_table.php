<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->integer('duration_seconds')->default(0);
            $table->integer('order')->default(0);
            $table->enum('quality', ['480p', '720p', '1080p'])->default('720p');
            $table->string('s3_key')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('material_url')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();

            $table->index('course_id');
            $table->index('order');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
