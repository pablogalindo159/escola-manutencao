<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * PIX Transparente + Checkout Pro - Consolidação de campos Mercado Pago
     * Remove duplicatas de versões anteriores
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Adicionar colunas PIX se não existirem
            if (!Schema::hasColumn('payments', 'mercado_pago_order_id')) {
                $table->string('mercado_pago_order_id')
                    ->nullable()
                    ->unique()
                    ->index()
                    ->after('mercado_pago_preference_id')
                    ->comment('Order ID do Mercado Pago (PIX Transparente)');
            }

            if (!Schema::hasColumn('payments', 'external_reference')) {
                $table->string('external_reference')
                    ->nullable()
                    ->unique()
                    ->index()
                    ->after('mercado_pago_order_id')
                    ->comment('External Reference para tracking de Webhook');
            }

            if (!Schema::hasColumn('payments', 'qr_code')) {
                $table->text('qr_code')
                    ->nullable()
                    ->after('external_reference')
                    ->comment('QR Code string (PIX)');
            }

            if (!Schema::hasColumn('payments', 'qr_code_base64')) {
                $table->longText('qr_code_base64')
                    ->nullable()
                    ->after('qr_code')
                    ->comment('QR Code Base64 encoded (PIX)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columnsToDrop = ['mercado_pago_order_id', 'external_reference', 'qr_code', 'qr_code_base64'];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    // Drop índices/uniques primeiro
                    try {
                        if ($column === 'mercado_pago_order_id' || $column === 'external_reference') {
                            $table->dropUnique([$column]);
                        }
                        $table->dropIndex([$column]);
                    } catch (\Exception $e) {
                        // Índice pode não existir
                    }
                    
                    $table->dropColumn($column);
                }
            }
        });
    }
};
