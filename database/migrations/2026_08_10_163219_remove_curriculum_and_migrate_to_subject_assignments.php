<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'is_elective')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_elective')->default(false)->after('exam_type');
            });
        }

        if (!Schema::hasColumn('subject_assignments', 'subject_id')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('subject_assignments', 'curriculum_id')) {
                    $table->unsignedBigInteger('subject_id')->nullable()->after('curriculum_id');
                } else {
                    $table->unsignedBigInteger('subject_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('curriculum') && Schema::hasColumn('subject_assignments', 'curriculum_id')) {
            $rows = DB::table('subject_assignments')
                ->join('curriculum', 'subject_assignments.curriculum_id', '=', 'curriculum.id')
                ->whereNull('subject_assignments.subject_id')
                ->select('subject_assignments.id', 'curriculum.subject_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('subject_assignments')->where('id', $row->id)->update(['subject_id' => $row->subject_id]);
            }
        }

        if (
            Schema::hasTable('subject')
            && Schema::hasColumn('subject_assignments', 'subject_id')
            && $this->hasForeignKey('subject_assignments', 'subject_id', 'subject')
        ) {
            $rows = DB::table('subject_assignments')
                ->join('subject', 'subject_assignments.subject_id', '=', 'subject.id')
                ->select('subject_assignments.id', 'subject.subject_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('subject_assignments')->where('id', $row->id)->update(['subject_id' => $row->subject_id]);
            }
        }

        if (Schema::hasColumn('subject_assignments', 'subject_id')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_id')->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('subject_assignments', 'curriculum_id')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                if ($this->hasForeignKey('subject_assignments', 'curriculum_id')) {
                    $table->dropForeign(['curriculum_id']);
                }
                if (Schema::hasIndex('subject_assignments', 'subject_assign_unique')) {
                    $table->dropUnique('subject_assign_unique');
                }
                $table->dropColumn('curriculum_id');
            });
        }

        Schema::table('subject_assignments', function (Blueprint $table) {
            if (!Schema::hasIndex('subject_assignments', 'subject_assign_unique')) {
                $table->unique(['subject_id', 'teacher_id', 'group_id', 'lesson_type'], 'subject_assign_unique');
            }

            if ($this->hasForeignKey('subject_assignments', 'subject_id')) {
                $table->dropForeign(['subject_id']);
            }
            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->cascadeOnDelete();

            if (!Schema::hasIndex('subject_assignments', 'subject_assignments_subject_id_semester_id_index')) {
                $table->index(['subject_id', 'semester_id'], 'subject_assignments_subject_id_semester_id_index');
            }
        });

        if (Schema::hasColumn('semester_grades', 'curriculum_id')) {
            Schema::table('semester_grades', function (Blueprint $table) {
                if ($this->hasForeignKey('semester_grades', 'curriculum_id')) {
                    $table->dropForeign(['curriculum_id']);
                }
                if (Schema::hasIndex('semester_grades', 'semester_grade_unique')) {
                    $table->dropUnique('semester_grade_unique');
                }
                $table->dropColumn('curriculum_id');
            });
        }

        if (!Schema::hasIndex('semester_grades', 'semester_grade_unique')) {
            Schema::table('semester_grades', function (Blueprint $table) {
                $table->unique(
                    ['student_id', 'subject_assignment_id', 'semester_id'],
                    'semester_grade_unique'
                );
            });
        }

        if (Schema::hasColumn('academic_debts', 'curriculum_id')) {
            Schema::table('academic_debts', function (Blueprint $table) {
                if ($this->hasForeignKey('academic_debts', 'curriculum_id')) {
                    $table->dropForeign(['curriculum_id']);
                }
                $table->dropColumn('curriculum_id');
            });
        }

        if (Schema::hasTable('curriculum')) {
            Schema::dropIfExists('curriculum');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('curriculum')) {
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
        }

        Schema::table('subject_assignments', function (Blueprint $table) {
            if ($this->hasForeignKey('subject_assignments', 'subject_id')) {
                $table->dropForeign(['subject_id']);
            }
            if (Schema::hasIndex('subject_assignments', 'subject_assign_unique')) {
                $table->dropUnique('subject_assign_unique');
            }
            if (Schema::hasIndex('subject_assignments', 'subject_assignments_subject_id_semester_id_index')) {
                $table->dropIndex('subject_assignments_subject_id_semester_id_index');
            }
            if (Schema::hasColumn('subject_assignments', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
            if (!Schema::hasColumn('subject_assignments', 'curriculum_id')) {
                $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
                $table->unique(['curriculum_id', 'teacher_id', 'group_id', 'lesson_type'], 'subject_assign_unique');
            }
        });

        Schema::table('semester_grades', function (Blueprint $table) {
            if (Schema::hasIndex('semester_grades', 'semester_grade_unique')) {
                $table->dropUnique('semester_grade_unique');
            }
            if (!Schema::hasColumn('semester_grades', 'curriculum_id')) {
                $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
                $table->unique(['student_id', 'curriculum_id', 'semester_id'], 'semester_grade_unique');
            }
        });

        Schema::table('academic_debts', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_debts', 'curriculum_id')) {
                $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
            }
        });

        if (Schema::hasColumn('subjects', 'is_elective')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('is_elective');
            });
        }
    }

    private function hasForeignKey(string $table, string $column, ?string $references = null): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $rows = DB::select('
                SELECT REFERENCED_TABLE_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ', [$table, $column]);

            return collect($rows)->contains(fn ($row) => !$references || $row->REFERENCED_TABLE_NAME === $references);
        }

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list({$table})");

            return collect($rows)->contains(fn ($row) => $row->from === $column && (!$references || $row->table === $references));
        }

        return false;
    }
};
