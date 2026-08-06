<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transcript extends Model
{
    protected $fillable = [
        'student_id', 'transcript_number', 'issue_date', 'type',
        'final_gpa', 'total_credits_earned', 'total_credits_required',
        'total_subjects_passed', 'total_subjects', 'honors',
        'issued_by', 'notes', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'final_gpa' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TranscriptLine::class)->orderBy('sort_order');
    }
}
