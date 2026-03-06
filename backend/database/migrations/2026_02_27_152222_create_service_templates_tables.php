<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');              // "Gäste-WC komplett"
            $table->string('category')->nullable(); // "Sanitär", "Heizung", etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0); // Wie oft verwendet
            $table->timestamps();

            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('service_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_template_id')->constrained()->cascadeOnDelete();
            $table->string('group_name');          // "Sanitärinstallation"
            $table->enum('type', ['material', 'labor', 'flat', 'text'])->default('material');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->default('Stück');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('service_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_template_items');
        Schema::dropIfExists('service_templates');
    }
};