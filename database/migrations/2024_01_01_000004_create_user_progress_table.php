<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('watched_seconds')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id', 'video_id']);
            $table->index('user_id');
            $table->index('course_id');
            $table->index('is_completed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
