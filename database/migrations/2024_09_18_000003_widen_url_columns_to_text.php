<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Campos de URL limitados a 255 caracteres causavam erro ao salvar
     * links reais (ex: URLs de busca/CDN com parâmetros longos). Usamos
     * SQL puro em vez de ->change() pra não depender do pacote
     * doctrine/dbal (que não está instalado no projeto).
     */
    public function up(): void
    {
        $columns = [
            'users' => ['avatar_url'],
            'courses' => ['thumbnail_url'],
            'videos' => ['video_url', 'thumbnail_url', 'material_url'],
            'repair_photos' => ['photo_url'],
            'certificates' => ['certificate_url'],
            'live_streams' => ['youtube_stream_url', 'youtube_chat_url', 'thumbnail_url'],
        ];

        foreach ($columns as $table => $fields) {
            foreach ($fields as $column) {
                DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$column}\" TYPE TEXT");
            }
        }
    }

    public function down(): void
    {
        // Não revertemos para varchar(255) - reduzir o tamanho poderia
        // truncar dados já salvos.
    }
};
