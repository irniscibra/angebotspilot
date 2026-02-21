<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address_street',
        'address_zip',
        'address_city',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    // Anzeigename
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'business') {
            return $this->company_name ?? $this->contact_person ?? 'Unbekannt';
        }

        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unbekannt';
    }

    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address_street,
            $this->address_zip . ' ' . $this->address_city,
        ]));
    }
}