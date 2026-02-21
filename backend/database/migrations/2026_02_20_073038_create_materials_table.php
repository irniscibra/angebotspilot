<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('category', 100);
            $table->string('subcategory', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('unit', 20)->default('Stück');

            // Preise
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2);
            $table->decimal('markup_percent', 5, 2)->default(30.00);

            // Lieferant
            $table->string('supplier')->nullable();
            $table->string('supplier_sku', 100)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};