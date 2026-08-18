<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Қарздории академӣ
        Schema::create('academic_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_grade_id')->constrained('semester_grades')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();

            // Сабаби қарздорӣ
            $table->enum('reason', [
                'exam_failed',      // Имтиҳонро нагузашт
                'exam_absent',      // Ба имтиҳон наомад
                'rating_failed',    // Рейтинг кам
                'attendance_low',   // Давомот кам
                'not_admitted'      // Ба имтиҳон иҷозат дода нашуд
            ]);

            $table->text('description')->nullable();
            $table->date('debt_date'); // Санаи пайдо шудани қарз
            $table->decimal('original_score', 5, 2)->nullable(); // Баҳои аслӣ (ки нагузашт)
            $table->string('original_grade', 2)->nullable(); // Fx ё F

            // Имконияти такрорсупорӣ
            $table->boolean('retake_allowed')->default(false);
            $table->unsignedTinyInteger('retake_attempts_used')->default(0);
            $table->unsignedTinyInteger('max_retake_attempts')->default(2); // Ҳадди аксар 2 бор
            $table->date('retake_deadline')->nullable(); // Мӯҳлати охирин

            // Ҳолат
            $table->enum('status', [
                'active',           // Фаъол — қарз мавҷуд аст
                'retake_scheduled', // Такрорсупорӣ таъин шуд
                'resolved',         // Ҳал шуд (гузашт)
                'escalated',        // Ба комиссия фиристода шуд
                'repeat_course',    // Бояд фанро дубора хонад
                'expelled'          // Хориҷ шуд (сабаби қарздорӣ)
            ])->default('active');

            $table->date('resolved_date')->nullable();
            $table->decimal('resolved_score', 5, 2)->nullable();
            $table->string('resolved_grade', 2)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status']);
            $table->index(['semester_id', 'status']);
            $table->index(['subject_id']);
            $table->index(['status']);
            $table->index(['retake_deadline']);
        });

        // Таърихи қарздорӣ (тағйиротҳо)
        Schema::create('academic_debt_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_debt_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50); // created, retake_scheduled, resolved, escalated...
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['academic_debt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_debt_history');
        Schema::dropIfExists('academic_debts');
    }
};
