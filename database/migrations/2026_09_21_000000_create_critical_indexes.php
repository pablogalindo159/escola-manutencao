<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Índices para pagamentos
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['user_id', 'course_id', 'status'], 'idx_payments_user_course_status');
            $table->index(['mercado_pago_payment_id'], 'idx_payments_mp_id');
            $table->index(['created_at'], 'idx_payments_created');
            $table->index(['status'], 'idx_payments_status');
        });

        // Índices para inscrições
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['user_id', 'course_id', 'status'], 'idx_subscriptions_user_course_status');
            $table->index(['expires_at'], 'idx_subscriptions_expires');
        });

        // Índices para cursos
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['status', 'featured'], 'idx_courses_status_featured');
            $table->index(['category'], 'idx_courses_category');
            $table->index(['instructor_id'], 'idx_courses_instructor');
        });

        // Índices para vídeos
        Schema::table('videos', function (Blueprint $table) {
            $table->index(['course_id'], 'idx_videos_course');
            $table->index(['created_at'], 'idx_videos_created');
        });

        // Índices para usuários
        Schema::table('users', function (Blueprint $table) {
            $table->index(['email'], 'idx_users_email');
            $table->index(['role', 'status'], 'idx_users_role_status');
        });

        // Índices para progresso
        Schema::table('user_progress', function (Blueprint $table) {
            $table->index(['user_id', 'course_id'], 'idx_progress_user_course');
            $table->index(['course_id'], 'idx_progress_course');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_user_course_status');
            $table->dropIndex('idx_payments_mp_id');
            $table->dropIndex('idx_payments_created');
            $table->dropIndex('idx_payments_status');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subscriptions_user_course_status');
            $table->dropIndex('idx_subscriptions_expires');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_status_featured');
            $table->dropIndex('idx_courses_category');
            $table->dropIndex('idx_courses_instructor');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex('idx_videos_course');
            $table->dropIndex('idx_videos_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role_status');
        });

        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropIndex('idx_progress_user_course');
            $table->dropIndex('idx_progress_course');
        });
    }
};
