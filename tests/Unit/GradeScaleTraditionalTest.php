<?php

namespace Tests\Unit;

use App\Enums\GradeScale;
use PHPUnit\Framework\TestCase;

/**
 * Шкалаи баҳогузории низоми кредитии Тоҷикистон.
 *
 * Ин тест манбаъи ягонаи ҳақиқатро мустаҳкам мекунад: transcript ва ҷадвали
 * шкала дар саҳифаи semester-grades бояд ҳамин аз `GradeScale` хонанд.
 */
class GradeScaleTraditionalTest extends TestCase
{
    /**
     * Ҳар як сатр: [фоиз, ҳарф, баҳои 5-баллӣ, гузаштааст]
     *
     * Ҳудудҳо аз рӯи `fromPercentage()` — ҳама ҳарфҳо бо ҳар як фоиз
     * (0..100) санҷида мешаванд, то ки дар байни бандҳо ҷойи холӣ нашавад.
     */
    public static function scaleProvider(): array
    {
        return [
            // Аъло → 5
            '100 → A → 5'   => [100.0, 'A', 5, true],
            '95  → A → 5'   => [95.0, 'A', 5, true],
            '94.9→ A- → 5'  => [94.9, 'A-', 5, true],
            '90  → A- → 5'  => [90.0, 'A-', 5, true],

            // Хуб → 4
            '89.9→ B+ → 4'  => [89.9, 'B+', 4, true],
            '89  → B+ → 4'  => [89.0, 'B+', 4, true],
            '85  → B+ → 4'  => [85.0, 'B+', 4, true],
            '84.9→ B  → 4'  => [84.9, 'B', 4, true],
            '80  → B  → 4'  => [80.0, 'B', 4, true],
            '79.9→ B- → 4'  => [79.9, 'B-', 4, true],
            '75  → B- → 4'  => [75.0, 'B-', 4, true],

            // Қаноатбахш → 3
            '74.9→ C+ → 3'  => [74.9, 'C+', 3, true],
            '74  → C+ → 3'  => [74.0, 'C+', 3, true],
            '70  → C+ → 3'  => [70.0, 'C+', 3, true],
            '69.9→ C  → 3'  => [69.9, 'C', 3, true],
            '65  → C  → 3'  => [65.0, 'C', 3, true],
            '64.9→ C- → 3'  => [64.9, 'C-', 3, true],
            '60  → C- → 3'  => [60.0, 'C-', 3, true],
            '59.9→ D+ → 3'  => [59.9, 'D+', 3, true],
            '55  → D+ → 3'  => [55.0, 'D+', 3, true],

            // D → 3 (ҳадиди гузаштааст)
            '54.9→ D  → 3'  => [54.9, 'D', 3, true],
            '54.5→ D  → 3'  => [54.5, 'D', 3, true],
            '52.1→ D  → 3'  => [52.1, 'D', 3, true],   // ҳолати аниқи илҳом
            '52.13→ D → 3'  => [52.13, 'D', 3, true],
            '50  → D  → 3'  => [50.0, 'D', 3, true],

            // Fx → 2
            '49.9→ Fx → 2'  => [49.9, 'Fx', 2, false],
            '49  → Fx → 2'  => [49.0, 'Fx', 2, false],
            '45  → Fx → 2'  => [45.0, 'Fx', 2, false],

            // F → 2
            '44.9→ F  → 2'  => [44.9, 'F', 2, false],
            '44  → F  → 2'  => [44.0, 'F', 2, false],
            '1   → F  → 2'  => [1.0, 'F', 2, false],
            '0   → F  → 2'  => [0.0, 'F', 2, false],
        ];
    }

    /**
     * @dataProvider scaleProvider
     */
    public function test_percentage_maps_to_expected_letter_and_five_point_grade(
        float $percentage,
        string $letter,
        int $traditional,
        bool $isPassing,
    ): void {
        $grade = GradeScale::fromPercentage($percentage);

        $this->assertSame($letter, $grade->value, "Фоиз {$percentage} бояд {$letter} шавад.");
        $this->assertSame($traditional, $grade->traditionalFivePoint(), "Фоиз {$percentage}: баҳои анъанавӣ {$traditional} бояд.");
        $this->assertSame($isPassing, $grade->isPassing(), "Фоиз {$percentage}: ҳолати гузаштагӣ.");
    }

