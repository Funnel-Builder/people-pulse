<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceAdjustmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'type',
        'date',
        'requested_time',
        'reason',
        'cover_person_id',
        'attachment_path',
        'attachment_name',
        'status',
        'current_approval_step',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'current_approval_step' => 'integer',
        ];
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Type constants
    const TYPE_LATE_ENTRY = 'late_entry';
    const TYPE_EARLY_OUT = 'early_out';

    /**
     * The employee who submitted the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The cover person who acts as the first-step approver.
     */
    public function coverPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cover_person_id');
    }

    /**
     * The attendance record for the requested day (if any).
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * All approval records for this request.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(AttendanceAdjustmentApproval::class, 'adjustment_request_id');
    }

    public function isLateEntry(): bool
    {
        return $this->type === self::TYPE_LATE_ENTRY;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Human-readable label for the request type.
     */
    public function typeLabel(): string
    {
        return $this->isLateEntry() ? 'Late Entry' : 'Early Out';
    }

    /**
     * Get the current pending approval at the active step.
     */
    public function currentApproval(): ?AttendanceAdjustmentApproval
    {
        return $this->approvals()
            ->where('step', $this->current_approval_step)
            ->where('status', AttendanceAdjustmentApproval::STATUS_PENDING)
            ->first();
    }

    /**
     * Total number of approval steps configured for adjustments.
     */
    public function getTotalSteps(): int
    {
        return count(config('attendance.adjustment_approval_steps', []));
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
