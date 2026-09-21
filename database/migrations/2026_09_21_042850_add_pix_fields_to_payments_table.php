<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('mercado_pago_order_id')->nullable()->unique()->comment('Order ID do Mercado Pago');
            $table->string('external_reference')->nullable()->unique()->comment('Referência externa = payments.id');
            $table->text('qr_code')->nullable()->comment('Código PIX copiável (copy & paste)');
            $table->longText('qr_code_base64')->nullable()->comment('QR Code em base64 (imagem)');
            $table->index('mercado_pago_order_id');
            $table->index('external_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'mercado_pago_order_id',
                'external_reference',
                'qr_code',
                'qr_code_base64',
            ]);
            $table->dropIndex('payments_mercado_pago_order_id_index');
            $table->dropIndex('payments_external_reference_index');
        });
    }
};
