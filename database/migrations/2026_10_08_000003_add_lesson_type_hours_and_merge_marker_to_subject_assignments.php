<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Як таъинот = як фан + гурӯҳ + омӯзгор + семестр (бе навъи дарс).
 *
 * - lecture_hours / practical_hours / lab_hours: соатҳои ҳар навъ (вобаста
 *   ба нақша); credits як маротиба (ҷамъи 2+2+2 = 6) дар сатр меҳисобад.
 * - merged_into_id: нишонаи нarm (на wipe) барои сатрҳои обудашуда пас аз
 *   `assignments:merge-duplicates` — сатр нест карда намешавад.
 * - Индекси кӯҳнаи unique(subject,teacher,group,lesson_type) бароварда
 *   мешавад: ҳоло навъи дарс ба таъинот вобаста нест. Индекси нав
 *   unique(subject,teacher,group,semester) аз ҷониби фармони merge пас аз
 *   обудакунӣ илова мешавад (дар testing илова намешавад, тоизолятсияи
 *   тестҳо тахриб нашавад — DDL дар MySQL транзаксияро implicit commit мекунад).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('subject_assignments', 'lecture_hours')) {
                $table->unsignedSmallInteger('lecture_hours')->nullable()->after('hours_per_week');
            }
            if (!Schema::hasColumn('subject_assignments', 'practical_hours')) {
                $table->unsignedSmallInteger('practical_hours')->nullable()->after('lecture_hours');
            }
            if (!Schema::hasColumn('subject_assignments', 'lab_hours')) {
                $table->unsignedSmallInteger('lab_hours')->nullable()->after('practical_hours');
            }
            if (!Schema::hasColumn('subject_assignments', 'merged_into_id')) {
                $table->foreignId('merged_into_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('subject_assignments')
                    ->nullOnDelete();
            }
        });

        // Индекси кӯҳна бароварда мешавад (кафолати нав: як таъинот дар як семестр)
        if ($this->hasIndex('subject_assignments', 'subject_assign_unique')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->dropUnique('subject_assign_unique');
            });
        }
    }

    public function down(): void
    {
        // Барқарорсозии индекси кӯҳна танҳо агар маълумот ба он мувофиқ бошад:
        // пас аз merge сатрҳои якхелаи (subject,teacher,group,lesson_type)
        // дар семестрҳои гуногун пайдо шуда метавонанд → индекс илова намешавад.
        if (!$this->hasIndex('subject_assignments', 'subject_assign_unique')) {
            $duplicates = DB::table('subject_assignments')
                ->select('subject_id', 'teacher_id', 'group_id', 'lesson_type', DB::raw('COUNT(*) as cnt'))
                ->groupBy('subject_id', 'teacher_id', 'group_id', 'lesson_type')
                ->having('cnt', '>', 1)
                ->exists();

            if (!$duplicates) {
                Schema::table('subject_assignments', function (Blueprint $table) {
                    $table->unique(
                        ['subject_id', 'teacher_id', 'group_id', 'lesson_type'],
                        'subject_assign_unique'
                    );
                });
            }
        }

        Schema::table('subject_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('subject_assignments', 'merged_into_id')) {
                $table->dropForeign(['merged_into_id']);
                $table->dropColumn('merged_into_id');
            }
            if (Schema::hasColumn('subject_assignments', 'lab_hours')) {
                $table->dropColumn('lab_hours');
            }
            if (Schema::hasColumn('subject_assignments', 'practical_hours')) {
                $table->dropColumn('practical_hours');
            }
            if (Schema::hasColumn('subject_assignments', 'lecture_hours')) {
                $table->dropColumn('lecture_hours');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $rows = DB::select(
                'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [$table, $index]
            );

            return (int) ($rows[0]->cnt ?? 0) > 0;
        }

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list({$table})");

            foreach ($rows as $row) {
                if (($row->name ?? '') === $index) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
