<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'short_name',
        'code',
        'credits',
        'total_hours',
        'lecture_hours',
        'practice_hours',
        'lab_hours',
        'independent_hours',
        'exam_type',
        'is_elective',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_elective' => 'boolean',
        ];
    }

    // ==================== РОБИТАҲО ====================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    public function academicDebts(): HasMany
    {
        return $this->hasMany(AcademicDebt::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeElective($query)
    {
        return $query->where('is_elective', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_elective', false);
    }
}
