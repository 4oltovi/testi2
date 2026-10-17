<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Навъи дарс (лексия/амалӣ/лаборатория) дар сатрҳои журнал.
 *
 * "Дарс" = жуфти (lesson_date, lesson_number). Дар ин ҷо ҷадвали алоҳидаи
 * lessons нест — сатрҳои `category_scores` ва `attendances` худи дарсро
 * нигоҳ медоранд, бинобар ин lesson_type ба ҳарду илова мешавад.
 *
 * Арзиши аввалӣ аз `subject_assignments.lesson_type`-и кӯҳна дар
 * `assignments:merge-duplicates` пур мешавад (на дар ин migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('category_scores', 'lesson_type')) {
                $table->string('lesson_type', 20)->nullable()->after('lesson_number');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'lesson_type')) {
                $table->string('lesson_type', 20)->nullable()->after('lesson_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('category_scores', function (Blueprint $table) {
            if (Schema::hasColumn('category_scores', 'lesson_type')) {
                $table->dropColumn('lesson_type');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'lesson_type')) {
                $table->dropColumn('lesson_type');
            }
        });
    }
};
