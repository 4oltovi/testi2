<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialty extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'education_level',
        'study_years',
        'total_credits',
        'study_form',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ==================== РОБИТАҲО ====================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Фанҳои ихтисос тавассути гурӯҳҳо ва таъинотҳо
     */
    public function subjectAssignments(): HasMany
    {
        return $this->hasManyThrough(
            SubjectAssignment::class,
            Group::class,
            'specialty_id', // Foreign key on groups table
            'group_id',     // Foreign key on subject_assignments table
            'id',           // Local key on specialties table
            'id'            // Local key on groups table
        );
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
