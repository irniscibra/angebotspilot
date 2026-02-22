<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category',
        'subcategory',
        'name',
        'description',
        'long_description',
        'sku',
        'unit',
        'purchase_price',
        'selling_price',
        'markup_percent',
        'supplier',
        'supplier_sku',
        'is_active',
        // Datanorm-Felder
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
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'list_price' => 'decimal:2',
        'gross_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function datanormImport()
    {
        return $this->belongsTo(DatanormImport::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFromDatanorm($query)
    {
        return $query->where('source', 'datanorm');
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }
}