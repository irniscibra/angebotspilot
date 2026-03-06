<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_template_id',
        'group_name',
        'type',
        'title',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'material_id',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(ServiceTemplate::class, 'service_template_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}