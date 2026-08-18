<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectAssignment extends Model
{
    protected $fillable = [
        'subject_id',
        'teacher_id',
        'group_id',
        'semester_id',
        'lesson_type',
        'hours_per_week',
        'is_active',
        'credits',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ==================== РОБИТАҲО ====================

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function currentGrades(): HasMany
    {
        return $this->hasMany(CurrentGrade::class);
    }

    public function semesterGrades(): HasMany
    {
        return $this->hasMany(SemesterGrade::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Номи фан (shortcut)
     */
    public function getSubjectNameAttribute(): string
    {
        return $this->subject?->name ?? '';
    }

    /**
     * Кредитҳо (shortcut)
     */
    /**
     * Кредит: агар дар журнал дода шуда бошад — ҳамон,
     * вагарна аз фан
     */
    public function getCreditsAttribute(): int
    {
        $own = $this->attributes['credits'] ?? null;

        return $own !== null ? (int) $own : (int) ($this->subject?->credits ?? 0);
    }

    /**
     * Навъи имтиҳон (shortcut)
     */
    public function getExamTypeAttribute(): ?string
    {
        return $this->subject?->exam_type;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeInSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }
}
