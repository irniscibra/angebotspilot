<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'category',
        'description',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ---- Relationships ----

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ServiceTemplateItem::class)->orderBy('sort_order');
    }

    // ---- Helpers ----

    /**
     * Erhöht den Nutzungszähler.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Berechnet den Gesamtwert der Vorlage.
     */
    public function getTotalAttribute(): float
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    }

    /**
     * Anzahl der Positionen.
     */
    public function getItemCountAttribute(): int
    {
        return $this->items->count();
    }
}