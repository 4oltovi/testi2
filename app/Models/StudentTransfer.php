<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'from_group_id',
        'to_group_id',
        'from_specialty_id',
        'to_specialty_id',
        'transfer_date',
        'reason',
        'order_number',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'from_group_id');
    }

    public function toGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'to_group_id');
    }

    public function fromSpecialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'from_specialty_id');
    }

    public function toSpecialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'to_specialty_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
