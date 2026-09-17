<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('certificate_number')->unique();
            $table->timestamp('issued_at');
            $table->string('certificate_url')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->decimal('grade', 3, 2)->nullable();
            $table->text('qr_code_data')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('course_id');
            $table->index('certificate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
