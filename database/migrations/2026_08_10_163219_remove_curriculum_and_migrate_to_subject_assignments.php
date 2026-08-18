<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграцияи нест кардани curriculum ва гузариш ба subject_assignments
     * 
     * Тартиб:
     * 1. subjects.is_elective илова мешавад
     * 2. subject_assignments.subject_id илова мешавад
     * 3. curriculum_id аз subject_assignments нест мешавад
     * 4. curriculum_id аз semester_grades нест мешавад
     * 5. curriculum_id аз academic_debts нест мешавад
     * 6. Ҷадвали curriculum нест мешавад
     */
    public function up(): void
    {
        // ============================================================
        // ҚАДАМИ 1: subjects.is_elective илова кардан
        // ============================================================
        if (!Schema::hasColumn('subjects', 'is_elective')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_elective')->default(false)->after('exam_type');
            });
        }

        // ============================================================
        // ҚАДАМИ 2: subject_assignments.subject_id илова кардан
        // ============================================================
        if (!Schema::hasColumn('subject_assignments', 'subject_id')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('curriculum_id');
            });
        }

        // ============================================================
        // ҚАДАМИ 3: subject_id-ро аз curriculum пур кардан (агар маълумот бошад)
        // Азбаски шумо гуфтед маълумот нест, ин танҳо барои бехатарӣ аст
        // ============================================================
        if (Schema::hasTable('curriculum')) {
            DB::statement('
                UPDATE subject_assignments sa
                JOIN curriculum c ON sa.curriculum_id = c.id
                SET sa.subject_id = c.subject_id
                WHERE sa.subject_id IS NULL
            ');
        }

        // ============================================================
        // ҚАДАМИ 4: subject_assignments.subject_id NOT NULL кардан
        // ============================================================
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable(false)->change();
        });

        // ============================================================
        // ҚАДАМИ 5: FK ва index-и кӯҳнаро нест кардан аз subject_assignments
        // ============================================================
        if (Schema::hasColumn('subject_assignments', 'curriculum_id')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                // Drop foreign key ба curriculum
                $table->dropForeign(['curriculum_id']);

                // Drop unique index кӯҳна
                $table->dropUnique('subject_assign_unique');

                // Drop curriculum_id column
                $table->dropColumn('curriculum_id');
            });
        }

        // ============================================================
        // ҚАДАМИ 6: FK ва index-и навро илова кардан ба subject_assignments
        // ============================================================
        Schema::table('subject_assignments', function (Blueprint $table) {
            if (!Schema::hasIndex('subject_assignments', 'subject_assign_unique')) {
                $table->unique(['subject_id', 'teacher_id', 'group_id', 'lesson_type'], 'subject_assign_unique');
            }

            // Foreign key ба subjects
            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->cascadeOnDelete();

            // Index барои суръат
            $table->index(['subject_id', 'semester_id']);
        });

        // ============================================================
        // ҚАДАМИ 7: semester_grades.curriculum_id нест кардан
        // ============================================================
        if (Schema::hasColumn('semester_grades', 'curriculum_id')) {
            Schema::table('semester_grades', function (Blueprint $table) {
                // Drop foreign key ба curriculum
                $table->dropForeign(['curriculum_id']);

                // Drop unique index кӯҳна
                $table->dropUnique('semester_grade_unique');

                // Drop curriculum_id column
                $table->dropColumn('curriculum_id');
            });
        }

        // ============================================================
        // ҚАДАМИ 8: semester_grades unique-и нав
        // ============================================================
        Schema::table('semester_grades', function (Blueprint $table) {
            if (!Schema::hasIndex('semester_grades', 'semester_grade_unique')) {
                $table->unique(
                    ['student_id', 'subject_assignment_id', 'semester_id'],
                    'semester_grade_unique'
                );
            }
        });

        // ============================================================
        // ҚАДАМИ 9: academic_debts.curriculum_id нест кардан
        // ============================================================
        if (Schema::hasColumn('academic_debts', 'curriculum_id')) {
            Schema::table('academic_debts', function (Blueprint $table) {
                // Drop foreign key ба curriculum
                $table->dropForeign(['curriculum_id']);

                // Drop curriculum_id column
                $table->dropColumn('curriculum_id');
            });
        }

        // ============================================================
        // ҚАДАМИ 10: Ҷадвали curriculum-ро нест кардан
        // ============================================================
        Schema::dropIfExists('curriculum');
    }

    /**
     * Бозгашт (rollback) — агар лозим шавад
     */
    public function down(): void
    {
        // Бозсозии ҷадвали curriculum
        Schema::create('curriculum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('credits');
            $table->unsignedSmallInteger('total_hours');
            $table->unsignedSmallInteger('lecture_hours')->default(0);
            $table->unsignedSmallInteger('practice_hours')->default(0);
            $table->unsignedSmallInteger('lab_hours')->default(0);
            $table->unsignedSmallInteger('independent_hours')->default(0);
            $table->enum('exam_type', ['exam', 'credit', 'diff_credit'])->default('exam');
            $table->enum('control_type', ['rating_exam', 'rating_only', 'project', 'coursework'])->default('rating_exam');
            $table->boolean('is_elective')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['specialty_id', 'subject_id', 'semester_id']);
            $table->index(['course_id', 'semester_id']);
        });

        // Бозгашти subject_assignments
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropUnique('subject_assign_unique');
            $table->dropIndex(['subject_id', 'semester_id']);
            $table->dropColumn('subject_id');

            $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
            $table->unique(['curriculum_id', 'teacher_id', 'group_id', 'lesson_type'], 'subject_assign_unique');
        });

        // Бозгашти semester_grades
        Schema::table('semester_grades', function (Blueprint $table) {
            $table->dropUnique('semester_grade_unique');
            $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
            $table->unique(['student_id', 'curriculum_id', 'semester_id'], 'semester_grade_unique');
        });

        // Бозгашти academic_debts
        Schema::table('academic_debts', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
        });

        // Нест кардани is_elective аз subjects
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('is_elective');
        });
    }
};
