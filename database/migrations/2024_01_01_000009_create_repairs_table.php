<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('equipment_type');
            $table->string('customer_name')->nullable();
            $table->text('defect_description');
            $table->text('diagnosis')->nullable();
            $table->json('measurements')->nullable();
            $table->json('components_replaced')->nullable();
            $table->text('solution')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending_review', 'reviewed', 'approved', 'rejected'])->default('draft');
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('instructor_feedback')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('course_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
