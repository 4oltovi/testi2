<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SubjectAssignment extends Model
{
    protected $fillable = [
        'subject_id',
        'teacher_id',
        'group_id',
        'semester_id',
        'lesson_type',
        'hours_per_week',
        'lecture_hours',
        'practical_hours',
        'lab_hours',
        'is_active',
        'merged_into_id',
        'credits',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Soft-delete-и мешавад: сатрҳои обудашуда (merged_into_id set)
        // дар ҳамаи дархостҳо пинҳонанд. Фақат merge-farmonи худ
        // (DB::table) ва санҷишҳо (withoutGlobalScope) онҳоро мебинанд.
        static::addGlobalScope('not_merged', function ($query) {
            $query->whereNull('subject_assignments.merged_into_id');
        });
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
     * Сатри қаноатманд, ки ин сатр ба он обуда шудааст (пас аз merge).
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    /**
     * Танҳо таъинотҳои обуданашуда (ягона дар як фан+гурӯҳ+омӯзгор+семестр).
     */
    public function scopeNotMerged($query)
    {
        return $query->whereNull('merged_into_id');
    }

    /**
     * Тақсимоти соатҳо/дарсҳо аз рӯи навъ — барои нишон дар рӯйхатҳо.
     * Агар соатҳои навъӣ набошанд, аз дарсҳои журнал (category_scores) ҳисоб мешавад.
     *
     * @return array<string,int> ['lecture' => 4, 'practice' => 6, 'lab' => 2]
     */
    public function lessonTypeBreakdown(): array
    {
        if ($this->lecture_hours !== null || $this->practical_hours !== null || $this->lab_hours !== null) {
            return [
                'lecture' => (int) $this->lecture_hours,
                'practice' => (int) $this->practical_hours,
                'lab' => (int) $this->lab_hours,
            ];
        }

        $rows = DB::table('category_scores')
            ->where('subject_assignment_id', $this->id)
            ->get(['lesson_type', 'lesson_date', 'lesson_number']);

        $lessons = [];
        foreach ($rows as $row) {
            $type = $row->lesson_type ?: ($this->lesson_type ?: 'practice');
            $lessons[$type][$row->lesson_date . '_' . $row->lesson_number] = true;
        }

        $breakdown = ['lecture' => 0, 'practice' => 0, 'lab' => 0];
        foreach ($lessons as $type => $set) {
            if (isset($breakdown[$type])) {
                $breakdown[$type] = count($set);
            }
        }

        return $breakdown;
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
