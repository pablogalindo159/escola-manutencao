<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained()->onDelete('cascade');
            $table->string('photo_url');
            $table->enum('stage', ['before', 'during', 'after', 'diagnostic'])->default('before');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('repair_id');
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_photos');
    }
};
