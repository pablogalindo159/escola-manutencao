<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            
            // Professor que vai transmitir
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Informações básicas
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null'); // Qual curso é
            
            // YouTube
            $table->string('youtube_video_id')->nullable(); // ID do vídeo
            $table->string('youtube_stream_url')->nullable(); // URL do stream
            $table->string('youtube_chat_url')->nullable(); // URL do chat
            
            // Status
            $table->enum('status', ['scheduled', 'live', 'ended', 'archived'])->default('scheduled');
            
            // Datas
            $table->dateTime('scheduled_at'); // Quando vai transmitir
            $table->dateTime('started_at')->nullable(); // Quando começou
            $table->dateTime('ended_at')->nullable(); // Quando terminou
            
            // Estatísticas
            $table->integer('viewers_count')->default(0); // Quantos assistindo
            $table->integer('total_viewers')->default(0); // Total que assistiu
            $table->integer('likes')->default(0);
            
            // Configurações
            $table->boolean('allow_chat')->default(true);
            $table->boolean('recorded')->default(true); // Salvar gravação?
            $table->string('thumbnail_url')->nullable();
            
            // Admin
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};
