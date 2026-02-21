<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();

            // Firmendaten für Angebote
            $table->string('address_street')->nullable();
            $table->string('address_zip', 10)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('trade_register', 100)->nullable();

            // Branding
            $table->string('logo_path', 500)->nullable();
            $table->string('primary_color', 7)->default('#1E40AF');

            // Einstellungen
            $table->decimal('default_vat_rate', 4, 2)->default(19.00);
            $table->decimal('default_hourly_rate', 8, 2)->default(65.00);
            $table->string('currency', 3)->default('EUR');
            $table->integer('quote_validity_days')->default(30);
            $table->string('quote_prefix', 10)->default('ANG');
            $table->integer('next_quote_number')->default(1001);

            // Abo
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->enum('plan', ['trial', 'starter', 'professional', 'enterprise'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};