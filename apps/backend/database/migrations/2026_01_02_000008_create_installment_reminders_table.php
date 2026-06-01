<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('installment_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('installment_contract_id')->constrained('installment_contracts')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->nullable()->constrained('installment_schedules')->nullOnDelete();
            $table->enum('type', ['upcoming', 'overdue', 'payment_confirmation']);
            $table->enum('channel', ['email', 'sms', 'push'])->default('push');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_reminders');
    }
};
