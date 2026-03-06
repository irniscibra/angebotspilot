<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcceptanceProtocol extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'quote_id',
        'created_by',
        'protocol_number',
        'project_title',
        'project_address',
        'execution_start',
        'execution_end',
        'acceptance_date',
        'contractor_name',
        'client_name',
        'client_representative',
        'result',
        'work_summary',
        'defects',
        'notes',
        'agreements',
        'signature_contractor',
        'signature_client',
        'signed_contractor_at',
        'signed_client_at',
        'pdf_path',
        'pdf_generated_at',
        'status',
    ];

    protected $casts = [
        'defects' => 'array',
        'execution_start' => 'date',
        'execution_end' => 'date',
        'acceptance_date' => 'date',
        'signed_contractor_at' => 'datetime',
        'signed_client_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    protected $hidden = [
        'signature_contractor',
        'signature_client',
    ];

    protected $appends = [
        'has_contractor_signature',
        'has_client_signature',
    ];

    protected static function booted(): void
    {
        static::creating(function (AcceptanceProtocol $protocol) {
            if (empty($protocol->uuid)) {
                $protocol->uuid = Str::uuid();
            }
        });
    }

    // ---- Relationships ----

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Accessors ----

    public function getHasContractorSignatureAttribute(): bool
    {
        return !empty($this->signature_contractor);
    }

    public function getHasClientSignatureAttribute(): bool
    {
        return !empty($this->signature_client);
    }

    // ---- Business Logic ----

    public function markAsSigned(): void
    {
        if ($this->signature_contractor && $this->signature_client) {
            $this->update(['status' => 'signed']);
        }
    }

    public function getResultLabelAttribute(): string
    {
        return match ($this->result) {
            'accepted' => 'Abnahme ohne Mängel',
            'accepted_with_defects' => 'Abnahme mit Mängeln',
            'rejected' => 'Abnahme verweigert',
            default => $this->result,
        };
    }

    public function getDefectCountAttribute(): int
    {
        return count($this->defects ?? []);
    }
}