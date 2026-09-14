<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AcademicDebt;
use App\Enums\DebtStatus;

class AcademicDebtRetakeTest extends TestCase
{
    public function test_fx_debt_allows_retake_without_payment(): void
    {
        $debt = new AcademicDebt([
            'debt_type' => 'fx',
            'payment_status' => 'not_required',
            'retake_allowed' => true,
            'retake_attempts_used' => 0,
            'max_retake_attempts' => 2,
            'status' => DebtStatus::ACTIVE,
        ]);

        $this->assertTrue($debt->isFx());
        $this->assertFalse($debt->isF());
        $this->assertTrue($debt->canBeAddedToRetakeExam());
    }

    public function test_f_debt_requires_payment_verification(): void
    {
        $debt = new AcademicDebt([
            'debt_type' => 'f',
            'payment_status' => 'pending',
            'retake_allowed' => false,
            'retake_attempts_used' => 0,
            'max_retake_attempts' => 2,
            'status' => DebtStatus::ACTIVE,
        ]);

        $this->assertFalse($debt->isFx());
        $this->assertTrue($debt->isF());
        $this->assertFalse($debt->isPaymentVerified());
        $this->assertFalse($debt->canBeAddedToRetakeExam());
    }

    public function test_f_debt_allows_retake_after_payment_verified(): void
    {
        $debt = new AcademicDebt([
            'debt_type' => 'f',
            'payment_status' => 'verified',
            'retake_allowed' => true,
            'retake_attempts_used' => 0,
            'max_retake_attempts' => 2,
            'status' => DebtStatus::ACTIVE,
        ]);

        $this->assertTrue($debt->isPaymentVerified());
        $this->assertTrue($debt->canBeAddedToRetakeExam());
    }

    public function test_f_debt_not_allowed_after_max_attempts(): void
    {
        $debt = new AcademicDebt([
            'debt_type' => 'f',
            'payment_status' => 'verified',
            'retake_allowed' => true,
            'retake_attempts_used' => 2,
            'max_retake_attempts' => 2,
            'status' => DebtStatus::ACTIVE,
        ]);

        $this->assertFalse($debt->canRetake());
        $this->assertFalse($debt->canBeAddedToRetakeExam());
    }

    public function test_fx_debt_not_allowed_when_status_resolved(): void
    {
        $debt = new AcademicDebt([
            'debt_type' => 'fx',
            'payment_status' => 'not_required',
            'retake_allowed' => true,
            'retake_attempts_used' => 0,
            'max_retake_attempts' => 2,
            'status' => DebtStatus::RESOLVED,
        ]);

        $this->assertFalse($debt->canRetake());
        $this->assertFalse($debt->canBeAddedToRetakeExam());
    }

    public function test_is_payment_verified_only_for_verified_status(): void
    {
        $debt = new AcademicDebt([
            'debt_type' => 'f',
            'payment_status' => 'pending',
        ]);
        $this->assertFalse($debt->isPaymentVerified());

        $debt->payment_status = 'verified';
        $this->assertTrue($debt->isPaymentVerified());

        $debt->payment_status = 'not_required';
        $this->assertFalse($debt->isPaymentVerified());
    }
}
