<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->integer('likes_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->enum('status', ['published', 'draft', 'archived'])->default('published');
            $table->timestamps();

            $table->index('user_id');
            $table->index('course_id');
            $table->index('is_pinned');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
