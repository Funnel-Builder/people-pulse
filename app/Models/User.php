<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'profile_picture',
        'department_id',
        'sub_department_id',
        'designation',
        'role',
        'weekend_days',
        'nid_number',
        'joining_date',
        'closing_date',
        'permanent_address',
        'present_address',
        'nationality',
        'fathers_name',
        'mothers_name',
        'graduated_institution',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'weekend_days' => 'array',
            'joining_date' => 'date',
            'closing_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the milestones for the user.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(EmployeeMilestone::class)->orderBy('date', 'desc');
    }

    /**
     * Get all leaves applied by this user.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Get all leaves where this user is the cover person.
     */
    public function coverPersonLeaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'cover_person_id');
    }

    /**
     * Get all leave approvals assigned to this user.
     */
    public function leaveApprovals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class, 'approver_id');
    }

    /**
     * Get the department that the user belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the sub-department that the user belongs to.
     */
    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    /**
     * Get the explicitly assigned sub-departments from the pivot table.
     * This is the new relationship for manager responsibilities.
     */
    public function managedResponsibilities(): BelongsToMany
    {
        return $this->belongsToMany(SubDepartment::class, 'manager_sub_departments', 'user_id', 'sub_department_id');
    }

    /**
     * Get the sub-departments that this manager manages.
     * Priority:
     * 1. Explicit assignments in the manager_sub_departments pivot table
     * 2. Fall back: If sub_department_id is set, manage only that sub_department
     * 3. Fall back: If department_id is set but no sub_department_id, manage all sub_departments in that department
     */
    public function getManagedSubDepartments()
    {
        if (!$this->isManager() && !$this->isAdmin()) {
            return collect([]);
        }

        // First, check for explicit assignments in the pivot table
        $explicitAssignments = $this->managedResponsibilities()->with('department')->get();
        if ($explicitAssignments->isNotEmpty()) {
            return $explicitAssignments;
        }

        // Fall back: If manager has a specific sub_department, they manage only that one
        if ($this->sub_department_id) {
            return SubDepartment::with('department')->where('id', $this->sub_department_id)->get();
        }

        // Fall back: If manager has a department but no sub_department, they manage all sub_departments in that department
        if ($this->department_id) {
            return SubDepartment::with('department')->where('department_id', $this->department_id)->get();
        }

        return collect([]);
    }

    /**
     * Get the IDs of sub-departments that this manager manages.
     */
    public function getManagedSubDepartmentIds(): array
    {
        return $this->getManagedSubDepartments()->pluck('id')->toArray();
    }

    /**
     * Alias for managedResponsibilities() for backward compatibility.
     */
    public function managedSubDepartments(): BelongsToMany
    {
        return $this->managedResponsibilities();
    }

    /**
     * Check if a given day is a weekend for this user
     */
    public function isWeekend(string $dayName): bool
    {
        $weekendDays = $this->weekend_days ?? ['saturday', 'sunday'];
        return in_array(strtolower($dayName), array_map('strtolower', $weekendDays));
    }

    /**
     * Scope to filter users by department
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter users by sub-department
     */
    public function scopeInSubDepartment($query, $subDepartmentId)
    {
        return $query->where('sub_department_id', $subDepartmentId);
    }
}
