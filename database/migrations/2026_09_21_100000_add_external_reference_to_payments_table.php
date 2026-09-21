<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'external_reference')) {
                $table->string('external_reference')
                    ->nullable()
                    ->unique()
                    ->index()
                    ->after('mercado_pago_preference_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'external_reference')) {
                $table->dropUnique(['external_reference']);
                $table->dropIndex(['external_reference']);
                $table->dropColumn('external_reference');
            }
        });
    }
};
