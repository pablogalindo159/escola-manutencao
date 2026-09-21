<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Alterar coluna 'method' de ENUM para string
     * Motivo: suportar novos tipos como 'pix_transparente'
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', [
                'credit_card',
                'debit_card',
                'pix',
                'boleto',
                'wallet'
            ])->nullable()->change();
        });
    }
};
