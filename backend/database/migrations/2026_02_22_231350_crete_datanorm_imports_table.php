<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datanorm_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();

            // Datei-Info
            $table->string('filename');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->default(0);

            // Lieferant
            $table->string('supplier_name')->nullable();
            $table->string('supplier_id', 50)->nullable();

            // Datanorm Version
            $table->string('datanorm_version', 10)->default('4'); // 4 oder 5

            // Import-Ergebnis
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable();

            // Einstellungen für den Import
            $table->decimal('default_markup_percent', 5, 2)->default(30.00);
            $table->boolean('update_existing')->default(true);
            $table->boolean('overwrite_prices')->default(true);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datanorm_imports');
    }
};