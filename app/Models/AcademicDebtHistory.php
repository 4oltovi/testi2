<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicDebtHistory extends Model
{
    protected $table = 'academic_debt_history';

    protected $fillable = [
        'academic_debt_id', 'action', 'from_status',
        'to_status', 'comment', 'metadata', 'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
        ];
    }

    public function academicDebt(): BelongsTo
    {
        return $this->belongsTo(AcademicDebt::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
