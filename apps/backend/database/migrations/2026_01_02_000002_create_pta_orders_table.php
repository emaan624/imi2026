<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pta_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pta_service_id')->constrained('pta_services')->restrictOnDelete();
            $table->string('order_number')->unique();
            $table->string('imei_1', 20);
            $table->string('imei_2', 20)->nullable();
            $table->enum('sim_type', ['single', 'dual'])->default('single');
            $table->enum('registration_type', ['passport', 'cnic', 'overseas']);
            $table->string('passport_number')->nullable();
            $table->string('cnic_number')->nullable();
            $table->enum('status', ['draft', 'pending_payment', 'paid', 'in_review', 'approved', 'rejected'])->default('pending_payment');
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('wallet_payment_amount', 12, 2)->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->string('external_reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pta_orders');
    }
};
