<?php

namespace Tests\Unit;

use Tests\TestCase;

class GradeScaleTest extends TestCase
{
    public function test_passing_grades_return_true(): void
    {
        $this->assertTrue(\App\Enums\GradeScale::A->isPassing());
        $this->assertTrue(\App\Enums\GradeScale::B->isPassing());
        $this->assertTrue(\App\Enums\GradeScale::C->isPassing());
        $this->assertTrue(\App\Enums\GradeScale::D->isPassing());
    }

    public function test_failing_grades_return_false(): void
    {
        $this->assertFalse(\App\Enums\GradeScale::FX->isPassing());
        $this->assertFalse(\App\Enums\GradeScale::F->isPassing());
    }

    public function test_fx_allows_retake(): void
    {
        $this->assertTrue(\App\Enums\GradeScale::FX->canRetake());
        $this->assertFalse(\App\Enums\GradeScale::F->canRetake());
    }

    public function test_from_percentage_maps_correctly(): void
    {
        $this->assertEquals(\App\Enums\GradeScale::A, \App\Enums\GradeScale::fromPercentage(95));
        $this->assertEquals(\App\Enums\GradeScale::FX, \App\Enums\GradeScale::fromPercentage(47));
        $this->assertEquals(\App\Enums\GradeScale::F, \App\Enums\GradeScale::fromPercentage(30));
    }
}
