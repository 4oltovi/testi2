<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // Формулаи баҳо
            [
                'key' => 'formula_weight_rating1',
                'value' => '0.30',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Рейтинг 1 (бе КМ)',
                'description' => 'Коэффисиенти R1 дар формулаи баҳои ниҳоӣ (бе корҳои мустақилона)',
                'is_public' => false,
            ],
            [
                'key' => 'formula_weight_rating2',
                'value' => '0.30',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Рейтинг 2 (бе КМ)',
                'description' => 'Коэффисиенти R2 дар формулаи баҳои ниҳоӣ (бе корҳои мустақилона)',
                'is_public' => false,
            ],
            [
                'key' => 'formula_weight_exam',
                'value' => '0.40',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Имтиҳон',
                'description' => 'Коэффисиенти имтиҳон дар формулаи баҳои ниҳоӣ',
                'is_public' => false,
            ],
            [
                'key' => 'formula_weight_rating1_with_iw',
                'value' => '0.15',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Рейтинг 1 (бо КМ)',
                'description' => 'Коэффисиенти R1 ҳангоми истифодаи корҳои мустақилона',
                'is_public' => false,
            ],
            [
                'key' => 'formula_weight_rating2_with_iw',
                'value' => '0.15',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Рейтинг 2 (бо КМ)',
                'description' => 'Коэффисиенти R2 ҳангоми истифодаи корҳои мустақилона',
                'is_public' => false,
            ],
            [
                'key' => 'formula_weight_independent_work',
                'value' => '0.30',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Корҳои мустақилона (КМ)',
                'description' => 'Коэффисиенти КМ дар формулаи баҳои ниҳоӣ',
                'is_public' => false,
            ],
            [
                'key' => 'formula_weight_exam_with_iw',
                'value' => '0.40',
                'type' => 'float',
                'group' => 'formula',
                'display_name' => 'Вазни Имтиҳон (бо КМ)',
                'description' => 'Коэффисиенти имтиҳон ҳангоми истифодаи корҳои мустақилона',
                'is_public' => false,
            ],
            // Танзимоти тест
            [
                'key' => 'test_total_questions',
                'value' => '25',
                'type' => 'integer',
                'group' => 'test',
                'display_name' => 'Шумораи саволҳо дар тест',
                'description' => 'Миқдори саволҳо дар ҳар як тест',
                'is_public' => false,
            ],
            [
                'key' => 'test_time_per_question',
                'value' => '1',
                'type' => 'integer',
                'group' => 'test',
                'display_name' => 'Вақт барои ҳар савол (дақиқа)',
                'description' => 'Миқдори дақиқа барои ҳар як савол',
                'is_public' => false,
            ],
            [
                'key' => 'test_total_time',
                'value' => '25',
                'type' => 'integer',
                'group' => 'test',
                'display_name' => 'Вақти умумии тест (дақиқа)',
                'description' => 'Маҷмӯи вақт барои анҷоми тест',
                'is_public' => false,
            ],
            [
                'key' => 'test_auto_submit',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'test',
                'display_name' => 'Автоматикӣ супоридан',
                'description' => 'Ҳангоми тамом шудани вақт тест автоматикӣ супорида мешавад',
                'is_public' => false,
            ],
            [
                'key' => 'test_default_points',
                'value' => '1.0',
                'type' => 'float',
                'group' => 'test',
                'display_name' => 'Балли пешфарз барои ҳар савол',
                'description' => 'Баллро ки ҳар савол мегирад (танзимшаванда)',
                'is_public' => false,
            ],
            [
                'key' => 'test_shuffle_questions',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'test',
                'display_name' => 'Генератсияи саволҳо (омехта)',
                'description' => 'Саволҳо ҳар бор дар тартиби тасодуфӣ нишон дода мешаванд',
                'is_public' => false,
            ],
            // Танзимоти рейтинг
            [
                'key' => 'rating1_week_start',
                'value' => '1',
                'type' => 'integer',
                'group' => 'formula',
                'display_name' => 'Рейтинг 1 — ҳафтаи оғоз',
                'description' => 'Аз кадом ҳафта Рейтинг 1 оғоз мешавад',
                'is_public' => false,
            ],
            [
                'key' => 'rating1_week_end',
                'value' => '8',
                'type' => 'integer',
                'group' => 'formula',
                'display_name' => 'Рейтинг 1 — ҳафтаи анҷом',
                'description' => 'Рейтинг 1 дар кадом ҳафта тамом мешавад',
                'is_public' => false,
            ],
            [
                'key' => 'rating2_week_start',
                'value' => '9',
                'type' => 'integer',
                'group' => 'formula',
                'display_name' => 'Рейтинг 2 — ҳафтаи оғоз',
                'description' => 'Аз кадом ҳафта Рейтинг 2 оғоз мешавад',
                'is_public' => false,
            ],
            [
                'key' => 'rating2_week_end',
                'value' => '16',
                'type' => 'integer',
                'group' => 'formula',
                'display_name' => 'Рейтинг 2 — ҳафтаи анҷом',
                'description' => 'Рейтинг 2 дар кадом ҳафта тамом мешавад',
                'is_public' => false,
            ],
            [
                'key' => 'passing_score',
                'value' => '50',
                'type' => 'integer',
                'group' => 'formula',
                'display_name' => 'Ҳадди ақали гузариш (%)',
                'description' => 'Ҳадди ақали фоизи гузариш аз фан',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('group', ['formula', 'test'])->delete();
    }
};
