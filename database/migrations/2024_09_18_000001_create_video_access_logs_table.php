<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('action', 30); // stream_url_requested, stream_played, access_denied, blocked
            $table->timestamps();

            $table->index('video_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_access_logs');
    }
};
