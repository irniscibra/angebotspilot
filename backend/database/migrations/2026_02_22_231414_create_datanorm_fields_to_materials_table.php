<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Datanorm-spezifische Felder
            $table->string('datanorm_article_number', 50)->nullable()->after('supplier_sku');
            $table->string('ean', 20)->nullable()->after('datanorm_article_number');
            $table->string('match_code', 50)->nullable()->after('ean');
            $table->string('product_group', 50)->nullable()->after('match_code');
            $table->string('discount_group', 50)->nullable()->after('product_group');
            $table->string('main_product_group', 50)->nullable()->after('discount_group');

            // Preisfelder für Datanorm
            $table->decimal('list_price', 10, 2)->nullable()->after('main_product_group');
            $table->decimal('gross_price', 10, 2)->nullable()->after('list_price');

            // Herkunft
            $table->enum('source', ['manual', 'datanorm', 'csv'])->default('manual')->after('is_active');
            $table->foreignId('datanorm_import_id')->nullable()->after('source')
                  ->constrained('datanorm_imports')->nullOnDelete();
            $table->text('long_description')->nullable()->after('description');

            // Indizes für Datanorm-Lookup
            $table->index(['company_id', 'datanorm_article_number'], 'mat_datanorm_article');
            $table->index(['company_id', 'ean'], 'mat_ean');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex('mat_datanorm_article');
            $table->dropIndex('mat_ean');

            $table->dropForeign(['datanorm_import_id']);
            $table->dropColumn([
                'datanorm_article_number',
                'ean',
                'match_code',
                'product_group',
                'discount_group',
                'main_product_group',
                'list_price',
                'gross_price',
                'source',
                'datanorm_import_id',
                'long_description',
            ]);
        });
    }
};