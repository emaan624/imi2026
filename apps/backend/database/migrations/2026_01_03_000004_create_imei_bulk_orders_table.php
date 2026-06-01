<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imei_bulk_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bulk_number')->unique();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('processed_orders')->default(0);
            $table->unsignedInteger('successful_orders')->default(0);
            $table->unsignedInteger('failed_orders')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_bulk_orders');
    }
};
