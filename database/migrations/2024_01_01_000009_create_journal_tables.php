<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Журнали электронӣ — Давомот (Attendance)
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->date('lesson_date');
            $table->unsignedTinyInteger('lesson_number'); // Рақами дарс дар он рӯз
            $table->enum('status', [
                'present',    // Ҳозир
                'absent',     // Ғоиб
                'excused',    // Ғоиби сабабнок
                'late',       // Дер омада
                'sick'        // Бемор (справка дорад)
            ])->default('present');
            $table->text('note')->nullable();
            $table->foreignId('marked_by')->constrained('users')->cascadeOnDelete(); // Кӣ сабт кард
            $table->timestamps();

            // Як донишҷӯ дар як вақт танҳо як қайд
            $table->unique(['student_id', 'subject_assignment_id', 'lesson_date', 'lesson_number'], 'attendance_unique');
            $table->index(['subject_assignment_id', 'lesson_date']);
            $table->index(['student_id', 'status']);
        });

        // Баҳоҳои ҷорӣ (Current grades / ongoing assessments)
        Schema::create('current_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->date('grade_date'); // Санаи баҳогузорӣ
            $table->unsignedTinyInteger('week_number')->nullable(); // Ҳафтаи дарсӣ (1-18)
            $table->enum('grade_type', [
                'homework',       // Вазифаи хонагӣ
                'classwork',      // Кори синфӣ
                'quiz',           // Тести кӯтоҳ
                'lab_work',       // Кори лабораторӣ
                'presentation',   // Презентатсия
                'project',        // Лоиҳа
                'essay',          // Эссе/Иншо
                'other'           // Дигар
            ]);
            $table->decimal('score', 5, 2); // Балл (аз 100)
            $table->decimal('max_score', 5, 2)->default(100); // Ҳадди аксар
            $table->text('comment')->nullable();
            $table->foreignId('graded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'subject_assignment_id', 'semester_id'], 'current_grades_main_idx');
            $table->index(['semester_id', 'week_number']);
        });

        // Рейтингҳои семестрӣ (Rating 1, Rating 2, КМ, Имтиҳон, Ниҳоӣ)
        Schema::create('semester_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();

            // Рейтинги 1 (Ҳафтаи 1-8) — аз 100
            $table->decimal('rating1_score', 5, 2)->nullable();
            // Рейтинги 2 (Ҳафтаи 9-16) — аз 100
            $table->decimal('rating2_score', 5, 2)->nullable();
            // Корҳои мустақилона — аз 100
            $table->decimal('independent_work_score', 5, 2)->nullable();
            // Имтиҳони асосӣ — аз 100
            $table->decimal('exam_score', 5, 2)->nullable();
            // Имтиҳони такрорӣ (retake) — аз 100
            $table->decimal('retake_score', 5, 2)->nullable();
            // Имтиҳони такрорӣ 2 (комиссионӣ) — аз 100
            $table->decimal('retake2_score', 5, 2)->nullable();

            // Баҳои ниҳоӣ ҳисобшуда
            $table->decimal('total_score', 5, 2)->nullable(); // Маҷмӯи фоизӣ (0-100)
            $table->string('letter_grade', 2)->nullable(); // A, A-, B+, B, B-, C+, C, C-, D+, D, Fx, F
            $table->decimal('grade_point', 4, 2)->nullable(); // 4.0, 3.67, 3.33...
            $table->string('traditional_grade', 30)->nullable(); // Аъло, Хуб, Қаноатбахш, Ғайриқаноатбахш

            // Кредити гирифташуда
            $table->unsignedTinyInteger('credits_earned')->default(0);

            // Ҳолат
            $table->enum('status', [
                'in_progress',  // Дар ҷараён
                'passed',       // Гузашт
                'failed',       // Нагузашт
                'retake',       // Дар такрорсупорӣ
                'retake2',      // Дар комиссияи такрорӣ
                'debt',         // Қарздор
                'exempt'        // Озод (перевод/барқарор)
            ])->default('in_progress');

            // Таърих
            $table->timestamp('rating1_date')->nullable();
            $table->timestamp('rating2_date')->nullable();
            $table->timestamp('exam_date')->nullable();
            $table->timestamp('retake_date')->nullable();
            $table->timestamp('retake2_date')->nullable();
            $table->timestamp('finalized_at')->nullable(); // Санаи тасдиқ

            $table->foreignId('exam_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_finalized')->default(false);

            $table->timestamps();

            // Як донишҷӯ — як фан — як семестр
            $table->unique(['student_id', 'curriculum_id', 'semester_id'], 'semester_grade_unique');
            $table->index(['semester_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['letter_grade']);
            $table->index(['is_finalized']);
        });

        // Таърихи тағйиротҳои журнал (Audit trail for grade changes)
        Schema::create('grade_change_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_grade_id')->constrained('semester_grades')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('field_changed', 50); // Кадом майдон тағйир ёфт
            $table->string('old_value', 50)->nullable();
            $table->string('new_value', 50)->nullable();
            $table->text('reason')->nullable(); // Сабаби тағйир
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['semester_grade_id']);
            $table->index(['student_id', 'created_at']);
            $table->index(['changed_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_change_log');
        Schema::dropIfExists('semester_grades');
        Schema::dropIfExists('current_grades');
        Schema::dropIfExists('attendances');
    }
};
