<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('category', 100);
            $table->string('name');
            $table->text('description')->nullable();

            // Kalkulation
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('flat_price', 10, 2)->nullable();
            $table->enum('pricing_type', ['hourly', 'flat'])->default('hourly');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};