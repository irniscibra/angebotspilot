<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatanormImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'filename',
        'original_filename',
        'file_size',
        'supplier_name',
        'supplier_id',
        'datanorm_version',
        'status',
        'total_records',
        'imported_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'errors',
        'default_markup_percent',
        'update_existing',
        'overwrite_prices',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'update_existing' => 'boolean',
        'overwrite_prices' => 'boolean',
        'default_markup_percent' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    /**
     * Zusammenfassung als lesbarer String.
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%d importiert, %d aktualisiert, %d übersprungen, %d Fehler',
            $this->imported_count,
            $this->updated_count,
            $this->skipped_count,
            $this->error_count
        );
    }
}