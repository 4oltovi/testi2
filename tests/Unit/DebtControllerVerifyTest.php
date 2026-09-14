<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Admin\DebtController;
use App\Models\AcademicDebt;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class DebtControllerVerifyTest extends TestCase
{
    public function test_verifyPayment_method_exists_and_is_callable(): void
    {
        $controller = new DebtController(
            app(\App\Services\DebtDetector::class),
        );

        $this->assertTrue(method_exists($controller, 'verifyPayment'));
    }

    public function test_verify_payment_rejects_non_admin(): void
    {
        $controller = new DebtController(
            app(\App\Services\DebtDetector::class),
        );

        $debt = new AcademicDebt([
            'debt_type' => 'f',
            'payment_status' => 'pending',
            'retake_allowed' => false,
            'retake_attempts_used' => 0,
            'max_retake_attempts' => 2,
            'status' => \App\Enums\DebtStatus::ACTIVE,
        ]);

        $request = Request::create('/admin/debts/1/verify-payment', 'POST', [
            'payment_amount' => '100',
            'payment_receipt' => 'test',
        ]);

        $result = $controller->verifyPayment($debt, $request);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}
