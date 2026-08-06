<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GPA семестрӣ (GPA per semester)
        Schema::create('semester_gpas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            $table->decimal('gpa', 4, 2); // GPA дар ин семестр
            $table->unsignedSmallInteger('credits_attempted'); // Кредитҳои кӯшиш шуда
            $table->unsignedSmallInteger('credits_earned'); // Кредитҳои гирифташуда
            $table->unsignedSmallInteger('subjects_passed'); // Фанҳои гузашта
            $table->unsignedSmallInteger('subjects_failed'); // Фанҳои нагузашта
            $table->decimal('total_grade_points', 7, 2); // Маҷмӯи grade points
            $table->unsignedSmallInteger('total_subjects'); // Шумораи умумии фанҳо

            $table->decimal('cumulative_gpa', 4, 2); // GPA кулл то ин семестр
            $table->unsignedSmallInteger('cumulative_credits_earned'); // Кредитҳои кулл

            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['student_id', 'semester_id']);
            $table->index(['semester_id', 'gpa']);
            $table->index(['student_id', 'cumulative_gpa']);
        });

        // Transcript — сабти расмии тамоми баҳоҳо
        Schema::create('transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('transcript_number', 30)->unique(); // Рақами transcript
            $table->date('issue_date'); // Санаи додан
            $table->enum('type', ['official', 'unofficial', 'partial'])->default('unofficial');

            // Маълумоти умумӣ
            $table->decimal('final_gpa', 4, 2);
            $table->unsignedSmallInteger('total_credits_earned');
            $table->unsignedSmallInteger('total_credits_required');
            $table->unsignedSmallInteger('total_subjects_passed');
            $table->unsignedSmallInteger('total_subjects');

            // Рейтинг
            $table->enum('honors', ['summa_cum_laude', 'magna_cum_laude', 'cum_laude', 'none'])->default('none');
            // summa_cum_laude: GPA >= 3.8
            // magna_cum_laude: GPA >= 3.5
            // cum_laude: GPA >= 3.0

            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable(); // Роҳи PDF
            $table->timestamps();

            $table->index(['student_id']);
        });

        // Сатрҳои transcript (Transcript Lines)
        Schema::create('transcript_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_grade_id')->nullable()->constrained('semester_grades')->nullOnDelete();

            $table->string('subject_name'); // Номи фан (snapshot)
            $table->string('subject_code', 20); // Рамзи фан (snapshot)
            $table->unsignedTinyInteger('credits');
            $table->decimal('total_score', 5, 2)->nullable();
            $table->string('letter_grade', 2);
            $table->decimal('grade_point', 4, 2);
            $table->string('traditional_grade', 30)->nullable();
            $table->enum('status', ['passed', 'failed', 'retaken', 'in_progress'])->default('passed');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['transcript_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcript_lines');
        Schema::dropIfExists('transcripts');
        Schema::dropIfExists('semester_gpas');
    }
};
