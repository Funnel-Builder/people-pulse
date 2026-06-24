<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'target_audience',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_audience' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user who created this announcement.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active announcements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get announcements that are currently visible (started and not expired).
     */
    public function scopeCurrentlyVisible($query)
    {
        $now = Carbon::now();
        
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            });
    }

    /**
     * Scope to filter announcements for a specific user based on targeting.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // No targeting = visible to all
            $q->whereNull('target_audience')
                ->orWhereJsonLength('target_audience', 0);
            
            // Role targeting
            $q->orWhereJsonContains('target_audience->roles', $user->role);
            
            // Department targeting
            if ($user->department_id) {
                $q->orWhereJsonContains('target_audience->department_ids', $user->department_id);
            }
            
            // Sub-department targeting
            if ($user->sub_department_id) {
                $q->orWhereJsonContains('target_audience->sub_department_ids', $user->sub_department_id);
            }
        });
    }

    /**
     * Get the type color class for display.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'event' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        };
    }

    /**
     * Get the type icon name for display.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'info' => 'info',
            'warning' => 'alert-triangle',
            'success' => 'check-circle',
            'event' => 'calendar',
            default => 'bell',
        };
    }

    /**
     * Check if announcement is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if announcement has started.
     */
    public function hasStarted(): bool
    {
        return !$this->starts_at || $this->starts_at->isPast();
    }

    /**
     * Get formatted target audience for display.
     */
    public function getTargetAudienceDisplayAttribute(): string
    {
        if (!$this->target_audience || empty($this->target_audience)) {
            return 'Everyone';
        }

        $parts = [];

        if (!empty($this->target_audience['roles'])) {
            $parts[] = 'Roles: ' . implode(', ', array_map('ucfirst', $this->target_audience['roles']));
        }

        if (!empty($this->target_audience['department_ids'])) {
            $deptNames = Department::whereIn('id', $this->target_audience['department_ids'])->pluck('name')->toArray();
            $parts[] = 'Departments: ' . implode(', ', $deptNames);
        }

        if (!empty($this->target_audience['sub_department_ids'])) {
            $subDeptNames = SubDepartment::whereIn('id', $this->target_audience['sub_department_ids'])->pluck('name')->toArray();
            $parts[] = 'Sub-Departments: ' . implode(', ', $subDeptNames);
        }

        return empty($parts) ? 'Everyone' : implode(' | ', $parts);
    }
}
