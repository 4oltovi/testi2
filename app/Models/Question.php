<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question_bank_id', 'subject_id', 'type', 'question_text',
        'question_image', 'difficulty_level', 'points', 'explanation',
        'is_active', 'times_used',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'points' => 'decimal:2',
        ];
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function answerOptions(): HasMany
    {
        return $this->hasMany(AnswerOption::class);
    }

    public function correctAnswers(): HasMany
    {
        return $this->hasMany(AnswerOption::class)->where('is_correct', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDifficulty($query, int $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Навъи савол ба тоҷикӣ
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'single_choice' => 'Якҷавобӣ',
            'multiple_choice' => 'Чандҷавобӣ',
            'open_text' => 'Ҷавоби кушод',
            'true_false' => 'Дуруст/Нодуруст',
            'matching' => 'Мувофиқгузорӣ',
            default => $this->type,
        };
    }

    public function getWeightedPointsAttribute(): float
    {
        return match ($this->type) {
            'single_choice', 'multiple_choice', 'true_false' => 2.5,
            'matching' => 10.0,
            'open_text' => 0.0,
            default => (float) ($this->points ?? 1.0),
        };
    }
}
