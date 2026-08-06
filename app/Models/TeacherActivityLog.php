<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherActivityLog extends Model
{
    protected $table = 'teacher_activity_log';

    protected $fillable = [
        'teacher_id', 'activity_type', 'description',
        'activity_date', 'order_number', 'metadata', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'metadata' => 'json',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
