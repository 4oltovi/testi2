<?php

namespace Tests\Unit;

use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    public function test_present_counts_as_present(): void
    {
        $this->assertTrue(\App\Enums\AttendanceStatus::PRESENT->countsAsPresent());
        $this->assertTrue(\App\Enums\AttendanceStatus::LATE->countsAsPresent());
        $this->assertTrue(\App\Enums\AttendanceStatus::EXCUSED->countsAsPresent());
        $this->assertTrue(\App\Enums\AttendanceStatus::SICK->countsAsPresent());
    }

    public function test_absent_does_not_count_as_present(): void
    {
        $this->assertFalse(\App\Enums\AttendanceStatus::ABSENT->countsAsPresent());
    }

    public function test_labels_are_correct(): void
    {
        $this->assertEquals('Ҳозир', \App\Enums\AttendanceStatus::PRESENT->label());
        $this->assertEquals('Ғоиб', \App\Enums\AttendanceStatus::ABSENT->label());
    }
}
