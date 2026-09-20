<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se a tabela não existir ainda, criar
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Adicionar valor padrão do método de pagamento
        \App\Models\Setting::updateOrCreate(
            ['key' => 'mercado_pago_payment_method'],
            ['value' => 'pix_transparente']
        );
    }

    public function down(): void
    {
        \App\Models\Setting::where('key', 'mercado_pago_payment_method')->delete();
    }
};
