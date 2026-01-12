<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Get the parent leave.
     */
    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }
}
