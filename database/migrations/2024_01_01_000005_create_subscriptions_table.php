<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['lifetime', 'monthly', 'annual'])->default('lifetime');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('mercado_pago_subscription_id')->nullable()->unique();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index('user_id');
            $table->index('course_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
