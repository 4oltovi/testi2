<?php

namespace Tests\Unit;

use Tests\TestCase;

class DebtStatusTest extends TestCase
{
    public function test_open_statuses(): void
    {
        $this->assertTrue(\App\Enums\DebtStatus::ACTIVE->isOpen());
        $this->assertTrue(\App\Enums\DebtStatus::RETAKE_SCHEDULED->isOpen());
        $this->assertTrue(\App\Enums\DebtStatus::ESCALATED->isOpen());
        $this->assertFalse(\App\Enums\DebtStatus::RESOLVED->isOpen());
    }
}
