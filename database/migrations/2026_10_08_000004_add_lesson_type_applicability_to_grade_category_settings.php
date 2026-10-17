<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Байнишонаҳои мувофиқии категория ба навъи дарс:
 *  - applies_lecture / applies_practical / applies_lab (default: ҳама true).
 *  - Пешфарз: «Савод» барои Лексия = off; ҳамаи дигарон = on.
 *    (Мувофиқи талабот: Лексия 4 категория, Амалӣ/Лаборатория = 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_category_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('grade_category_settings', 'applies_lecture')) {
                $table->boolean('applies_lecture')->default(true);
            }
            if (!Schema::hasColumn('grade_category_settings', 'applies_practical')) {
                $table->boolean('applies_practical')->default(true);
            }
            if (!Schema::hasColumn('grade_category_settings', 'applies_lab')) {
                $table->boolean('applies_lab')->default(true);
            }
        });

        // ВАЖНО: сатрҳои мавҷуда тағйир дода НАМЕШАВАНд — натиҷаи ҳисоби
        // кӯҳнаи фанҳои якнавъ бояд 100% якхела бимонад. Пешфарзи нав
        // («Савод» барои лексия = off) танҳо ҳангоми сохтани сатри нав дар
        // GradeCategorySetting::getOrCreateDefaults() татбиқ мешавад.
    }

    public function down(): void
    {
        Schema::table('grade_category_settings', function (Blueprint $table) {
            foreach (['applies_lecture', 'applies_practical', 'applies_lab'] as $column) {
                if (Schema::hasColumn('grade_category_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
