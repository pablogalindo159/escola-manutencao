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
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('thumbnail_url')->nullable();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('type', ['free', 'paid'])->default('paid');
            $table->integer('duration_minutes')->default(0);
            $table->string('category')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->decimal('rating', 3, 2)->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestamps();

            $table->index('slug');
            $table->index('instructor_id');
            $table->index('category');
            $table->index('status');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
