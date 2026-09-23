<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arte do certificado por curso (fundo, logo, assinatura).
 * Caminhos no disco "local" (privado). Vazio = certificado padrão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'certificate_background')) {
                $table->string('certificate_background')->nullable();
            }
            if (!Schema::hasColumn('courses', 'certificate_logo')) {
                $table->string('certificate_logo')->nullable();
            }
            if (!Schema::hasColumn('courses', 'certificate_signature')) {
                $table->string('certificate_signature')->nullable();
            }
            if (!Schema::hasColumn('courses', 'certificate_hide_frame')) {
                $table->boolean('certificate_hide_frame')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach (['certificate_background', 'certificate_logo', 'certificate_signature', 'certificate_hide_frame'] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
