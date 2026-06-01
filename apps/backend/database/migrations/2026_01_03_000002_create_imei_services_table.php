<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imei_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('imei_category_id')->constrained('imei_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('checker_type')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('estimated_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_services');
    }
};
