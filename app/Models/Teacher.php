<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_id',
        'birth_date',
        'gender',
        'academic_degree',
        'academic_title',
        'position',
        'employment_type',
        'rate',
        'hire_date',
        'contract_end_date',
        'max_hours_per_week',
        'current_load_hours',
        'status',
        'biography',
        'phone_work',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'contract_end_date' => 'date',
            'rate' => 'decimal:2',
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class, 'teacher_id', 'user_id');
    }

    public function activityLog(): HasMany
    {
        return $this->hasMany(TeacherActivityLog::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // ==================== МЕТОДҲО ====================

    public function getFullNameAttribute(): string
    {
        return $this->user->full_name;
    }

    public function getShortNameAttribute(): string
    {
        return $this->user->short_name;
    }

    /**
     * Фанҳои таълимии ин семестр
     */
    public function getSubjectsInSemester(int $semesterId): \Illuminate\Support\Collection
    {
        return $this->subjectAssignments()
            ->where('semester_id', $semesterId)
            ->where('is_active', true)
            ->with(['curriculum.subject', 'group'])
            ->get();
    }

    /**
     * Борбандии ҳафтаина
     */
    public function getWeeklyLoadAttribute(): int
    {
        return $this->subjectAssignments()
            ->where('is_active', true)
            ->sum('hours_per_week');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
