<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id',
        'step',
        'approver_type',
        'approver_id',
        'status',
        'comment',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Approver type constants
    const TYPE_COVER_PERSON = 'cover_person';
    const TYPE_MANAGER = 'manager';
    const TYPE_ADMIN = 'admin';

    /**
     * Get the parent leave.
     */
    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Check if approval is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approval is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if approval is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Scope for pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approvals by a specific approver.
     */
    public function scopeForApprover($query, int $approverId)
    {
        return $query->where('approver_id', $approverId);
    }
}
