<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'customer_id',
        'created_by',
        'quote_number',
        'project_title',
        'project_description',
        'project_address',
        'ai_prompt',
        'ai_response',
        'ai_model',
        'ai_tokens_used',
        'subtotal_materials',
        'subtotal_labor',
        'subtotal_net',
        'vat_rate',
        'vat_amount',
        'total_gross',
        'discount_percent',
        'discount_amount',
        'status',
        'valid_until',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'rejected_at',
        'pdf_path',
        'pdf_generated_at',
        'internal_notes',
        'terms_text',
        'header_text',
        'footer_text',
    ];

    protected $casts = [
        'ai_response' => 'array',
        'subtotal_materials' => 'decimal:2',
        'subtotal_labor' => 'decimal:2',
        'subtotal_net' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_gross' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->uuid)) {
                $quote->uuid = Str::uuid();
            }
        });
    }

    // ---- Relationships ----

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }



    public function creator()
{
    return $this->belongsTo(User::class, 'user_id');
}

    public function items()
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order')->orderBy('position_number');
    }

    // ---- Business Logic ----

    public function recalculate(): void
    {
        $materialTotal = $this->items()->where('type', 'material')->sum('total_price');
        $laborTotal = $this->items()->whereIn('type', ['labor', 'flat'])->sum('total_price');

        $subtotalNet = $materialTotal + $laborTotal;

        // Rabatt anwenden
        $discountAmount = $subtotalNet * ($this->discount_percent / 100);
        $netAfterDiscount = $subtotalNet - $discountAmount;

        $vatAmount = $netAfterDiscount * ($this->vat_rate / 100);

        $this->update([
            'subtotal_materials' => $materialTotal,
            'subtotal_labor' => $laborTotal,
            'subtotal_net' => $netAfterDiscount,
            'discount_amount' => $discountAmount,
            'vat_amount' => $vatAmount,
            'total_gross' => $netAfterDiscount + $vatAmount,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'valid_until' => now()->addDays($this->company->quote_validity_days),
        ]);
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function markAsRejected(): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);
    }

    // Öffentlicher Link für Kunden
    public function getPublicUrlAttribute(): string
    {
        return url("/angebot/{$this->uuid}");
    }

    // Gruppenweise Positionen
    public function getGroupedItemsAttribute(): array
    {
        return $this->items->groupBy('group_name')->toArray();
    }
}