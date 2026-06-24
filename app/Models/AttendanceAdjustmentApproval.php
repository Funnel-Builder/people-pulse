<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAdjustmentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_request_id',
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
     * The parent adjustment request.
     */
    public function adjustmentRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceAdjustmentRequest::class, 'adjustment_request_id');
    }

    /**
     * The user who acted on this approval.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
