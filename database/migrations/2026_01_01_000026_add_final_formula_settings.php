<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'rating_part_divisor'],
            [
                'value' => '4',
                'type' => 'number',
                'group' => 'formula',
                'display_name' => 'Тақсимкунандаи рейтинг: (R1+R2) ÷ X',
                'description' => 'Пешфарз: 4 → қисми рейтинг то 50 бал',
                'is_public' => 0,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'exam_weight'],
            [
                'value' => '0.5',
                'type' => 'number',
                'group' => 'formula',
                'display_name' => 'Вазни имтиҳон: Имтиҳон × X',
                'description' => 'Пешфарз: 0.5 → қисми имтиҳон то 50 бал',
                'is_public' => 0,
            ]
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', ['rating_part_divisor', 'exam_weight'])->delete();
    }
};
