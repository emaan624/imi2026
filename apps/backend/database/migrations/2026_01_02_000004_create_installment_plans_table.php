<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('device_price', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('installment_amount', 12, 2);
            $table->unsignedInteger('installment_count');
            $table->enum('frequency', ['weekly', 'monthly', 'custom'])->default('monthly');
            $table->unsignedInteger('grace_period_days')->default(5);
            $table->enum('late_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('late_fee_value', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('risk_level')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
