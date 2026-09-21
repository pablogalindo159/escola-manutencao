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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('mercado_pago_order_id')
                ->nullable()
                ->unique()
                ->after('mercado_pago_payment_id')
                ->comment('Order ID da Orders API (PIX Transparente)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['mercado_pago_order_id']);
            $table->dropColumn('mercado_pago_order_id');
        });
    }
};