    /**
     * Дар байни бандҳои шкала ҳеҷ ҷойи холӣ нест: ҳар як фоиз аз 0 то 100
     * ҳарф ва баҳои 5-баллӣ мегирад.
     */
    public function test_every_percentage_from_0_to_100_maps_without_gaps(): void
    {
        $allowed = [5, 4, 3, 2];

        for ($i = 0; $i <= 10000; $i++) {
            $percentage = $i / 100;
            $grade = GradeScale::fromPercentage($percentage);

            $this->assertContains(
                $grade->traditionalFivePoint(),
                $allowed,
                "Фоиз {$percentage} баҳои нодуруст дод.",
            );

            // Фоиз бояд дар ҳудуди худи ҳарф ҷойгир бошад
            $range = $grade->percentageRange();
            $this->assertGreaterThanOrEqual(
                $range['min'] - 0.001,
                $percentage,
                "Фоиз {$percentage} аз ҳудуди {$grade->value} берунтар аст.",
            );
            $this->assertLessThanOrEqual(
                $range['max'] + 0.001,
                $percentage,
                "Фоиз {$percentage} аз ҳудуди {$grade->value} болотар аст.",
            );
        }
    }

    /**
     * Ҳар як ҳарфи шкала баҳои анъанавии ягона дорад (5/4/3/2) ва онро
     * як хел ҳам transcript ва ҳам ҷадвали шкала нишон медиҳанд.
     */
    public function test_every_letter_has_one_consistent_traditional_value(): void
    {
        foreach (GradeScale::cases() as $grade) {
            $five = $grade->traditionalFivePoint();
            $text = $grade->traditionalGrade();

            $this->assertContains($five, [5, 4, 3, 2], "{$grade->value}: баҳои {$five}.");

            // Рақами ягона, ки ҳама ҷой истифода мебаранд
            $this->assertSame(
                $five,
                match ($text) {
                    'Аъло' => 5,
                    'Хуб' => 4,
                    'Қаноатбахш' => 3,
                    'Ғайриқаноатбахш' => 2,
                },
                "{$grade->value}: матн «{$text}» бо рақами {$five} ҳаққӣ нест.",
            );
        }
    }

    /**
     * D гузаштааст, Fx ва F нестанд — ҳуқуқи transcript ба ин вобаста аст.
     */
    public function test_passing_boundary_is_d(): void
    {
        $this->assertTrue(GradeScale::fromPercentage(52.1)->isPassing());
        $this->assertTrue(GradeScale::fromPercentage(50)->isPassing());
        $this->assertFalse(GradeScale::fromPercentage(49.99)->isPassing());

        $this->assertTrue(GradeScale::D->isPassing());
        $this->assertFalse(GradeScale::FX->isPassing());
        $this->assertFalse(GradeScale::F->isPassing());
    }

    /**
     * `traditionalFivePoint()` бояд бо мати мувофиқ шавад — рақм барои
     * намоиш, матн барои ҳуҷҷатҳо.
     */
    public function test_five_point_and_text_agree_for_every_letter(): void
    {
        $this->assertSame(3, GradeScale::D->traditionalFivePoint());
        $this->assertSame('Қаноатбахш', GradeScale::D->traditionalGrade());
        $this->assertSame(2, GradeScale::FX->traditionalFivePoint());
        $this->assertSame(2, GradeScale::F->traditionalFivePoint());
        $this->assertSame(5, GradeScale::A->traditionalFivePoint());
        $this->assertSame(4, GradeScale::B->traditionalFivePoint());

        // Fx имкони такрорсупорӣ дорад, F — дуборахонӣ
        $this->assertTrue(GradeScale::FX->canRetake());
        $this->assertTrue(GradeScale::F->mustRepeatCourse());
    }
}