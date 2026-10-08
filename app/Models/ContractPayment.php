<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPayment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'amount',
        'payment_date',
        'payment_method',
        'note',
        'receipt_number',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Ҳамаи receipt_number-ҳо дар ҷадвали пардохт якхеланд.
     * NULL-ҳо (пардохт-е бе рақами расид) иҷозатанд.
     */
    protected static function booted(): void
    {
        static::saving(function (ContractPayment $payment) {
            if (!empty($payment->receipt_number)) {
                $payment->receipt_number = trim($payment->receipt_number);
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}