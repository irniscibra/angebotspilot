<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['private', 'business'])->default('private');

            // Privatkunde
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();

            // Geschäftskunde
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();

            // Kontakt
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();

            // Adresse
            $table->string('address_street')->nullable();
            $table->string('address_zip', 10)->nullable();
            $table->string('address_city', 100)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};