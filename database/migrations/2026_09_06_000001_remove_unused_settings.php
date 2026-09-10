<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->whereIn('key', [
            'formula_weight_rating1',
            'formula_weight_rating2',
            'formula_weight_exam',
            'formula_weight_rating1_with_iw',
            'formula_weight_rating2_with_iw',
            'formula_weight_independent_work',
            'formula_weight_exam_with_iw',
            'rating_part_divisor',
            'exam_weight',
            'rating1_week_start',
            'rating1_week_end',
            'rating2_week_start',
            'rating2_week_end',
            'passing_score',
            'test_default_points',
            'test_shuffle_questions',
        ])->delete();
    }

    public function down(): void
    {
        // These legacy settings are intentionally not recreated because the runtime no longer reads them.
    }
};