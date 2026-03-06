<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\ServiceTemplate;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address_street',
        'address_zip',
        'address_city',
        'phone',
        'email',
        'website',
        'tax_id',
        'trade_register',
        'logo_path',
        'primary_color',
        'default_vat_rate',
        'default_hourly_rate',
        'currency',
        'quote_validity_days',
        'quote_prefix',
        'next_quote_number',
        'plan',
        'trial_ends_at',
    ];

    protected $casts = [
        'default_vat_rate' => 'decimal:2',
        'default_hourly_rate' => 'decimal:2',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
            if (empty($company->trial_ends_at)) {
                $company->trial_ends_at = now()->addDays(14);
            }
        });
    }

    // ---- Relationships ----

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->users()->where('role', 'owner')->first();
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function serviceTemplates()
    {
        return $this->hasMany(ServiceTemplate::class);
    }

    // ---- Business Logic ----

    public function generateQuoteNumber(): string
    {
        $number = $this->next_quote_number;
        $this->increment('next_quote_number');

        return $this->quote_prefix . '-' . date('Y') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function isTrialActive(): bool
    {
        return $this->plan === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function hasActiveSubscription(): bool
    {
        return in_array($this->plan, ['starter', 'professional', 'enterprise']) || $this->isTrialActive();
    }
}