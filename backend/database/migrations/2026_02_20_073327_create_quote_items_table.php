<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->integer('position_number');
            $table->string('group_name', 100)->nullable();

            $table->enum('type', ['material', 'labor', 'flat', 'text']);

            // Beschreibung
            $table->string('title');
            $table->text('description')->nullable();

            // Kalkulation
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->default('Stück');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);

            // Referenzen
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

            // KI
            $table->boolean('is_ai_generated')->default(true);
            $table->decimal('ai_confidence', 3, 2)->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quote_id', 'position_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};