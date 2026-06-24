<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'type',
        'reason',
        'cover_person_id',
        'status',
        'current_approval_step',
    ];

    protected function casts(): array
    {
        return [
            'current_approval_step' => 'integer',
        ];
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Type constants
    const TYPE_ADVANCE = 'advance';
    const TYPE_POST = 'post';

    /**
     * Get the user who applied for leave.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave type.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the cover person (for advance leave).
     */
    public function coverPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cover_person_id');
    }

    /**
     * Get all dates for this leave.
     */
    public function dates(): HasMany
    {
        return $this->hasMany(LeaveDate::class);
    }

    /**
     * Get all approval records for this leave.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * Check if this is an advance leave.
     */
    public function isAdvance(): bool
    {
        return $this->type === self::TYPE_ADVANCE;
    }

    /**
     * Check if this is a post leave.
     */
    public function isPost(): bool
    {
        return $this->type === self::TYPE_POST;
    }

    /**
     * Check if leave is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if leave is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if leave is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get the current pending approval.
     */
    public function currentApproval(): ?LeaveApproval
    {
        return $this->approvals()
            ->where('step', $this->current_approval_step)
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->first();
    }

    /**
     * Get total number of approval steps for this leave type.
     */
    public function getTotalSteps(): int
    {
        $steps = config("leave.approval_steps.{$this->type}", []);
        return count($steps);
    }

    /**
     * Scope for pending leaves.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for a specific user's leaves.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
