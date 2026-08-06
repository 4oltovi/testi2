<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Фанҳо (Предметҳо)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete(); // Кафедраи масъул
            $table->string('name');              // Номи фан
            $table->string('short_name', 30)->nullable();
            $table->string('code', 20)->unique(); // Рамзи фан
            $table->unsignedTinyInteger('credits'); // Шумораи кредитҳо
            $table->unsignedSmallInteger('total_hours'); // Соатҳои умумӣ
            $table->unsignedSmallInteger('lecture_hours')->default(0); // Лексия
            $table->unsignedSmallInteger('practice_hours')->default(0); // Амалӣ
            $table->unsignedSmallInteger('lab_hours')->default(0); // Лабораторӣ
            $table->unsignedSmallInteger('independent_hours')->default(0); // Мустақилона
            $table->enum('exam_type', ['exam', 'credit', 'diff_credit'])->default('exam');
            // exam=имтиҳон, credit=синҷиш, diff_credit=синҷиши бо баҳо
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active']);
        });

        // Нақшаи таълимӣ (Учебный план) — фан ба ихтисос/курс/семестр
        Schema::create('curriculum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('credits'); // Кредитҳо дар ин семестр
            $table->unsignedSmallInteger('total_hours');
            $table->unsignedSmallInteger('lecture_hours')->default(0);
            $table->unsignedSmallInteger('practice_hours')->default(0);
            $table->unsignedSmallInteger('lab_hours')->default(0);
            $table->unsignedSmallInteger('independent_hours')->default(0);
            $table->enum('exam_type', ['exam', 'credit', 'diff_credit'])->default('exam');
            $table->enum('control_type', ['rating_exam', 'rating_only', 'project', 'coursework'])->default('rating_exam');
            $table->boolean('is_elective')->default(false); // Фани интихобӣ
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['specialty_id', 'subject_id', 'semester_id']);
            $table->index(['course_id', 'semester_id']);
        });

        // Таъинкунии омӯзгор ба фан/гурӯҳ дар семестри мушаххас
        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curriculum')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->enum('lesson_type', ['lecture', 'practice', 'lab'])->default('practice');
            $table->unsignedSmallInteger('hours_per_week')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['curriculum_id', 'teacher_id', 'group_id', 'lesson_type'], 'subject_assign_unique');
            $table->index(['teacher_id', 'semester_id']);
            $table->index(['group_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_assignments');
        Schema::dropIfExists('curriculum');
        Schema::dropIfExists('subjects');
    }
};
