<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imei_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imei_service_id')->constrained('imei_services')->cascadeOnDelete();
            $table->foreignId('imei_provider_id')->nullable()->constrained('imei_providers')->nullOnDelete();
            $table->foreignId('imei_bulk_order_id')->nullable()->constrained('imei_bulk_orders')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('provider_order_id')->nullable()->index();
            $table->string('imei', 20);
            $table->string('status')->default('pending_submission');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('provider_cost', 12, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->json('result')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_orders');
    }
};
