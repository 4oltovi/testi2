<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'journal_part_points'],
            [
                'value'        => '60',
                'type'         => 'number',
                'group'        => 'formula',
                'display_name' => 'Балли журнал дар рейтинг (R1/R2)',
                'description'  => 'Қисми рейтинг аз журнали электронӣ. Боқӣ аз тест: 100 − ин бал (пешфарз: 60 журнал + 40 тест)',
                'is_public'    => 0,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', 'journal_part_points')->delete();
    }
};
