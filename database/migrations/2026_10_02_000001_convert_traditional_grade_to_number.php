<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Тарҷумаи сутуни матнии `traditional_grade` ба рақми 5-баллӣ (5/4/3/2).
 *
 * Сабаб: сутун то имрӯз матни «Аъло/Хуб/Қаноатбахш/Ғайриқаноатбахш» буд ва
 * дар база бо қоидаи кӯҳна навишта шуда буд. Бо ҳамин сабаб дар transcript
 * донишҷӯ бо ҳарфи D (52.13, ҳолати «гузаштааст») «Ғайриқаноатбахш» дида
 * мешуд, ҳатто ки D = 3 = Қаноатбахш аст.
 *
 * Ҳоло ҳама чо мехонанд як манбаъи ҳақиқат — `GradeScale`. Ин миграция
 * маълумоти кӯҳнаро аз рӯи ҳарфи баҳо дубора ҳисоб мекунад.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            ['table' => 'transcript_lines', 'column' => 'letter_grade'],
            ['table' => 'semester_grades', 'column' => 'letter_grade'],
        ];

        foreach ($tables as $t) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($t['table'])) {
                continue;
            }

            $rows = DB::table($t['table'])
                ->whereNotNull($t['column'])
                ->get(['id', $t['column'], 'traditional_grade']);

            foreach ($rows as $row) {
                $enum = \App\Enums\GradeScale::tryFrom((string) $row->{$t['column']});

                if ($enum === null) {
                    continue; // Ҳарфи номбурда — аз он даст намебарем
                }

                $correct = (string) $enum->traditionalFivePoint();

                if ((string) $row->traditional_grade !== $correct) {
                    DB::table($t['table'])
                        ->where('id', $row->id)
                        ->update(['traditional_grade' => $correct]);
                }
            }
        }
    }

    public function down(): void
    {
        // Барқарор кардани матн аз рақм мумкин нест, чунки қоидаи кӯҳна
        // аниқ нест. Аммо барои бехатарӣ аз ҳарфи баҳо бар мегардонанд:
        $texts = [
            5 => 'Аъло', 4 => 'Хуб', 3 => 'Қаноатбахш', 2 => 'Ғайриқаноатбахш',
        ];

        foreach (['transcript_lines', 'semester_grades'] as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $texts) {
                foreach ($rows as $row) {
                    if ($row->traditional_grade === null || ! isset($texts[(int) $row->traditional_grade])) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['traditional_grade' => $texts[(int) $row->traditional_grade]]);
                }
            });
        }
    }
};