<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acceptance_protocols', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Protokoll-Nummer
            $table->string('protocol_number')->nullable();

            // Projektdaten (aus Angebot übernommen, aber editierbar)
            $table->string('project_title');
            $table->string('project_address')->nullable();
            $table->date('execution_start')->nullable();    // Ausführungsbeginn
            $table->date('execution_end')->nullable();       // Ausführungsende
            $table->date('acceptance_date')->nullable();     // Abnahmedatum

            // Beteiligte
            $table->string('contractor_name')->nullable();   // Auftragnehmer
            $table->string('client_name')->nullable();       // Auftraggeber
            $table->string('client_representative')->nullable(); // Vertreter AG

            // Ergebnis
            $table->enum('result', [
                'accepted',              // Abnahme ohne Mängel
                'accepted_with_defects', // Abnahme mit Mängeln
                'rejected',              // Abnahme verweigert
            ])->default('accepted');

            // KI-generierte Zusammenfassung der durchgeführten Arbeiten
            $table->text('work_summary')->nullable();

            // Mängelliste (JSON: [{title, description, severity, deadline}])
            $table->json('defects')->nullable();

            // Freitext-Felder
            $table->text('notes')->nullable();               // Bemerkungen
            $table->text('agreements')->nullable();           // Vereinbarungen

            // Unterschriften (base64 Bilder)
            $table->longText('signature_contractor')->nullable();
            $table->longText('signature_client')->nullable();
            $table->dateTime('signed_contractor_at')->nullable();
            $table->dateTime('signed_client_at')->nullable();

            // PDF
            $table->string('pdf_path')->nullable();
            $table->dateTime('pdf_generated_at')->nullable();

            $table->enum('status', ['draft', 'completed', 'signed'])->default('draft');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acceptance_protocols');
    }
};