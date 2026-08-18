<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class RatingSession extends Model
{
    protected $fillable = [
        'name',
        'period',
        'semester_id',
        'start_at',
        'end_at',
        'duration_minutes',
        'questions_count',
        'max_attempts',
        'schedule_mode',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'end_at' => 'datetime'];
    }

    // ==================== РОБИТАҲО ====================

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'rating_session_subjects')
            ->withPivot('questions_count');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'rating_session_groups')
            ->withPivot(['start_at', 'end_at']);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(RatingAttempt::class);
    }

    // ==================== ҲОЛАТҲО ====================

    public function isOpenNow(): bool
    {
        return $this->status === 'active' && now()->between($this->start_at, $this->end_at);
    }

    public function isFinished(): bool
    {
        return $this->status === 'completed' || now()->greaterThan($this->end_at);
    }

    /**
     * Равзанаи вақт барои гурӯҳ (режими by_group)
     */
    public function windowForGroup(?int $groupId): array
    {
        if ($this->schedule_mode === 'by_group' && $groupId) {
            $g = $this->groups->firstWhere('id', $groupId);

            if ($g && $g->pivot->start_at && $g->pivot->end_at) {
                return [
                    'start' => \Carbon\Carbon::parse($g->pivot->start_at),
                    'end'   => \Carbon\Carbon::parse($g->pivot->end_at),
                ];
            }
        }

        return ['start' => $this->start_at, 'end' => $this->end_at];
    }

    public function isOpenForGroup(?int $groupId): bool
    {
        if ($this->status !== 'active') return false;

        $w = $this->windowForGroup($groupId);

        return now()->between($w['start'], $w['end']);
    }

    // ==================== САНҶИШИ ОМАДАГӢ ====================

    /**
     * Оё барои ҳар фан саволҳои кофӣ (банк rating) ҳаст?
     */
    public function readinessReport(): array
    {
        $subjectIds = $this->subjects()->pluck('subjects.id')->all();
        $need = $this->questions_count;

        if (empty($subjectIds)) {
            return ['total' => 0, 'ready' => 0, 'missing' => 0, 'missing_ids' => []];
        }

        $ids = implode(',', array_map('intval', $subjectIds));

        $counts = collect(DB::select("
            SELECT qb.subject_id AS sid, COUNT(*) AS cnt
            FROM questions q
            JOIN question_banks qb ON qb.id = q.question_bank_id
            WHERE qb.bank_type = 'rating' AND qb.subject_id IN ({$ids})
            GROUP BY qb.subject_id
        "))->keyBy('sid');

        $missing = [];
        foreach ($subjectIds as $sid) {
            if ((int) ($counts[$sid]->cnt ?? 0) < $need) {
                $missing[] = $sid;
            }
        }

        return [
            'total' => count($subjectIds),
            'ready' => count($subjectIds) - count($missing),
            'missing' => count($missing),
            'missing_ids' => $missing,
        ];
    }
}
