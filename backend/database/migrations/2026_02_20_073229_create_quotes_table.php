<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Für öffentlichen Kundenlink
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');

            // Angebotsnummer
            $table->string('quote_number', 50);

            // Projekt
            $table->string('project_title');
            $table->text('project_description')->nullable();
            $table->string('project_address', 500)->nullable();

            // KI
            $table->text('ai_prompt')->nullable();
            $table->json('ai_response')->nullable();
            $table->string('ai_model', 50)->nullable();
            $table->integer('ai_tokens_used')->default(0);

            // Kalkulation
            $table->decimal('subtotal_materials', 12, 2)->default(0);
            $table->decimal('subtotal_labor', 12, 2)->default(0);
            $table->decimal('subtotal_net', 12, 2)->default(0);
            $table->decimal('vat_rate', 4, 2)->default(19.00);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);

            // Status
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // PDF
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            // Texte
            $table->text('internal_notes')->nullable();
            $table->text('terms_text')->nullable();
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'quote_number']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};