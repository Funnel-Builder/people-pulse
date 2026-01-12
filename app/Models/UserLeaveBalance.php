<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'balance',
        'used',
        'accrual_type',
        'attendance_days_threshold',
        'last_accrual_date',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:1',
            'used' => 'decimal:1',
            'attendance_days_threshold' => 'integer',
            'last_accrual_date' => 'date',
        ];
    }

    // Accrual type constants
    const ACCRUAL_MANUAL = 'manual';
    const ACCRUAL_ATTENDANCE = 'attendance';

    /**
     * Get the user this balance belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave type for this balance.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get remaining available balance.
     */
    public function getAvailableAttribute(): float
    {
        return max(0, (float) $this->balance - (float) $this->used);
    }

    /**
     * Deduct leave days from balance.
     */
    public function deductLeave(float $days): bool
    {
        if ($this->available < $days) {
            return false;
        }

        $this->used = (float) $this->used + $days;
        return $this->save();
    }

    /**
     * Adjust balance (admin action).
     */
    public function adjustBalance(float $newBalance): bool
    {
        $this->balance = $newBalance;
        return $this->save();
    }

    /**
     * Calculate and apply attendance-based accrual.
     * Returns number of days accrued.
     */
    public function calculateAttendanceAccrual(): float
    {
        if ($this->accrual_type !== self::ACCRUAL_ATTENDANCE || !$this->attendance_days_threshold) {
            return 0;
        }

        $user = $this->user;
        $startDate = $this->last_accrual_date ?? $user->joining_date ?? now()->subYear();

        // Count attendance days since last accrual
        $attendanceDays = Attendance::where('user_id', $this->user_id)
            ->where('date', '>', $startDate)
            ->where('status', '!=', 'absent')
            ->count();

        // Calculate earned leaves
        $earnedLeaves = floor($attendanceDays / $this->attendance_days_threshold);

        if ($earnedLeaves > 0) {
            $this->balance = (float) $this->balance + $earnedLeaves;
            $this->last_accrual_date = now();
            $this->save();
        }

        return $earnedLeaves;
    }

    /**
     * Get or create balance for a user and leave type.
     */
    public static function getOrCreate(int $userId, int $leaveTypeId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'leave_type_id' => $leaveTypeId],
            ['balance' => 0, 'used' => 0, 'accrual_type' => self::ACCRUAL_MANUAL]
        );
    }
}
