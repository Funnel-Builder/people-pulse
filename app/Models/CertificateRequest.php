<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref_id',
        'user_id',
        'purpose',
        'purpose_other',
        'urgency',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
        'issued_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ISSUED = 'issued';
    const STATUS_CANCELLED = 'cancelled';

    // Purpose constants
    const PURPOSE_VISA_APPLICATION = 'visa_application';
    const PURPOSE_BANK_LOAN = 'bank_loan';
    const PURPOSE_APARTMENT_LEASING = 'apartment_leasing';
    const PURPOSE_HIGHER_EDUCATION = 'higher_education';
    const PURPOSE_OTHER = 'other';

    /**
     * Get the employee who requested the certificate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager/admin who approved the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who issued the certificate.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the certificate has been issued.
     */
    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for issued requests.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Get the display name for the purpose.
     */
    public function getPurposeDisplayAttribute(): string
    {
        if ($this->purpose === self::PURPOSE_OTHER) {
            return $this->purpose_other ?? 'Other';
        }

        $purposes = config('services_module.certificate_purposes', []);
        return $purposes[$this->purpose] ?? ucwords(str_replace('_', ' ', $this->purpose));
    }

    /**
     * Generate a unique reference ID.
     * Format: BDFB/POD/{Year}/{SequentialNumber}
     */
    public static function generateRefId(): string
    {
        $year = now()->year;
        $prefix = "BDFB/POD/{$year}/";

        // Get the last ref_id for the current year
        $lastRequest = static::where('ref_id', 'like', $prefix . '%')
            ->orderBy('ref_id', 'desc')
            ->first();

        if ($lastRequest) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastRequest->ref_id, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
