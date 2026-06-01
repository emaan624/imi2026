<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imei_provider_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('imei_provider_id')->constrained('imei_providers')->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique('imei_provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_provider_balances');
    }
};
