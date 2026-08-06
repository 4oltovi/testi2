<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'subject_assignment_id', 'classroom_id', 'semester_id',
        'day_of_week', 'lesson_number', 'start_time', 'end_time',
        'week_type', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Номи рӯзи ҳафта ба тоҷикӣ
     */
    public function getDayNameAttribute(): string
    {
        return match ($this->day_of_week) {
            1 => 'Душанбе',
            2 => 'Сешанбе',
            3 => 'Чоршанбе',
            4 => 'Панҷшанбе',
            5 => 'Ҷумъа',
            6 => 'Шанбе',
            default => '',
        };
    }
}
